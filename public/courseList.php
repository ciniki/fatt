<?php
//
// Description
// -----------
// This method will return the list of courses for a business.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get courses for.
//
// Returns
// -------
//
function ciniki_fatt_courseList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Categories'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.courseList');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the time information for business and user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	//
	// Load fatt maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'maps');
	$rc = ciniki_fatt_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// Get the list of courses
	//
	$strsql = "SELECT ciniki_fatt_courses.id, "
		. "ciniki_fatt_courses.name, "
		. "ciniki_fatt_courses.code, "
		. "ciniki_fatt_courses.price, "
		. "ciniki_fatt_courses.status AS status_text "
		. "FROM ciniki_fatt_courses "
		. "WHERE ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY name "
		. "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
		array('container'=>'courses', 'fname'=>'id', 'name'=>'course',
			'fields'=>array('id', 'name', 'code', 'price', 'status_text'),
			'maps'=>array('status_text'=>$maps['course']['status'])),
		));
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$rsp = array('stat'=>'ok', 'courses'=>array());
	if( isset($rc['courses']) ) {
		$rsp['courses'] = $rc['courses'];
		foreach($rsp['courses'] as $cid => $course) {
			$rsp['courses'][$cid]['course']['price'] = numfmt_format_currency($intl_currency_fmt, $course['course']['price'], $intl_currency);
		}
	}

	//
	// Check if we should return the categories as well
	//
	if( isset($args['categories']) && $args['categories'] == 'yes' 
		&& isset($ciniki['business']['modules']['ciniki.fatt']['flags'])
		&& ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x02) > 0 
		) {
		$strsql = "SELECT ciniki_fatt_categories.id, "
			. "ciniki_fatt_categories.name, "
			. "ciniki_fatt_categories.permalink "
			. "FROM ciniki_fatt_categories "
			. "WHERE ciniki_fatt_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY name "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'categories', 'fname'=>'id', 'name'=>'category',
				'fields'=>array('id', 'name', 'permalink')),
			));
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( isset($rc['categories']) ) {
			$rsp['categories'] = $rc['categories'];
		}
	}

	return $rsp;
}
?>
