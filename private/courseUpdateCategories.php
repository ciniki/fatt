<?php
//
// Description
// ===========
// This function will update the list of categories to which a course is assigned.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the course is attached to.
// 
function ciniki_fatt_courseUpdateCategories($ciniki, $business_id, $course_id, $ncategories) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

	//
	// Get the list of existing/old categories for the course
	//
	$strsql = "SELECT category_id, id "
		. "FROM ciniki_fatt_course_categories "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND course_id = '" . ciniki_core_dbQuote($ciniki, $course_id) . "' "
		. "";
	$rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.fatt', 'categories');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$ocategories = $rc['categories'];
	
	// 
	// Check if new categories need to be added
	//
	foreach($ncategories as $cid) {
		if( !isset($ocategories[$cid]) ) {
			// Add category link
			$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.fatt.course_category', 
				array('category_id'=>$cid, 'course_id'=>$course_id), 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Check of old categories need to be removed
	//
	foreach($ocategories as $cid => $object_id) {
		if( !in_array($cid, $ncategories) ) {
			$rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.fatt.course_category', $object_id, null, 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>
