<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_fatt_hooks_uiSettings($ciniki, $business_id, $args) {

	$settings = array();

	//
	// Load the courses and instructors for the business
	//
	if( ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x01) > 0 ) {
		$strsql = "SELECT id, name "
			. "FROM ciniki_fatt_courses "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND status = 10 "
			. "ORDER BY name "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'courses', 'fname'=>'id', 'name'=>'course',
				'fields'=>array('id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['courses']) ) {
			$settings['courses'] = $rc['courses'];
		}

		$strsql = "SELECT id, name "
			. "FROM ciniki_fatt_instructors "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND status = 10 "
			. "ORDER BY name "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'instructors', 'fname'=>'id', 'name'=>'instructor',
				'fields'=>array('id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['instructors']) ) {
			$settings['instructors'] = $rc['instructors'];
		}
	}

	//
	// Load the fatt locations for the business
	//
	if( ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x04) > 0 ) {
		$strsql = "SELECT id, name "
			. "FROM ciniki_fatt_locations "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND status = 10 "
			. "ORDER BY name "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'locations', 'fname'=>'id', 'name'=>'location',
				'fields'=>array('id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['locations']) ) {
			$settings['locations'] = $rc['locations'];
		}
	}

	//
	// Load the certs for the business
	//
	if( ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x10) > 0 ) {
		$strsql = "SELECT id, name "
			. "FROM ciniki_fatt_certs "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND status = 10 "
			. "ORDER BY name "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'certs', 'fname'=>'id', 'name'=>'cert',
				'fields'=>array('id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['certs']) ) {
			$settings['certs'] = $rc['certs'];
		}
	}

	return array('stat'=>'ok', 'settings'=>$settings);	
}
?>
