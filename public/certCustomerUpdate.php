<?php
//
// Description
// ===========
// This method will update a cert in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the cert is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_fatt_certCustomerUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'certcustomer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Certification'), 
        'cert_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Certification'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'), 
        'date_received'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Certification Date'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.certCustomerUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the time information for business and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    date_default_timezone_set($intl_timezone);

    //
    // Get the existing cert customer
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectGet');
    $rc = ciniki_core_objectGet($ciniki, $args['business_id'], 'ciniki.fatt.certcustomer', $args['certcustomer_id']);
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    if( !isset($rc['certcustomer']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.71', 'msg'=>'Certfication not found'));
    }
    $certcustomer = $rc['certcustomer'];

    //
    // Check if expiry date should be updated
    //
    if( (isset($args['cert_id']) && $args['cert_id'] != $certcustomer['cert_id'])       // Did the cert change?
        || (isset($args['date_received']) && $args['date_received'] != '')              // Did the date received changed?
        ) {
        if( isset($args['date_received']) && $args['date_received'] != '' ) {
            $dt = new DateTime($args['date_received'], new DateTimeZone($intl_timezone));
        } else {
            $dt = new DateTime($certcustomer['date_received'], new DateTimeZone($intl_timezone));
        }
        if( isset($args['cert_id']) && $args['cert_id'] != '' ) {
            $rc = ciniki_core_objectGet($ciniki, $args['business_id'], 'ciniki.fatt.cert', $args['cert_id']);
        } else {
            $rc = ciniki_core_objectGet($ciniki, $args['business_id'], 'ciniki.fatt.cert', $certcustomer['cert_id']);
        }
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['cert']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.72', 'msg'=>'The certification does not exist'));
        }
        $cert = $rc['cert'];
        
        //
        // Setup the expiry date, based on date received and years_valid from cert 
        //
        if( $cert['years_valid'] > 0 ) {
            $dt->add(new DateInterval('P' . $cert['years_valid'] . 'Y'));
            $args['date_expiry'] = $dt->format('Y-m-d');
        } else {
            $args['date_expiry'] = '';
        }
    }

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
    // Update the cert in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.fatt.certcustomer', $args['certcustomer_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
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
