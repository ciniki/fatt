<?php
//
// Description
// ===========
// This method will return all the information about a course.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the course is attached to.
// course_id:		The ID of the course to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_courseGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        'messages'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Messages'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.courseGet'); 
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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

	if( $args['course_id'] == 0 ) {
		$rsp = array('stat'=>'ok', 'course'=>array(
			'name'=>'',
			'status'=>'10',
			'primary_image_id'=>'0',
			'flags'=>'0',
			));
	} else {
		//
		// Get the course details
		//
		$strsql = "SELECT ciniki_fatt_courses.id, "
			. "ciniki_fatt_courses.name, "
			. "ciniki_fatt_courses.code, "
			. "ciniki_fatt_courses.permalink, "
			. "ciniki_fatt_courses.status, "
			. "ciniki_fatt_courses.primary_image_id, "
			. "ciniki_fatt_courses.synopsis, "
			. "ciniki_fatt_courses.description, "
			. "ciniki_fatt_courses.price, "
			. "ciniki_fatt_courses.taxtype_id, "
			. "ciniki_fatt_courses.num_days, "
			. "ciniki_fatt_courses.num_hours, "
			. "ciniki_fatt_courses.num_seats_per_instructor, "
			. "ciniki_fatt_courses.flags, "
			. "ciniki_fatt_courses.cert_form "
			. "FROM ciniki_fatt_courses "
			. "WHERE ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_fatt_courses.id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'courses', 'fname'=>'id', 'name'=>'course',
				'fields'=>array('id', 'name', 'code', 'status', 'primary_image_id', 'synopsis', 'description', 
					'price', 'taxtype_id', 'num_days', 'num_hours', 'num_seats_per_instructor', 'flags', 'cert_form')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['courses']) || !isset($rc['courses'][0]) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2320', 'msg'=>'Unable to find course'));
		}
		$rsp = array('stat'=>'ok', 'course'=>$rc['courses'][0]['course']);
		$rsp['course']['price'] = numfmt_format_currency($intl_currency_fmt, $rsp['course']['price'], $intl_currency);
		$rsp['course']['num_hours'] = (float)$rsp['course']['num_hours'];
	}

	//
	// Get the categories for the course and the business
	//
	if( ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x02) > 0 ) {
		$rsp['course']['categories'] = '';
		$strsql = "SELECT ciniki_fatt_categories.id, "
			. "ciniki_fatt_categories.name, "
			. "ciniki_fatt_categories.sequence, "
			. "IFNULL(ciniki_fatt_course_categories.id, 0) AS link_id "
			. "FROM ciniki_fatt_categories "
			. "LEFT JOIN ciniki_fatt_course_categories ON ("
				. "ciniki_fatt_categories.id = ciniki_fatt_course_categories.category_id "
				. "AND ciniki_fatt_course_categories.course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
				. "AND ciniki_fatt_course_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_fatt_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY ciniki_fatt_categories.sequence, ciniki_fatt_categories.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'categories', 'fname'=>'id', 'name'=>'item',
				'fields'=>array('id', 'name', 'sequence', 'link_id')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$rsp['categories'] = array();
		if( isset($rc['categories']) ) {
			$rsp['categories'] = $rc['categories'];
			foreach($rsp['categories'] as $cid => $item) {
				if( $item['item']['link_id'] > 0 ) {
					$rsp['course']['categories'] .= ($rsp['course']['categories']!=''?',':'') . $item['item']['id'];
				}
				unset($rsp['categories'][$cid]['item']['link_id']);
			}
		}
	}

	//
	// Get the certs for the course and the business
	//
	if( ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x10) > 0 ) {
		$rsp['course']['certs'] = '';
		$strsql = "SELECT ciniki_fatt_certs.id, "
			. "ciniki_fatt_certs.name, "
			. "IFNULL(ciniki_fatt_course_certs.id, 0) AS link_id "
			. "FROM ciniki_fatt_certs "
			. "LEFT JOIN ciniki_fatt_course_certs ON ("
				. "ciniki_fatt_certs.id = ciniki_fatt_course_certs.cert_id "
				. "AND ciniki_fatt_course_certs.course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
				. "AND ciniki_fatt_course_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_fatt_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY ciniki_fatt_certs.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'certs', 'fname'=>'id', 'name'=>'item',
				'fields'=>array('id', 'name', 'link_id')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$rsp['certs'] = array();
		if( isset($rc['certs']) ) {
			$rsp['certs'] = $rc['certs'];
			foreach($rsp['certs'] as $cid => $item) {
				if( $item['item']['link_id'] > 0 ) {
					$rsp['course']['certs'] .= ($rsp['course']['certs']!=''?',':'') . $item['item']['id'];
				}
				unset($rsp['certs'][$cid]['item']['link_id']);
			}
		}
	}

	//
	// Get any messages about the course
	//
	if( isset($args['messages']) && $args['messages'] == 'yes' 
		&& ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x08) > 0 
		) {
		$strsql = "SELECT id, days, subject, message "
			. "FROM ciniki_fatt_messages "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND object = 'ciniki.fatt.course' "
			. "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
			. "ORDER BY days "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'messages', 'fname'=>'id', 'name'=>'message',
				'fields'=>array('id', 'days', 'subject', 'message')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['messages']) ) {
			$rsp['course']['messages'] = $rc['messages'];
		} else {
			$rsp['course']['messages'] = array();
		}
	}

	return $rsp;
}
?>
