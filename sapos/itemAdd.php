<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_fatt_sapos_itemAdd($ciniki, $tnid, $invoice_id, $item) {
    //
    // An offering was added to an invoice item, get the details and see if we need to 
    // create a registration for this offering
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.fatt.offering' && isset($item['object_id']) ) {
        //
        // Check the offering exists
        //
        $strsql = "SELECT id, seats_remaining "
            . "FROM ciniki_fatt_offerings "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'offering');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['offering']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.121', 'msg'=>'Unable to find item'));
        }
        $offering = $rc['offering'];

        //
        // Load the customer for the invoice
        //
        $strsql = "SELECT id, customer_id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.122', 'msg'=>'Unable to find invoice'));
        }
        $invoice = $rc['invoice'];
        
        //
        // Create the registration for the customer
        //
        $reg_args = array('offering_id'=>$offering['id'],
            'customer_id'=>$invoice['customer_id'],
            'student_id'=>(isset($item['student_id']) ? $item['student_id'] : $invoice['customer_id']),
            'invoice_id'=>$invoice['id'],
            );
        if( isset($item['registration_status']) && $item['registration_status'] != '' ) {
            $reg_args['status'] = $item['registration_status'];
        }
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.fatt.offeringregistration', $reg_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $reg_id = $rc['id'];

        $rsp = array('stat'=>'ok', 'object'=>'ciniki.fatt.offeringregistration', 'object_id'=>$reg_id);

        //
        // Get the student name if the student is different from customer
        //
        if( $reg_args['student_id'] != $reg_args['customer_id'] ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, array('customer_id'=>$reg_args['student_id']));
            if( $rc['stat'] == 'ok' && isset($rc['customer']['display_name']) ) {
                $rsp['notes'] = $rc['customer']['display_name'];
            }
        }

        //
        // Update the offering
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateDatesSeats');
        $rc = ciniki_fatt_offeringUpdateDatesSeats($ciniki, $tnid, $item['object_id'], 'yes');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.123', 'msg'=>'Unable to update offering', 'err'=>$rc['err']));
        }

        return $rsp;
    }

/*
    //
    // If a registration was added to an invoice, update the invoice_id for the registration
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.fatt.registration' && isset($item['object_id']) ) {
        //
        // Check the registration exists
        //
        $strsql = "SELECT id, invoice_id "
            . "FROM ciniki_fatt_offering_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'registration');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['registration']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.124', 'msg'=>'Unable to find offering registration'));
        }
        $registration = $rc['registration'];
    
        //
        // If the registration does not already have an invoice
        //
        if( $registration['invoice_id'] == '0' ) {
            $reg_args = array('invoice_id'=>$invoice_id);
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.fatt.registration', 
                $registration['id'], $reg_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            return array('stat'=>'ok');
        }
    }
*/

    return array('stat'=>'ok');
}
?>
