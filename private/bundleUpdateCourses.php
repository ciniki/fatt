<?php
//
// Description
// ===========
// This function will update the list of bundles to which a course is assigned.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the course is attached to.
// 
function ciniki_fatt_bundleUpdateCourses($ciniki, $business_id, $bundle_id, $ncoursess) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

	//
	// Get the list of existing/old bundles for the course
	//
	$strsql = "SELECT course_id, id "
		. "FROM ciniki_fatt_course_bundles "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND bundle_id = '" . ciniki_core_dbQuote($ciniki, $bundle_id) . "' "
		. "";
	$rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.fatt', 'bundles');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$ocourses = $rc['bundles'];
	
	// 
	// Check if new bundles need to be added
	//
	foreach($ncoursess as $cid) {
		if( !isset($ocourses[$cid]) ) {
			// Add bundle link
			$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.fatt.course_bundle', 
				array('course_id'=>$cid, 'bundle_id'=>$bundle_id), 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Check of old bundles need to be removed
	//
	foreach($ocourses as $cid => $object_id) {
		if( !in_array($cid, $ncoursess) ) {
			$rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.fatt.course_bundle', $object_id, null, 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>
