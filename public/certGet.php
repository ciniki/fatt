<?php
//
// Description
// ===========
// This method will return all the information about a cert.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the cert is attached to.
// cert_id:		The ID of the cert to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_certGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'cert_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Certification'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.certGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

	if( $args['cert_id'] == 0 ) {	
		//
		// Return the default settings for a new cert
		//
		$rsp = array('stat'=>'ok', 'cert'=>array(
			'name'=>'',
			'grouping'=>'',
			'status'=>'10',
			'years_valid'=>'',
			));
	} else {
		//
		// Get the cert details
		//
		$strsql = "SELECT ciniki_fatt_certs.id, "
			. "ciniki_fatt_certs.name, "
			. "ciniki_fatt_certs.grouping, "
			. "ciniki_fatt_certs.status, "
			. "ciniki_fatt_certs.years_valid "
			. "FROM ciniki_fatt_certs "
			. "WHERE ciniki_fatt_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_fatt_certs.id = '" . ciniki_core_dbQuote($ciniki, $args['cert_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.certs', array(
			array('container'=>'certs', 'fname'=>'id', 'name'=>'cert',
				'fields'=>array('id', 'name', 'grouping', 'status', 'years_valid')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['certs']) || !isset($rc['certs'][0]) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2307', 'msg'=>'Unable to find cert'));
		}
		$rsp = array('stat'=>'ok', 'cert'=>$rc['certs'][0]['cert']);
	}

	//
	// Get the courses for the certs and the business
	//
	$rsp['cert']['courses'] = '';
	$strsql = "SELECT ciniki_fatt_courses.id, "
		. "ciniki_fatt_courses.name, "
		. "IFNULL(ciniki_fatt_course_certs.id, 0) AS link_id "
		. "FROM ciniki_fatt_courses "
		. "LEFT JOIN ciniki_fatt_course_certs ON ("
			. "ciniki_fatt_courses.id = ciniki_fatt_course_certs.course_id "
			. "AND ciniki_fatt_course_certs.cert_id = '" . ciniki_core_dbQuote($ciniki, $args['cert_id']) . "' "
			. "AND ciniki_fatt_course_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY ciniki_fatt_courses.name "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
		array('container'=>'courses', 'fname'=>'id', 'name'=>'item',
			'fields'=>array('id', 'name', 'link_id')),
	));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$rsp['courses'] = array();
	if( isset($rc['courses']) ) {
		$rsp['courses'] = $rc['courses'];
		foreach($rsp['courses'] as $cid => $item) {
			if( $item['item']['link_id'] > 0 ) {
				$rsp['cert']['courses'] .= ($rsp['cert']['courses']!=''?',':'') . $item['item']['id'];
			}
			unset($rsp['courses'][$cid]['item']['link_id']);
		}
	}

	//
	// Get any messages about the cert
	//
	if( isset($args['messages']) && $args['messages'] == 'yes' 
		&& ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x20) > 0 
		) {
		$strsql = "SELECT id, days, subject, message "
			. "FROM ciniki_fatt_messages "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND object = 'ciniki.fatt.cert' "
			. "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['cert_id']) . "' "
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
			$rsp['cert']['messages'] = $rc['messages'];
		} else {
			$rsp['cert']['messages'] = array();
		}
	}

	return $rsp;
}
?>
