<?php
//
// Description
// -----------
// This method will delete a course from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the course is attached to.
// course_id:			The ID of the course to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_fatt_courseDelete(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'course_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Course'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
	$rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.courseDelete');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the uuid of the course to be deleted
	//
	$strsql = "SELECT uuid "
		. "FROM ciniki_fatt_courses "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'course');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['course']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2318', 'msg'=>'The course does not exist'));
	}
	$course_uuid = $rc['course']['uuid'];

	//
	// Check if there is any offerings still attached to the course
	//
	$strsql = "SELECT 'items', COUNT(*) "
		. "FROM ciniki_fatt_offerings "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
	$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.fatt', 'num');
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
		$count = $rc['num']['items'];
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2337', 'msg'=>'There ' . ($count==1?'is':'are') . ' still ' . $count . ' offering' . ($count==1?'':'s') . ' assigned to that course.'));
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
	// Remove the course
	//
	$rc = ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.fatt.course', 
		$args['course_id'], $course_uuid, 0x04);
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
