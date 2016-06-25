<?php
//
// Description
// ===========
// This method returns the invoice for a customer or their parent if it exists for a class.
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
// Returns
// -------
//
function ciniki_fatt_classCustomerInvoice($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.classCustomerInvoice'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Check for parent_id on customer
    //
    $strsql = "SELECT parent_id "
        . "FROM ciniki_customers "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customer']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3301', 'msg'=>'Customer does not exist'));
    }
    if( $rc['customer']['parent_id'] > 0 ) {
        $args['customer_id'] = $rc['customer']['parent_id'];
    }

    //
    // Check for an existing invoice
    //
    $strsql = "SELECT r1.invoice_id, r1.customer_id "
        . "FROM ciniki_fatt_offering_dates AS d1, ciniki_fatt_offering_dates AS d2, ciniki_fatt_offering_registrations AS r1 "
        . "WHERE d1.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "AND d1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND d1.start_date = d2.start_date "
        . "AND d1.location_id = d2.location_id "
        . "AND d2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND d2.offering_id = r1.offering_id "
        . "AND r1.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
        . "AND r1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $invoice_id = 0;
    if( isset($rc['rows']) ) {
        foreach($rc['rows'] as $row) {
            if( $row['customer_id'] == $args['customer_id'] && $row['invoice_id'] > 0 ) {
                $invoice_id = $row['invoice_id'];
            }
        }
    }
    
    //
    // Get the offering date
    //
    $strsql = "SELECT start_date "
        . "FROM ciniki_fatt_offerings "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'offering');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $start_date = '';
    if( isset($rc['offering']['start_date']) ) {
        $start_date = $rc['offering']['start_date'];
    }

    return array('stat'=>'ok', 'invoice_id'=>$invoice_id, 'start_date'=>$start_date);
}
?>
