<?php
//
// Description
// ===========
// This function will update the list of certs to which a course is assigned.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the course is attached to.
// 
function ciniki_fatt_certUpdateCourses($ciniki, $business_id, $cert_id, $ncourses) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

	//
	// Get the list of existing/old courses for the cert
	//
	$strsql = "SELECT course_id, id "
		. "FROM ciniki_fatt_course_certs "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND cert_id = '" . ciniki_core_dbQuote($ciniki, $cert_id) . "' "
		. "";
	$rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.fatt', 'courses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$ocourses = $rc['courses'];
	
	// 
	// Check if new certs need to be added
	//
	foreach($ncourses as $cid) {
		if( !isset($ocourses[$cid]) ) {
			// Add cert link
			$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.fatt.course_cert', 
				array('course_id'=>$cid, 'cert_id'=>$cert_id), 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Check of old certs need to be removed
	//
	foreach($ocourses as $cid => $object_id) {
		if( !in_array($cid, $ncourses) ) {
			$rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.fatt.course_cert', $object_id, null, 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>
