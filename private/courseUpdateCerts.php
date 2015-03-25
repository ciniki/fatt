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
function ciniki_fatt_courseUpdateCerts($ciniki, $business_id, $course_id, $ncerts) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList2');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

	//
	// Get the list of existing/old certs for the course
	//
	$strsql = "SELECT cert_id, id "
		. "FROM ciniki_fatt_course_certs "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND course_id = '" . ciniki_core_dbQuote($ciniki, $course_id) . "' "
		. "";
	$rc = ciniki_core_dbQueryList2($ciniki, $strsql, 'ciniki.fatt', 'certs');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$ocerts = $rc['certs'];
	
	// 
	// Check if new certs need to be added
	//
	foreach($ncerts as $cid) {
		if( !isset($ocerts[$cid]) ) {
			// Add cert link
			$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.fatt.course_cert', 
				array('cert_id'=>$cid, 'course_id'=>$course_id), 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	//
	// Check of old certs need to be removed
	//
	foreach($ocerts as $cid => $object_id) {
		if( !in_array($cid, $ncerts) ) {
			$rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.fatt.course_cert', $object_id, null, 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}
	}

	return array('stat'=>'ok');
}
?>
