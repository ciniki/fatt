<?php
//
// Description
// ===========
// This method will move a registration from one offering to another. If the offerings are on different dates
// or different locations, the customer may be moved invoices as well.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the offering is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_fatt_offeringRegistrationSwitchOffering(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'),
        'item_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Invoice Item'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.offeringRegistrationSwitchOffering'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the current registration
    //
    $strsql = "SELECT id, offering_id, customer_id, student_id, invoice_id, status "
        . "FROM ciniki_fatt_offering_registrations "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.111', 'msg'=>'Registration not found'));
    }
    $registration = $rc['registration'];

    //
    // Lookup the current offering
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringLoad');
    $rc = ciniki_fatt_offeringLoad($ciniki, $args['business_id'], $registration['offering_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['offering']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.112', 'msg'=>'Offering not found'));
    }
    $current_offering = $rc['offering'];

    //
    // Lookup the new offering
    //
    $rc = ciniki_fatt_offeringLoad($ciniki, $args['business_id'], $args['offering_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['offering']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.113', 'msg'=>'Offering not found'));
    }
    $new_offering = $rc['offering'];

    //
    // Load the current class
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'classLoad');
    $rc = ciniki_fatt_classLoad($ciniki, $args['business_id'], array('start_ts'=>$current_offering['start_date_ts'], 'location_id'=>$current_offering['location_id']));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['class']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.114', 'msg'=>'Class not found'));
    }
    $current_class = $rc['class'];

    //
    // Load the new class
    //
    $rc = ciniki_fatt_classLoad($ciniki, $args['business_id'], array('start_ts'=>$new_offering['start_date_ts'], 'location_id'=>$new_offering['location_id']));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['class']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.115', 'msg'=>'Class not found'));
    }
    $new_class = $rc['class'];

    //
    // When the customer is changing between course dates, they may need to be switched invoices. 
    // If the customer is the only one on the invoice they do not need to change invoices.
    // If the customer is a employee of a business, and there are others on the invoice, they should be 
    // removed from the invoice and either added to a new invoice, or added to an existing invoice on the new date
    //
    if( $current_offering['start_date'] != $new_offering['start_date'] 
        || $current_offering['location'] != $new_offering['location']
        ) {
        //
        // Check if others are on the invoice
        //
        if( isset($current_class['registrations']) ) {
            foreach($current_class['registrations'] as $rid => $reg) {
                if( $registration['id'] != $reg['registration']['id'] && $registration['invoice_id'] == $reg['registration']['invoice_id'] ) {
                    //
                    // The invoice has other students for the old offering, need a new invoice
                    //
                    $new_invoice_id = 0;
                }
            }
        }
        
        //
        // Check if there is already an invoice for this customer in the new class
        //
        if( isset($new_class['registrations']) ) {
            foreach($new_class['registrations'] as $rid => $reg) {
                if( $reg['registration']['customer_id'] == $registration['customer_id'] ) {
                    $new_invoice_id = $reg['registration']['invoice_id'];
                }
            }
        }
    }

    //
    // Lookup the course information
    //
    $strsql = "SELECT ciniki_fatt_courses.name, "
        . "ciniki_fatt_courses.code, "
        . "ciniki_fatt_courses.price "
        . "FROM ciniki_fatt_offerings "
        . "LEFT JOIN ciniki_fatt_courses ON ("
            . "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
            . "AND ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_fatt_offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'course');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['course']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.116', 'msg'=>'Course not found'));
    }
    $course = $rc['course'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Update the invoice item
    //
    $item_args = array(
        'item_id'=>$args['item_id'],
        'description'=>$course['name'],
        'unit_amount'=>$course['price'],
        );
    if( isset($new_invoice_id) ) {
        $item_args['new_invoice_id'] = $new_invoice_id;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemUpdate');
    $rc = ciniki_sapos_hooks_invoiceItemUpdate($ciniki, $args['business_id'], $item_args);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }
    if( isset($rc['invoice_id']) ) {
        $args['invoice_id'] = $rc['invoice_id'];
    }

    //
    // Update the registration in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.fatt.offeringregistration', $args['registration_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }

    //
    // Check if the status changed
    //
    if( isset($args['status']) && $args['status'] != $registration['status'] ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringRegistrationUpdateCerts');
        $rc = ciniki_fatt_offeringRegistrationUpdateCerts($ciniki, $args['business_id'], $args['registration_id'], $registration);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
    }

    //
    // Update the seats of the old/existing offering
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateDatesSeats');
    $rc = ciniki_fatt_offeringUpdateDatesSeats($ciniki, $args['business_id'], $registration['offering_id'], 'yes');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }

    //
    // Update the seats of the new offering if the offering is on a different date
    //
    if( $current_offering['start_date'] != $new_offering['start_date'] ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateDatesSeats');
        $rc = ciniki_fatt_offeringUpdateDatesSeats($ciniki, $args['business_id'], $args['offering_id'], 'yes');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'fatt');

    return array('stat'=>'ok');
}
?>
