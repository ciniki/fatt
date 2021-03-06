<?php
//
// Description
// -----------
// This method will add a new cert for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to add the cert to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_fatt_certCustomerAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'cert_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Certification'), 
        'customer_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Customer'), 
        'offering_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Offering'), 
        'date_received'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Certification Date'), 
        'date_expiry'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Certification Expiry'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.certCustomerAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
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
    // Add the cert to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'certCustomerAdd');
    $rc = ciniki_fatt__certCustomerAdd($ciniki, $args['tnid'], $args);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }
    $certcustomer_id = $rc['id'];

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'fatt');

    return array('stat'=>'ok', 'id'=>$certcustomer_id);
}
?>
