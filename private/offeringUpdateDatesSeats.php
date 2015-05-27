<?php
//
// Description
// ===========
// This function calculates the number of seats remaining (open for registration) for an offering.
// This is based on checking the other offerings that share a date and location.
//
// The function finds all the other offerings in the same location and same date/time, then figures
// out how many actual seats are still remaining. The maximum number of seats available is based
// on the locations number of seats, the instructor/seat ratio and number of registrations of the overlapping courses.
//
// This function should be called after any change in the number of instructors, dates, locations or registrations.
// This ensures all the seat counts and numbers stay up to date.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the offering is attached to.
// 
function ciniki_fatt_offeringUpdateDatesSeats($ciniki, $business_id, $offering_id, $recurse='yes') {
	//
	// Get the time information for business and user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

	$seats_remaining = NULL;

	//
	// Keep track of updates to the offering
	//
	$offering_update_args = array();

	//
	// Get the number of registrations for the current offering
	//
	$strsql = "SELECT ciniki_fatt_offerings.id, "
		. "ciniki_fatt_offerings.course_id, "
		. "ciniki_fatt_offerings.start_date, "
		. "ciniki_fatt_offerings.date_string, "
		. "ciniki_fatt_offerings.location, "
		. "ciniki_fatt_offerings.max_seats, "
		. "ciniki_fatt_offerings.seats_remaining, "
		. "IFNULL(ciniki_fatt_courses.num_seats_per_instructor, 0) AS num_seats_per_instructor, "
		. "COUNT(ciniki_fatt_offering_instructors.id) AS num_instructors, "
		. "COUNT(ciniki_fatt_offering_registrations.id) AS num_registrations "
		. "FROM ciniki_fatt_offerings "
		. "LEFT JOIN ciniki_fatt_courses ON ("
			. "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
			. "AND ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_fatt_offering_instructors ON ("
			. "ciniki_fatt_offerings.id = ciniki_fatt_offering_instructors.offering_id "
			. "AND ciniki_fatt_offering_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "LEFT JOIN ciniki_fatt_offering_registrations ON ("
			. "ciniki_fatt_offerings.id = ciniki_fatt_offering_registrations.offering_id "
			. "AND ciniki_fatt_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_fatt_offerings.id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
		. "AND ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "GROUP BY ciniki_fatt_offerings.id "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
		array('container'=>'offerings', 'fname'=>'id',
			'fields'=>array('id', 'course_id', 'start_date', 'date_string', 'location', 
				'max_seats', 'seats_remaining', 'num_seats_per_instructor', 'num_instructors', 'num_registrations'),
			'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d H:i'))),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['offerings']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2343', 'msg'=>'Offering not found'));
	}
	$offering = array_pop($rc['offerings']);

	//
	// Start with calculating how many seats total based on number of instructors for the course.
	//
	if( $offering['num_seats_per_instructor'] > 0 ) {
		$max_instructor_seats = ($offering['num_seats_per_instructor'] * $offering['num_instructors']);
		$seats_remaining = $max_instructor_seats;
	}
	
	//
	// Keep track of how many registrations there are in the other courses for the same date/time/location
	//
	$other_num_registrations = 0;

	//
	// Get the dates for the current offering, and the location information
	//
	$strsql = "SELECT ciniki_fatt_offering_dates.id, "
		. "ciniki_fatt_offering_dates.start_date, "
		. "ciniki_fatt_offering_dates.num_hours, "
		. "ciniki_fatt_offering_dates.location_id, "
		. "IFNULL(ciniki_fatt_locations.name, '') AS location_name, "
		. "IFNULL(ciniki_fatt_locations.num_seats, 0) AS num_seats "
		. "FROM ciniki_fatt_offering_dates "
		. "LEFT JOIN ciniki_fatt_locations ON ("
			. "ciniki_fatt_offering_dates.location_id = ciniki_fatt_locations.id "
			. "AND ciniki_fatt_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_fatt_offering_dates.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
		. "AND ciniki_fatt_offering_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY ciniki_fatt_offering_dates.start_date "
		. "";
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
		array('container'=>'dates', 'fname'=>'id',
			'fields'=>array('id', 'start_date', 'num_hours', 'location_id', 'location_name', 'num_seats'),
			'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d H:i'))),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['dates']) || count($rc['dates']) == 0 ) {
		$offering_dates = array();
		$other_offerings = array();
	} else {
		$offering_dates = $rc['dates'];
		//
		// Get the other offerings and their number of registrations
		//
		$strsql = "SELECT ciniki_fatt_offering_dates.offering_id, "
			. "ciniki_fatt_offerings.max_seats, "
			. "COUNT(ciniki_fatt_offering_registrations.id) AS num_registrations "
			. "FROM ciniki_fatt_offering_dates "
			. "LEFT JOIN ciniki_fatt_offerings ON ("
				. "ciniki_fatt_offering_dates.offering_id = ciniki_fatt_offerings.id "
				. "AND ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "LEFT JOIN ciniki_fatt_offering_registrations ON ("
				. "ciniki_fatt_offerings.id = ciniki_fatt_offering_registrations.offering_id "
				. "AND ciniki_fatt_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_fatt_offering_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_fatt_offering_dates.offering_id <> '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
			. "";
		$strsql_dates = "";
		$max_location_seats = NULL;
		$date_string = '';
		$location = '';
		$first_date = NULL;
		$prev_date = NULL;
		$num_dates = 0;
		foreach($offering_dates as $date) {
			$num_dates++;
			//
			// Setup the date string
			//
			$cur_date = new DateTime($date['start_date']);
			if( $prev_date == NULL ) {
				$date_string = $cur_date->format('D M j');
			} else {
				if( $prev_date->format('M') == $cur_date->format('M') ) {
					$date_string .= ($date_string!=''?', ':'') . $cur_date->format('D j');
				} else {
					$date_string .= ($date_string!=''?', ':'') . $cur_date->format('D M j');
				}
			}
			$prev_date = clone $cur_date;
			if( $first_date == NULL ) { 
				$first_date = clone $cur_date;
			}

			//
			// Setup the location string
			//
			if( !preg_match('/' . $date['location_name'] . '/', $location) ) {
				$location .= ($location != ''?', ':'') . $date['location_name'];
			}
			//
			// Determine the lowest number of seats available at any location 
			// of the current offering.
			//
			if( $max_location_seats == NULL || ($date['num_seats'] > 0 && $date['num_seats'] < $max_location_seats) ) {
				$max_location_seats = $date['num_seats'];
			}

			//
			// Calculate the UTC start/end datetime for each date of the current offering.
			//
			$dts = new DateTime($date['start_date']);
			$dtsu = $dts->format('U');
			$dte = clone $dts;
//			if( preg_match('/^([0-9]+)\.([0-9]+)$/, $date['num_hours'], $matches) ) {
//				$dte = $dte->add(new DateInterval('PT' . $matches[1] . 'H' .  ($matches[2]*60). 'H'));
//			} else {
//				$dte = $dte->add(new DateInterval('PT' . $date['num_hours'] . 'H'));
				$dte = $dte->add(new DateInterval('PT' . ($date['num_hours']*3600) . 'S'));
//			}
			$dteu = $dte->format('U');

			//
			// Find the other offerings that start or end during this time at the same location.
			//
			$strsql_dates .= ($strsql_dates!=''?'OR ':'')
				. "("
				// Location must match
				. "ciniki_fatt_offering_dates.location_id = '" . ciniki_core_dbQuote($ciniki, $date['location_id'])  . "' "
				// Start date of current offering within start/end datetime of other offering
				. "AND ((unix_timestamp(ciniki_fatt_offering_dates.start_date) <= '" . ciniki_core_dbQuote($ciniki, $dtsu) . "' "
					. "AND unix_timestamp(ciniki_fatt_offering_dates.start_date)+(num_hours*3600) > '" . ciniki_core_dbQuote($ciniki, $dtsu) . "' "
					. ") "
				// end date of current offering withing start/end datetime of other offering
				. "OR (unix_timestamp(ciniki_fatt_offering_dates.start_date) < '" . ciniki_core_dbQuote($ciniki, $dteu) . "' "
					. "AND unix_timestamp(ciniki_fatt_offering_dates.start_date)+(num_hours*3600) >= '" . ciniki_core_dbQuote($ciniki, $dteu) . "' "
					. ")) "
				. ") ";
		}
		if( $strsql_dates != '' ) {
			$strsql .= "AND ($strsql_dates) ";
		}
		$strsql .= "GROUP BY ciniki_fatt_offering_dates.offering_id ";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'offerings', 'fname'=>'offering_id',
				'fields'=>array('id'=>'offering_id', 'max_seats', 'num_registrations')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['offerings']) ) {
			$other_offerings = $rc['offerings'];
		} else {
			$other_offerings = array();
		}

		//
		// Check if the maximum number of seats in the location is less than the seats remaining
		//
		if( $max_location_seats !== NULL && ($seats_remaining === NULL || $max_location_seats < $seats_remaining) ) {
			$seats_remaining = $max_location_seats;
		}

		//
		// Check the other overlapping offerings
		//
		foreach($other_offerings as $oid => $other_offering) {
			//
			// Check if the other offering has a lower instructor max that our current seats available
			//
			if( $other_offering['max_seats'] > 0 && ($seats_remaining === NULL || $other_offering['max_seats'] < $seats_remaining) ) {
				$seats_remaining = $other_offering['max_seats'];
			}

			//
			// Calculate how many seats should be remaining in the other offering, and how many registrations they have
			//
//			$other_offering_seats_remaining = $other_offering['max_seats'] - $other_offering['num_registrations'];
			$other_num_registrations += $other_offering['num_registrations'];
//			if( $other_offering_seats_remaining < $seats_remaining ) {
//				$seats_remaining = $other_offering_seats_remaining>0?$other_offering_seats_remaining:0;
//			}
		}

		if( $first_date != NULL ) {
			$date_string .= ($num_dates>1?' - ':', ') . $first_date->format('Y');
			if( $first_date->format('Y') != $cur_date->format('Y') ) {
				$date_string .= '/' . $cur_date->format('Y');
			}
		}
	}

	if( isset($first_date) && $first_date !== NULL && $first_date->format('Y-m-d H:i') != $offering['start_date'] ) {
		$first_date->setTimezone(new DateTimeZone('UTC'));
		$offering_update_args['start_date'] = $first_date->format('Y-m-d H:i');
	} elseif( !isset($first_date) && $offering['start_date'] != '0000-00-00 00:00' && $offering['start_date'] != '' ) {
		$offering_update_args['start_date'] = '0000-00-00 00:00';
	}
	if( isset($date_string) && $date_string != $offering['date_string'] ) {
		$offering_update_args['date_string'] = $date_string;
	} elseif( !isset($date_string) && $offering['date_string'] != '' ) {
		$offering_update_args['date_string'] = '';
	}
	if( isset($location) && $location != $offering['location'] ) {
		$offering_update_args['location'] = $location;
	} elseif( !isset($location) && $offering['location'] != '' ) {
		$offering_update_args['location'] = '';
	}

	//
	// Remove the number of registrations in this offering and other offerings
	//
	if( $seats_remaining !== NULL ) {
		$seats_remaining -= ($offering['num_registrations'] + $other_num_registrations);
	}

	//
	// Check if the number of seats remaining needs to be updated
	//
	if( $seats_remaining !== NULL && $seats_remaining != $offering['seats_remaining'] ) {
		$offering_update_args['seats_remaining'] = $seats_remaining;
	}

	//
	// Check if the max_seats needs to be updated
	//
	if( isset($max_instructor_seats) && isset($max_location_seats) && $max_location_seats !== NULL ) {
		$max_seats = $max_instructor_seats<$max_location_seats?$max_instructor_seats:$max_location_seats;
	} elseif( isset($max_instructor_seats) ) {
		$max_seats = $max_instructor_seats;
	} elseif( isset($max_location_seats) && $max_location_seats !== NULL ) {
		$max_seats = $max_location_seats;
	}
	if( isset($max_seats) && $max_seats != $offering['max_seats'] ) {
		$offering_update_args['max_seats'] = $max_seats;
	}

	//
	// Update the offering
	//
	if( count($offering_update_args) > 0 ) {
		$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.fatt.offering', $offering_id, $offering_update_args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2344', 'msg'=>'Unable to update offering', 'err'=>$rc['err']));
		}
	}

	//
	// If recurse is on, then update the seats of all other offerings that share the date
	//
	if( $recurse == 'yes' ) {
		foreach($other_offerings as $oid => $offering) {
			$rc = ciniki_fatt_offeringUpdateDatesSeats($ciniki, $business_id, $offering['id'], 'no');
			if( $rc['stat'] != 'ok' ) {
				return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2345', 'msg'=>'Unable to update offering', 'err'=>$rc['err']));
			}
		}
	}

	return array('stat'=>'ok');
}
?>