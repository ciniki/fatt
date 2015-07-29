<?php
//
// Description
// ===========
// This function will load an offering and all associated data
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business the offering is attached to.
// offering_id:		The ID of the offering to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_offeringLoad($ciniki, $business_id, $offering_id) {
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
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	date_default_timezone_set($intl_timezone);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

	//
	// Load the status maps for the text description of each status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'maps');
	$rc = ciniki_fatt_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	$rsp = array('stat'=>'ok');

	$strsql = "SELECT ciniki_fatt_offerings.id, "
		. "ciniki_fatt_offerings.course_id, "
		. "IFNULL(ciniki_fatt_courses.name, '') AS course_name, "
		. "ciniki_fatt_offerings.permalink, "
		. "ciniki_fatt_offerings.price, "
		. "ciniki_fatt_offerings.flags, "
		. "ciniki_fatt_offerings.flags AS flags_display, "
		. "ciniki_fatt_offerings.date_string, "
		. "ciniki_fatt_offerings.location, "
		. "ciniki_fatt_offerings.max_seats, "
		. "ciniki_fatt_offerings.seats_remaining "
		. "FROM ciniki_fatt_offerings "
		. "LEFT JOIN ciniki_fatt_courses ON ("
			. "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
			. "AND ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_fatt_offerings.id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.offerings', array(
		array('container'=>'offerings', 'fname'=>'id', 'name'=>'offering',
			'fields'=>array('id', 'course_id', 'course_name', 'permalink', 'price', 'flags', 'flags_display',
				'date_string', 'location', 'max_seats', 'seats_remaining'),
			'flags'=>array('flags_display'=>$maps['offering']['flags']),
			),
	));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['offerings']) || !isset($rc['offerings'][0]) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2406', 'msg'=>'Unable to find offering'));
	}
	$rsp['offering'] = $rc['offerings'][0]['offering'];
	$rsp['offering']['price'] = numfmt_format_currency($intl_currency_fmt, $rsp['offering']['price'], $intl_currency);

	//
	// Get the instructors for the course
	//
	$strsql = "SELECT ciniki_fatt_offering_instructors.id, "
		. "ciniki_fatt_offering_instructors.instructor_id, "
		. "ciniki_fatt_instructors.name, "
		. "ciniki_fatt_instructors.email, "
		. "ciniki_fatt_instructors.phone "
		. "FROM ciniki_fatt_offering_instructors, ciniki_fatt_instructors "
		. "WHERE ciniki_fatt_offering_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_fatt_offering_instructors.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
		. "AND ciniki_fatt_offering_instructors.instructor_id = ciniki_fatt_instructors.id "
		. "AND ciniki_fatt_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.offerings', array(
		array('container'=>'instructors', 'fname'=>'id', 'name'=>'instructor',
			'fields'=>array('id'=>'instructor_id', 'name', 'email', 'phone')),
	));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['instructors']) ) {
		$rsp['offering']['instructors'] = $rc['instructors'];
	} else {
		$rsp['offering']['instructors'] = array();
	}

	//
	// Get the registrations for a course
	//
	$strsql = "SELECT ciniki_fatt_offering_registrations.id, "
		. "ciniki_fatt_offering_registrations.customer_id, "
		. "ciniki_fatt_offering_registrations.student_id, "
		. "ciniki_fatt_offering_registrations.invoice_id, "
		. "ciniki_fatt_offering_registrations.status, "
		. "ciniki_fatt_offering_registrations.customer_notes, "
		. "ciniki_fatt_offering_registrations.notes, "
		. "IFNULL(c1.display_name, '') AS customer_display_name, "
		. "IFNULL(c2.display_name, '') AS student_display_name "
		. "FROM ciniki_fatt_offering_registrations "
		. "LEFT JOIN ciniki_customers AS c1 ON ("
			. "ciniki_fatt_offering_registrations.customer_id = c1.id "
			. "AND c1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_customers AS c2 ON ("
			. "ciniki_fatt_offering_registrations.student_id = c2.id "
			. "AND c2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_fatt_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_fatt_offering_registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
		. "ORDER BY student_display_name, customer_display_name "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.offerings', array(
		array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
			'fields'=>array('id', 'invoice_id', 'status',
				'customer_id', 'customer_display_name',
				'student_id', 'student_display_name',
				'customer_notes', 'notes')),
	));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['registrations']) ) {
		$rsp['offering']['registrations'] = $rc['registrations'];
		//
		// Get the invoice status for each registration
		//
		$invoice_ids = array();
		foreach($rc['registrations'] as $rid => $reg) {
			$invoice_ids[$reg['registration']['invoice_id']] = $rid;
		}
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceStatus');
		$rc = ciniki_sapos_hooks_invoiceStatus($ciniki, $business_id, array('invoice_ids'=>array_keys($invoice_ids)));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['invoices']) ) {
			foreach($rsp['offering']['registrations'] as $rid => $registration) {
				if( isset($rc['invoices'][$registration['registration']['invoice_id']]) ) {
					$rsp['offering']['registrations'][$rid]['registration']['invoice_status'] = $rc['invoices'][$registration['registration']['invoice_id']]['status_text'];
				}
			}
		}

	} else {
		$rsp['offering']['registrations'] = array();
	}
	
	//
	// Get the dates for this offering
	//
	$strsql = "SELECT ciniki_fatt_offering_dates.id, "
		. "ciniki_fatt_offering_dates.day_number, "
		. "ciniki_fatt_offering_dates.start_date, "
		. "ciniki_fatt_offering_dates.num_hours, "
		. "ciniki_fatt_offering_dates.location_id, "
		. "ciniki_fatt_locations.flags AS location_flags, "
		. "IFNULL(ciniki_fatt_locations.name, 'Unknown') AS location_name, "
		. "ciniki_fatt_offering_dates.address1, "
		. "ciniki_fatt_offering_dates.address2, "
		. "ciniki_fatt_offering_dates.city, "
		. "ciniki_fatt_offering_dates.province, "
		. "ciniki_fatt_offering_dates.postal, "
		. "ciniki_fatt_offering_dates.latitude, "
		. "ciniki_fatt_offering_dates.longitude "
		. "FROM ciniki_fatt_offering_dates "
		. "LEFT JOIN ciniki_fatt_locations ON ("
			. "ciniki_fatt_offering_dates.location_id = ciniki_fatt_locations.id "
			. "AND ciniki_fatt_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_fatt_offering_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_fatt_offering_dates.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
		. "ORDER BY ciniki_fatt_offering_dates.start_date, day_number "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.offerings', array(
		array('container'=>'dates', 'fname'=>'id', 'name'=>'date',
			'fields'=>array('id', 'day_number', 'start_date', 'num_hours', 'location_id', 'location_name', 'location_flags', 
				'address1', 'address2', 'city', 'postal', 'latitude', 'longitude'),
			'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format)),
			),
	));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['dates']) ) {
		$rsp['offering']['dates'] = $rc['dates'];
		foreach($rsp['offering']['dates'] as $did => $date) {
			$rsp['offering']['dates'][$did]['date']['num_hours'] = (float)$date['date']['num_hours'];
		}
	}

	return $rsp;
}
?>
