<?php
//
// Description
// -----------
// This method will delete a offering date from the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the offering date is attached to.
// date_id:             The ID of the offering date to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_fatt_offeringDateDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'date_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Date'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.offeringDateDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the uuid of the offering date to be deleted
    //
    $strsql = "SELECT uuid, offering_id "
        . "FROM ciniki_fatt_offering_dates "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'date');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['date']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.101', 'msg'=>'The date does not exist'));
    }
    $date_uuid = $rc['date']['uuid'];
    $offering_id = $rc['date']['offering_id'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Remove the location
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.fatt.offeringdate', 
        $args['date_id'], $date_uuid, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }

    //
    // Update the offering the seats incase something has changed in location size
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateDatesSeats');
    $rc = ciniki_fatt_offeringUpdateDatesSeats($ciniki, $args['tnid'], $offering_id, 'yes');
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
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'fatt');

    $rsp = array('stat'=>'ok');

    //
    // Load the offering and return
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringLoad');
    $rc = ciniki_fatt_offeringLoad($ciniki, $args['tnid'], $offering_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['offering']) ) {
        $rsp['offering'] = $rc['offering'];
    }

    return $rsp;
}
?>
