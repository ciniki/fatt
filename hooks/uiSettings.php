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
	// Get the time information for business and user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	//
	// Load the courses and instructors for the business
	//
	if( ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x01) > 0 ) {
		$strsql = "SELECT id, name, price, num_days "
			. "FROM ciniki_fatt_courses "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND status = 10 "
			. "ORDER BY name "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'courses', 'fname'=>'id', 'name'=>'course',
				'fields'=>array('id', 'name', 'price', 'num_days')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['courses']) ) {
			$settings['courses'] = $rc['courses'];
			foreach($settings['courses'] as $cid => $course) {
				$settings['courses'][$cid]['course']['price'] = numfmt_format_currency($intl_currency_fmt, 
					$settings['courses'][$cid]['course']['price'], $intl_currency);
			}
		}
		if( ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x40) > 0 ) {
			$strsql = "SELECT ciniki_fatt_bundles.id, ciniki_fatt_bundles.name, MAX(num_days) as num_days "
				. "FROM ciniki_fatt_bundles, ciniki_fatt_course_bundles, ciniki_fatt_courses "
				. "WHERE ciniki_fatt_bundles.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_fatt_bundles.id = ciniki_fatt_course_bundles.bundle_id "
				. "AND ciniki_fatt_course_bundles.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_fatt_course_bundles.course_id = ciniki_fatt_courses.id "
				. "AND ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_fatt_courses.status = 10 "
				. "GROUP BY ciniki_fatt_bundles.id "
				. "ORDER BY ciniki_fatt_bundles.name "
				. "";
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
			$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
				array('container'=>'bundles', 'fname'=>'id', 'name'=>'bundle',
					'fields'=>array('id', 'name', 'num_days')),
				));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( isset($rc['bundles']) ) {
				$settings['bundles'] = $rc['bundles'];
			}
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
