<?php
//
// Description
// -----------
// This function will return the appointment for a business
//
//
// Arguments
// ---------
// ciniki:
// business_id:			The ID of the business to get the details for.
// args:				The args passed through the API.
//
// Returns
// -------
//	</appointments>
//
function ciniki_fatt_hooks_appointmentSearch($ciniki, $business_id, $args) {
	//
	// Load date settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
	
	//
	// Setup the date range to find
	//
	if( isset($args['date']) && ($args['date'] == '' || $args['date'] == 'today') ) {
		$args['date'] = strftime("%Y-%m-%d");
	}
	$tz = new DateTimeZone($intl_timezone);
	$start_date = new DateTime($args['date'] . '00.00.00', $tz);
	$end_date = clone $start_date;
	$end_date->add(new DateInterval('P1D'));
	// Set to UTC for searching the database
	$start_date->setTimezone(new DateTimeZone('UTC'));
	$end_date->setTimezone(new DateTimeZone('UTC'));

	//
	// Get the list of offerings
	//
	$strsql = "SELECT ciniki_fatt_offerings.id, "
		. "ciniki_fatt_offerings.course_id, "
		. "ciniki_fatt_courses.code AS course_code, "
		. "ciniki_fatt_courses.name AS course_name, "
		. "ciniki_fatt_courses.num_seats_per_instructor AS instructor_seats, "
		. "ciniki_fatt_locations.name AS location_name, "
		. "ciniki_fatt_locations.num_seats AS location_seats, "
		. "ciniki_fatt_offering_dates.start_date AS start_date, "
		. "UNIX_TIMESTAMP(ciniki_fatt_offering_dates.start_date) AS start_ts, "
		. "ciniki_fatt_offering_dates.start_date AS date, "
		. "ciniki_fatt_offering_dates.start_date AS time, "
		. "ciniki_fatt_offering_dates.start_date AS 12hour, "
		. "(ciniki_fatt_offering_dates.num_hours*60) AS duration, "
		. "ciniki_fatt_offerings.date_string, "
		. "ciniki_fatt_offerings.location, "
		. "ciniki_fatt_offerings.max_seats, "
		. "ciniki_fatt_offerings.seats_remaining, "
		. "ciniki_fatt_instructors.name AS instructors, "
		. "COUNT(ciniki_fatt_offering_registrations.id) AS num_registrations "
		. "FROM ciniki_fatt_offering_dates "
		. "LEFT JOIN ciniki_fatt_offerings ON ("
			. "ciniki_fatt_offering_dates.offering_id = ciniki_fatt_offerings.id "
			. "AND ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_fatt_courses ON ("
			. "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
			. "AND ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_fatt_offering_instructors ON ("
			. "ciniki_fatt_offerings.id = ciniki_fatt_offering_instructors.offering_id "
			. "AND ciniki_fatt_offering_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_fatt_instructors ON ("
			. "ciniki_fatt_offering_instructors.instructor_id = ciniki_fatt_instructors.id "
			. "AND ciniki_fatt_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_fatt_locations ON ("
			. "ciniki_fatt_offering_dates.location_id = ciniki_fatt_locations.id "
			. "AND ciniki_fatt_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_fatt_offering_registrations ON ("
			. "ciniki_fatt_offerings.id = ciniki_fatt_offering_registrations.id "
			. "AND ciniki_fatt_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_fatt_offering_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//		. "AND ciniki_fatt_offering_dates.start_date >= '" . $start_date->format('Y-m-d H:i:s') . "' "
//		. "AND ciniki_fatt_offering_dates.start_date < '" . $end_date->format('Y-m-d H:i:s') . "' "
		. "";
	if( is_numeric($args['start_needle']) ) {
		$strsql .= "AND invoice_number LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "";
	} else {
		$strsql .= "AND ( ciniki_customers.first LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_customers.first LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_customers.last LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_customers.last LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_customers.company LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_customers.company LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_products.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_products.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' ) "
			. "OR DATE_FORMAT(bottling_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "";
	}
		. "GROUP BY ciniki_fatt_offerings.id "
		. "ORDER BY ciniki_fatt_offering_dates.start_date "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
		array('container'=>'appointments', 'fname'=>'id', 'name'=>'appointment', 
			'fields'=>array('id', 'subject'=>'course_name', 'start_date', 'start_ts', 'date', 'time', '12hour', 'duration', 
				'secondary_text'=>'location_name', 'max_seats', 'seats_remaining', 'instructors'),
			'dlists'=>array('instructors'=>', '),
			'utctotz'=>array(
				'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
				'date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d'),
				'time'=>array('timezone'=>$intl_timezone, 'format'=>'H:i'),
				'12hour'=>array('timezone'=>$intl_timezone, 'format'=>'h:i'),
				)),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['appointments']) ) {
		return array('stat'=>'ok', 'appointments'=>array());
	}
	$appointments = $rc['appointments'];

	//
	// Go through and cleanup the appointment
	//
	foreach($appointments as $aid => $appointment) {
		$appointments[$aid]['appointment']['allday'] = 'no';
		$appointments[$aid]['appointment']['repeat_type'] = '0';
		$appointments[$aid]['appointment']['repeat_interval'] = '1';
		$appointments[$aid]['appointment']['colour'] = '#ffcccc';
		$appointments[$aid]['appointment']['calendar'] = 'Courses';
		$appointments[$aid]['appointment']['module'] = 'ciniki.fatt';
//		if( $appointment['appointment']['location_name'] != '' ) {
//			$appointments[$aid]['appointment']['secondary_text'] = $appointment['appointment']['location_name'];
//		}
		if( $appointment['appointment']['seats_remaining'] < 0 ) {
			$appointments[$aid]['appointment']['secondary_text'] .= ($appointments[$aid]['appointment']['secondary_text']!=''?' - ':'') . abs($appointment['appointment']['seats_remaining']) . ' oversold';
		} elseif( $appointment['appointment']['seats_remaining'] == 0 ) {
			$appointments[$aid]['appointment']['secondary_text'] .= ($appointments[$aid]['appointment']['secondary_text']!=''?' - ':'') . 'Sold Out';
		} elseif( $appointment['appointment']['seats_remaining'] > 0 ) {
			$appointments[$aid]['appointment']['secondary_text'] .= ($appointments[$aid]['appointment']['secondary_text']!=''?' - ':'') . $appointment['appointment']['seats_remaining'] . ' left';
		}
		if( $appointment['appointment']['instructors'] != '' ) {
			$appointments[$aid]['appointment']['secondary_text'] .= ($appointments[$aid]['appointment']['secondary_text']!=''?' [':'') . $appointment['appointment']['instructors'] . ']';
		}
	}

	return array('stat'=>'ok', 'appointments'=>$appointments);;
}






	//
	// Grab the settings for the business from the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
	$rc =  ciniki_core_dbDetailsQuery($ciniki, 'ciniki_wineproduction_settings', 'business_id', $business_id, 'ciniki.wineproduction', 'settings', '');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$settings = $rc['settings'];

	if( !isset($args['start_needle']) || $args['start_needle'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2370', 'msg'=>'No search specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	$strsql = "SELECT ciniki_wineproductions.id AS order_id, "
		. "CONCAT_WS('-', UNIX_TIMESTAMP(ciniki_wineproductions.bottling_date), ciniki_wineproductions.customer_id) AS id, "
		. "ciniki_customers.display_name AS customer_name, "
		. "invoice_number, "
		. "ciniki_products.name AS wine_name, "
		//. "CONCAT_WS(' - ', CONCAT_WS(' ', first, last), IF(COUNT(invoice_number)>1, CONCAT('(',COUNT(invoice_number),')'), NULL), invoice_number, ciniki_products.name) AS subject, "
		. "UNIX_TIMESTAMP(bottling_date) AS start_ts, "
		. "DATE_FORMAT(bottling_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS start_date, "
		. "DATE_FORMAT(bottling_date, '%Y-%m-%d') AS date, "
		. "DATE_FORMAT(bottling_date, '%H:%i') AS time, "
		. "DATE_FORMAT(bottling_date, '%l:%i') AS 12hour, "
		. "'ciniki.wineproduction' AS module, "
		. "ciniki_wineproductions.bottling_flags, "
		. "ciniki_wineproductions.bottling_nocolour_flags, "
		. "ciniki_wineproductions.bottling_status, "
		. "ciniki_wineproductions.bottling_notes, "
		. "ciniki_wineproductions.status, "
		. "bottling_duration AS duration, '#aaddff' AS colour, 'ciniki.appointments' AS 'module' "
		. "FROM ciniki_wineproductions "
		. "JOIN ciniki_products ON (ciniki_wineproductions.product_id = ciniki_products.id "
			. "AND ciniki_products.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
		. "LEFT JOIN ciniki_customers ON (ciniki_wineproductions.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
		. "WHERE ciniki_wineproductions.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "";
	if( isset($args['full']) && $args['full'] == 'yes' ) {
		// search for orders including bottled
		$strsql .= "AND ciniki_wineproductions.status <= 60 ";
	} else {
		// Only search for active orders
		$strsql .= "AND ciniki_wineproductions.status < 60 ";
	}
	if( is_numeric($args['start_needle']) ) {
		$strsql .= "AND invoice_number LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "";
	} else {
		$strsql .= "AND ( ciniki_customers.first LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_customers.first LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_customers.last LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_customers.last LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_customers.company LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_customers.company LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_products.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_products.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' ) "
			. "OR DATE_FORMAT(bottling_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "";
	}
	//$strsql .= "GROUP BY CONCAT_WS('-', UNIX_TIMESTAMP(ciniki_wineproductions.bottling_date), ciniki_wineproductions.customer_id) ";
	if( isset($args['date']) && $args['date'] != '' ) {
		$strsql .= "ORDER BY ABS(DATEDIFF(DATE(ciniki_wineproductions.bottling_date), DATE('" . ciniki_core_dbQuote($ciniki, $args['date']) . "'))), ciniki_wineproductions.bottling_date, ciniki_wineproductions.customer_id, ciniki_products.name, id ";
	} else {
		$strsql .= "ORDER BY ABS(DATEDIFF(DATE(ciniki_wineproductions.bottling_date), DATE(NOW()))), ciniki_wineproductions.bottling_date, ciniki_wineproductions.customer_id, ciniki_products.name, id ";
	}
	// $strsql .= "ORDER BY ABS(DATEDIFF(ciniki_wineproductions.bottling_date, NOW())), ciniki_wineproductions.bottling_date, ciniki_wineproductions.customer_id, ciniki_products.name, id ";

	// 
	// Have to increase the limit because there are several wines per order, we want the limit the orders, not the wines.
	//
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']*5) . " ";	// is_numeric verified
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.wineproduction', array(
		array('container'=>'appointments', 'fname'=>'id', 'name'=>'appointment', 
			'fields'=>array('id', 'module', 'start_ts', 'start_date', 'date', 'time', '12hour', 'duration', 'wine_name'),
				'sums'=>array('duration'), 'countlists'=>array('wine_name'), 'limit'=>$args['limit']),
		array('container'=>'orders', 'fname'=>'order_id', 'name'=>'order', 'fields'=>array('order_id', 'customer_name', 'invoice_number', 'wine_name', 'duration', 'status', 'bottling_flags', 'bottling_nocolour_flags', 'bottling_status', 'bottling_notes')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['appointments']) ) {
		$appointments = $rc['appointments'];
	} else {
		$appointments = array();
	}

	//
	// Create subject, and remote orders to flatten appointments
	//
	foreach($appointments as $anum => $appointment) {
		if( $appointment['appointment']['time'] == '00:00' ) {
			$appointments[$anum]['appointment']['allday'] = 'yes';
		}
		$appointments[$anum]['appointment']['subject'] = $appointment['appointment']['orders'][0]['order']['customer_name'];
		if( count($appointment['appointment']['orders']) > 1 ) {
			$appointments[$anum]['appointment']['subject'] .= ' (' . count($appointment['appointment']['orders']) . ')';
		}
		$appointments[$anum]['appointment']['subject'] .= ' - ' . preg_replace('/-\s*[A-Z]/', '', $appointment['appointment']['orders'][0]['order']['invoice_number']);
		$appointments[$anum]['appointment']['subject'] .= ' - ' . $appointment['appointment']['wine_name'];
		$min_status = 255;
		$min_flags = 255;
		$min_order_status = 99;
		$bottling_notes = '';
		$appointments[$anum]['appointment']['secondary_text'] = '';
		$appointments[$anum]['appointment']['secondary_colour'] = '#ffffff';
		$scomma = '';
		$bottling_nocolour_flags = 0;
		foreach($appointments[$anum]['appointment']['orders'] as $onum => $order) {
			if( $order['order']['bottling_status'] < $min_status ) {
				$min_status = $order['order']['bottling_status'];
			}
			if( $order['order']['bottling_flags'] < $min_flags ) {
				$min_flags = $order['order']['bottling_flags'];
			}
			if( $order['order']['status'] < $min_order_status ) {
				$min_order_status = $order['order']['status'];
			}
			if( $bottling_notes == '' ) {
				$bottling_notes = $order['order']['bottling_notes'];
			}
			$bottling_nocolour_flags |= $order['order']['bottling_nocolour_flags'];
		}
		for($i=1;$i<=8;$i++) {
			if( isset($settings["bottling.nocolour.flags.$i.name"]) && $settings["bottling.nocolour.flags.$i.name"] != '' 
				&& ($order['order']['bottling_nocolour_flags']&pow(2, $i-1)) == pow(2,$i-1) ) {
				$appointments[$anum]['appointment']['secondary_colour_text'] = 'm';
				$appointments[$anum]['appointment']['secondary_text'] .= $scomma . $settings["bottling.nocolour.flags.$i.name"];
				$scomma = ', ';
			}
		}
		
		if( $min_status < 255 && isset($settings['bottling.status.' . (log($min_status, 2)+1) . '.name'])) {
			$appointments[$anum]['appointment']['secondary_text'] .= $scomma . $settings['bottling.status.' . (log($min_status, 2)+1) . '.name'];
			$appointments[$anum]['appointment']['colour'] = $settings['bottling.status.' . (log($min_status, 2)+1) . '.colour'];
			$scomma = ', ';
		}
		if( $min_flags < 255 && isset($settings['bottling.flags.' . (log($min_flags, 2)+1) . '.name']) ) {
			$appointments[$anum]['appointment']['secondary_text'] .= $scomma . $settings['bottling.flags.' . (log($min_flags, 2)+1) . '.name'];
			$appointments[$anum]['appointment']['secondary_colour'] = $settings['bottling.flags.' . (log($min_flags, 2)+1) . '.colour'];
		}
		if( $min_order_status == 60 ) {
			$appointments[$anum]['appointment']['colour'] = '#e4d8f9';
		}
		if( $bottling_notes != '' ) {
			$appointments[$anum]['appointment']['secondary_text'] .= $scomma . $bottling_notes;
		}
		
		unset($appointments[$anum]['appointment']['wine_name']);
		unset($appointments[$anum]['appointment']['orders']);
		unset($appointments[$anum]['appointment']['bottling_status']);
		$appointments[$anum]['appointment']['calendar'] = 'Bottling Schedule';
		$appointments[$anum]['appointment']['module'] = 'ciniki.wineproduction';
	}

	return array('stat'=>'ok', 'appointments'=>$appointments);;
}
?>
