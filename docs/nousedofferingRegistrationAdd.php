<?php
//
// Description
// ===========
// This method will update an offering registration and the connected invoice item.
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
function ciniki_fatt_offeringRegistrationAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        'student_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Student'),
        'customer_notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Customer Notes'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Notes'), 
        'test_results'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Test Results'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.offeringRegistrationAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Create the invoice
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'public', 'invoiceAdd');
    $rc = ciniki_sapos_invoiceAdd($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice = $rc['invoice'];

    //
    // Get the registration_id, should be the first one we just created this invoice
    //
    if( !isset($invoice['items']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2374', 'msg'=>'Internal error creating invoice'));
    }

    foreach($invoice['items'] as $item) {
        if( $item['object'] == 'ciniki.fatt.offeringregistration' ) {
            $registration_id = $item['object_id'];
        }
    }

    if( !isset($registration_id) || $registration_id === NULL || $registration_id == 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2375', 'msg'=>'Internal error creating invoice'));
    }

    //
    // Update the student_id for the registration
    //
    if( isset($args['student_id']) && $args['student_id'] != $args['customer_id'] ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.fatt.offeringregistration', $registration_id, array('student_id'=>$args['student_id']), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2376', 'msg'=>'Unable to update student', 'err'=>$rc['err']));
        }
    }
    
    //
    // Get the registration
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'registrationLoad');
    return ciniki_fatt_registrationLoad($ciniki, $args['business_id'], $registration_id);   
}
?>
