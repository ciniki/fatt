<?php
//
// Description
// -----------
// This function will return the menu items for the main menu.
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get events for.
//
// args:			The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_fatt_web_courseDetails(&$ciniki, $settings, $business_id, $permalink) {
	
	if( !isset($ciniki['business']['modules']['ciniki.fatt']) ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2639', 'msg'=>"I'm sorry, the file you requested does not exist."));
	}

	//
	// Load the course
	//
	$strsql = "SELECT ciniki_fatt_courses.id, "
		. "ciniki_fatt_courses.name, "
		. "ciniki_fatt_courses.code, "
		. "ciniki_fatt_courses.permalink, "
		. "ciniki_fatt_courses.status, "
		. "ciniki_fatt_courses.primary_image_id AS image_id, "
		. "ciniki_fatt_courses.synopsis, "
		. "ciniki_fatt_courses.description, "
		. "ciniki_fatt_courses.price, "
		. "ciniki_fatt_courses.taxtype_id, "
		. "ciniki_fatt_courses.num_days, "
		. "ciniki_fatt_courses.num_hours, "
		. "ciniki_fatt_courses.flags "
		. "FROM ciniki_fatt_courses "
		. "WHERE ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_fatt_courses.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'course');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['course']) ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2641', 'msg'=>"I'm sorry, the course you requested does not exist."));
	}
	$course = $rc['course'];

	//
	// Load the upcoming classes
	//
	$strsql = "SELECT ciniki_fatt_offerings.id, "
		. "ciniki_fatt_offerings.permalink, "
		. "ciniki_fatt_offerings.price AS unit_amount, "
		. "ciniki_fatt_offerings.flags, "
		. "ciniki_fatt_offerings.start_date, "
		. "ciniki_fatt_offerings.date_string, "
		. "ciniki_fatt_offerings.location, "
		. "ciniki_fatt_offerings.city, "
		. "ciniki_fatt_offerings.seats_remaining "
		. "FROM ciniki_fatt_offerings "
		. "WHERE ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_fatt_offerings.course_id = '" . ciniki_core_dbQuote($ciniki, $course['id']) . "' "
		. "AND (ciniki_fatt_offerings.flags&0x01) = 0x01 "
		. "ORDER BY ciniki_fatt_offerings.start_date "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'offering');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['rows']) ) {
		$course['offerings'] = array();
	} else {
		$course['offerings'] = $rc['rows'];
	}

	return array('stat'=>'ok', 'course'=>$course);
}
?>
