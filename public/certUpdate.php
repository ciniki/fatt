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
// tnid:     The ID of the tenant the cert is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_fatt_certUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'cert_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Certification'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
        'grouping'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Grouping'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'years_valid'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Valid For'), 
        'alt_cert_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Alternate Cert'), 
        'courses'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Courses'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.certUpdate'); 
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
    // Update the cert in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.cert', $args['cert_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }

    //
    // Update the courses
    //
    if( isset($args['courses']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'certUpdateCourses');
        $rc = ciniki_fatt_certUpdateCourses($ciniki, $args['tnid'], $args['cert_id'], $args['courses']);
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'fatt');

    return array('stat'=>'ok');
}
?>
