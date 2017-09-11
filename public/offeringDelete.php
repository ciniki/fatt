<?php
//
// Description
// -----------
// This method will delete a offering from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the offering is attached to.
// offering_id:         The ID of the offering to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_fatt_offeringDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'offering_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Course Offering'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.offeringDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if there is any customers still attached to the offering
    //
    $strsql = "SELECT 'items', COUNT(*) "
        . "FROM ciniki_fatt_offering_registrations "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.fatt', 'num');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
        $count = $rc['num']['items'];
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.105', 'msg'=>'There ' . ($count==1?'is':'are') . ' still ' . $count . ' registration' . ($count==1?'':'s') . ' assigned to that course offering.'));
    }

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
    // Remove the offering
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringRemove');
    $rc = ciniki_fatt_offeringRemove($ciniki, $args['business_id'], $args['offering_id']);
    if( $rc['stat'] != 'ok' ) {
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
