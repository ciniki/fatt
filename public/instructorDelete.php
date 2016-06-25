<?php
//
// Description
// -----------
// This method will delete a instructor from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the instructor is attached to.
// instructor_id:           The ID of the instructor to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_fatt_instructorDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'instructor_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Instructor'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.instructorDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the uuid of the instructor to be deleted
    //
    $strsql = "SELECT uuid "
        . "FROM ciniki_fatt_instructors "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['instructor_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'instructor');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['instructor']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2298', 'msg'=>'The instructor does not exist'));
    }
    $instructor_uuid = $rc['instructor']['uuid'];

    //
    // Check if there is any courses still attached to the instructor
    //
    $strsql = "SELECT 'items', COUNT(*) "
        . "FROM ciniki_fatt_offering_instructors "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND instructor_id = '" . ciniki_core_dbQuote($ciniki, $args['instructor_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.fatt', 'num');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
        $count = $rc['num']['items'];
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2299', 'msg'=>'There ' . ($count==1?'is':'are') . ' still ' . $count . ' course' . ($count==1?'':'s') . ' assigned to that instructor.'));
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
    // Remove the instructor
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.fatt.instructor', 
        $args['instructor_id'], $instructor_uuid, 0x04);
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
