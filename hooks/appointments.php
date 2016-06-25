<?php
//
// Description
// -----------
// This function will return a bottling schedule for a day
//
//
// Arguments
// ---------
// ciniki:
// business_id:         The ID of the business to get the appointments for.
// args:                The args passed from the API.
//
// Returns
// -------
//  <appointments>
//      <appointment module="ciniki.fatt" customer_name="" invoice_number="" wine_name="" />
//  </appointments>
//
function ciniki_fatt_hooks_appointments($ciniki, $business_id, $args) {
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
    if( isset($args['start_date']) && isset($args['end_date']) ) {
        $start_date = new DateTime($args['start_date'], new DateTimeZone('UTC'));
        $end_date = new DateTime($args['end_date'], new DateTimeZone('UTC'));
    } else {
        $start_date = new DateTime($args['date'] . ' 00:00:00', $tz);
        $end_date = clone $start_date;
        $end_date->add(new DateInterval('P1D'));
    }

    // Set to UTC for searching the database
    $start_date->setTimezone(new DateTimeZone('UTC'));
    $end_date->setTimezone(new DateTimeZone('UTC'));

    $start_date_ts = $start_date->format('U');
    $end_date_ts = $end_date->format('U');

    //
    // Get the list of offerings
    //
    $strsql = "SELECT ciniki_fatt_offerings.id, "
        . "CONCAT_WS('-', UNIX_TIMESTAMP(ciniki_fatt_offering_dates.start_date), ciniki_fatt_locations.id) AS appointment_id, "
        . "ciniki_fatt_offerings.course_id, "
        . "ciniki_fatt_courses.code AS course_codes, "
        . "ciniki_fatt_courses.name AS course_name, "
        . "ciniki_fatt_courses.num_seats_per_instructor AS instructor_seats, "
        . "ciniki_fatt_locations.code AS location_codes, "
        . "ciniki_fatt_locations.name AS location_name, "
        . "ciniki_fatt_locations.num_seats AS location_seats, "
        . "ciniki_fatt_locations.colour AS location_colour, "
        . "ciniki_fatt_offering_dates.start_date AS start_date, "
        . "ciniki_fatt_offering_dates.start_date as start_ts, "
        . "ciniki_fatt_offering_dates.start_date AS date, "
        . "ciniki_fatt_offering_dates.start_date AS time, "
        . "ciniki_fatt_offering_dates.start_date AS 12hour, "
        . "(ciniki_fatt_offering_dates.num_hours*60) AS duration, "
        . "ciniki_fatt_offerings.date_string, "
        . "ciniki_fatt_offerings.location, "
        . "ciniki_fatt_offerings.max_seats, "
        . "ciniki_fatt_offerings.seats_remaining, "
        . "ciniki_fatt_offerings.num_registrations, "
        . "ciniki_fatt_instructors.initials AS instructor_codes, "
        . "ciniki_fatt_instructors.name AS instructors "
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
        . "WHERE ciniki_fatt_offering_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_fatt_offering_dates.start_date >= '" . $start_date->format('Y-m-d H:i:s') . "' "
        . "AND ciniki_fatt_offering_dates.start_date < '" . $end_date->format('Y-m-d H:i:s') . "' "
        . "ORDER BY ciniki_fatt_offering_dates.start_date, ciniki_fatt_locations.code, ciniki_fatt_courses.code "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'appointments', 'fname'=>'appointment_id', 'name'=>'appointment', 
            'fields'=>array('id'=>'appointment_id', 'course_codes', 'start_date', 'start_ts', 'date', 'time', '12hour', 'duration', 
                'secondary_text'=>'location_name', 'location_codes', 'colour'=>'location_colour', 'max_seats', 'seats_remaining', 'instructors', 'instructor_codes'),
            'dlists'=>array('course_codes'=>'/', 'location_codes'=>',', 'instructors'=>', ', 'instructor_codes'=>','),
            'utctotz'=>array('start_ts'=>array('timezone'=>$intl_timezone, 'format'=>'U'),
                'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d'),
                'time'=>array('timezone'=>$intl_timezone, 'format'=>'H:i'),
                '12hour'=>array('timezone'=>$intl_timezone, 'format'=>'h:i'),
                )),
        array('container'=>'offerings', 'fname'=>'id', 'name'=>'offering',
            'fields'=>array('id', 'course_codes', 'location_codes', 'duration', 'instructor_codes', 'instructors', 'max_seats', 'seats_remaining', 'num_registrations')),
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
        $num_registrations = 0;
        if( isset($appointment['appointment']['offerings']) ) { 
            $max_seats = 9999;
            $seats_remaining = 9999;
            $duration = 0;
            foreach($appointment['appointment']['offerings'] as $oid => $offering) {
                if( $offering['offering']['duration'] > $duration ) {
                    $duration = $offering['offering']['duration'];
                }
                if( $offering['offering']['max_seats'] > 0 && $offering['offering']['max_seats'] < $max_seats ) {
                    $max_seats = $offering['offering']['max_seats'];
                }
                if( $offering['offering']['seats_remaining'] < $seats_remaining ) {
                    $seats_remaining = $offering['offering']['seats_remaining'];
                }
                $num_registrations += $offering['offering']['num_registrations'];
            }
            if( $seats_remaining < 9999 ) {
                $appointments[$aid]['appointment']['seats_remaining'] = $seats_remaining;
            }
            if( $max_seats < 9999 ) {
                $appointments[$aid]['appointment']['seats_remaining'] = $seats_remaining;
            }
            if( $duration > 0 ) {
                $appointments[$aid]['appointment']['duration'] = $duration;
            }
        }
        $appointments[$aid]['appointment']['instructors'] = implode(', ', array_unique(explode(', ', $appointment['appointment']['instructors'])));
        $appointments[$aid]['appointment']['instructor_codes'] = implode(',', array_unique(explode(',', $appointment['appointment']['instructor_codes'])));
        $appointments[$aid]['appointment']['allday'] = 'no';
        $appointments[$aid]['appointment']['repeat_type'] = '0';
        $appointments[$aid]['appointment']['repeat_interval'] = '1';
        if( $appointments[$aid]['appointment']['colour'] == '' ) {
            $appointments[$aid]['appointment']['colour'] = '#ffcccc';
        }
        $appointments[$aid]['appointment']['calendar'] = 'Courses';
//      $appointments[$aid]['appointment']['module'] = 'ciniki.fatt';
        $appointments[$aid]['appointment']['app'] = 'ciniki.fatt.offerings';
//      if( $appointment['appointment']['location_name'] != '' ) {
//          $appointments[$aid]['appointment']['secondary_text'] = $appointment['appointment']['location_name'];
//      }
        $appointments[$aid]['appointment']['subject'] = $appointments[$aid]['appointment']['course_codes'];
        $appointments[$aid]['appointment']['abbr_secondary_text'] = '';
        if( $appointment['appointment']['seats_remaining'] < 0 ) {
            $appointments[$aid]['appointment']['secondary_text'] .= ($appointments[$aid]['appointment']['secondary_text']!=''?' - ':'') . abs($appointments[$aid]['appointment']['seats_remaining']) . ' oversold';
            $appointments[$aid]['appointment']['abbr_secondary_text'] .= ($appointments[$aid]['appointment']['abbr_secondary_text']!=''?' - ':'') . abs($appointments[$aid]['appointment']['seats_remaining']) . ' oversold';
        } elseif( $appointment['appointment']['seats_remaining'] == 0 ) {
            $appointments[$aid]['appointment']['secondary_text'] .= ($appointments[$aid]['appointment']['secondary_text']!=''?' - ':'') . 'Sold Out';
            $appointments[$aid]['appointment']['abbr_secondary_text'] .= ($appointments[$aid]['appointment']['abbr_secondary_text']!=''?' - ':'') . 'Sold Out';
        } elseif( $appointment['appointment']['seats_remaining'] > 0 ) {
            $appointments[$aid]['appointment']['secondary_text'] .= ($appointments[$aid]['appointment']['secondary_text']!=''?' - ':'') . $appointments[$aid]['appointment']['seats_remaining'] . ' left';
            $appointments[$aid]['appointment']['abbr_secondary_text'] .= ($appointments[$aid]['appointment']['abbr_secondary_text']!=''?' - ':'') . $appointments[$aid]['appointment']['seats_remaining'] . ' left';
        }
        if( $appointment['appointment']['instructors'] != '' ) {
            $appointments[$aid]['appointment']['secondary_text'] .= ($appointments[$aid]['appointment']['secondary_text']!=''?' [':'') . $appointments[$aid]['appointment']['instructors'] . ']';
        }
        //
        // Setup the abbreviated subject and secondary_text
        //
        $appointments[$aid]['appointment']['abbr_subject'] = $appointments[$aid]['appointment']['location_codes'] . ":" . $appointments[$aid]['appointment']['instructor_codes'] . ":" . $appointments[$aid]['appointment']['course_codes'];
        $appointments[$aid]['appointment']['abbr_secondary_text'] .= ' [' . $num_registrations . '/' . $max_seats . ']';
    }

    //
    // Convert the start/end dates to local timezone, as expirations are stored as local dates not UTC datetimes.
    //
    $start_date->setTimezone(new DateTimeZone($intl_timezone));
    $end_date->setTimezone(new DateTimeZone($intl_timezone));

    //
    // Get the list of aed expirations
    //
    $strsql = "SELECT ciniki_fatt_aeds.id, "
        . "ciniki_fatt_aeds.customer_id, "
        . "IFNULL(ciniki_customers.display_name, 'Unregistered') AS display_name, "
        . "ciniki_fatt_aeds.location, "
        . "ciniki_fatt_aeds.status, "
        . "ciniki_fatt_aeds.flags, "
        . "ciniki_fatt_aeds.make, "
        . "ciniki_fatt_aeds.model, "
        . "ciniki_fatt_aeds.serial, "
        . "UNIX_TIMESTAMP(ciniki_fatt_aeds.device_expiration) AS device_expiration, "
        . "UNIX_TIMESTAMP(ciniki_fatt_aeds.primary_battery_expiration) AS primary_battery_expiration, "
        . "UNIX_TIMESTAMP(ciniki_fatt_aeds.secondary_battery_expiration) AS secondary_battery_expiration, "
        . "UNIX_TIMESTAMP(ciniki_fatt_aeds.primary_adult_pads_expiration) AS primary_adult_pads_expiration, "
        . "UNIX_TIMESTAMP(ciniki_fatt_aeds.secondary_adult_pads_expiration) AS secondary_adult_pads_expiration, "
        . "UNIX_TIMESTAMP(ciniki_fatt_aeds.primary_child_pads_expiration) AS primary_child_pads_expiration, "
        . "UNIX_TIMESTAMP(ciniki_fatt_aeds.secondary_child_pads_expiration) AS secondary_child_pads_expiration "
        . "FROM ciniki_fatt_aeds "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_fatt_aeds.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_fatt_aeds.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ("
            . "(ciniki_fatt_aeds.device_expiration >= '" . $start_date->format('Y-m-d') . "' "
                . "AND ciniki_fatt_aeds.device_expiration < '" . $end_date->format('Y-m-d') . "' "
                . ") "
            . "OR (ciniki_fatt_aeds.primary_battery_expiration >= '" . $start_date->format('Y-m-d') . "' "
                . "AND ciniki_fatt_aeds.primary_battery_expiration < '" . $end_date->format('Y-m-d') . "' "
                . ") "
            . "OR (ciniki_fatt_aeds.secondary_battery_expiration >= '" . $start_date->format('Y-m-d') . "' "
                . "AND ciniki_fatt_aeds.secondary_battery_expiration < '" . $end_date->format('Y-m-d') . "' "
                . ") "
            . "OR (ciniki_fatt_aeds.primary_adult_pads_expiration >= '" . $start_date->format('Y-m-d') . "' "
                . "AND ciniki_fatt_aeds.primary_adult_pads_expiration < '" . $end_date->format('Y-m-d') . "' "
                . ") "
            . "OR (ciniki_fatt_aeds.secondary_adult_pads_expiration >= '" . $start_date->format('Y-m-d') . "' "
                . "AND ciniki_fatt_aeds.secondary_adult_pads_expiration < '" . $end_date->format('Y-m-d') . "' "
                . ") "
            . "OR (ciniki_fatt_aeds.primary_child_pads_expiration >= '" . $start_date->format('Y-m-d') . "' "
                . "AND ciniki_fatt_aeds.primary_child_pads_expiration < '" . $end_date->format('Y-m-d') . "' "
                . ") "
            . "OR (ciniki_fatt_aeds.secondary_child_pads_expiration >= '" . $start_date->format('Y-m-d') . "' "
                . "AND ciniki_fatt_aeds.secondary_child_pads_expiration < '" . $end_date->format('Y-m-d') . "' "
                . ") "
            . ") "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('customer_id', 'display_name')),
        array('container'=>'aeds', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name', 'location', 'status', 'flags', 'make', 'model', 'serial', 
                'device_expiration', 'primary_battery_expiration', 'secondary_battery_expiration',
                'primary_adult_pads_expiration', 'secondary_adult_pads_expiration', 'primary_child_pads_expiration', 'secondary_child_pads_expiration', 
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['customers']) ) {
        $customers = $rc['customers'];
        foreach($customers as $cid => $customer) {
            $lowest_expiration_ts = $end_date_ts;
            $expiring_pieces = '';
            if( isset($customer['aeds']) ) {
                foreach($customer['aeds'] as $aid => $aed) {
                    if( $aed['device_expiration'] >= $start_date_ts && $aed['device_expiration'] <= $lowest_expiration_ts ) {
                        $lowest_expiration_ts = $aed['device_expiration'];
                        if( strstr($expiring_pieces, 'device') === false ) {
                            $expiring_pieces .= ($expiring_pieces != '' ? ', ' : '') . 'device';
                        }
                    }
                    if( $aed['primary_battery_expiration'] >= $start_date_ts && $aed['primary_battery_expiration'] <= $lowest_expiration_ts ) {
                        if( $aed['primary_battery_expiration'] < $lowest_expiration_ts ) {
                            $expiring_pieces = 'battery';
                        } elseif( strstr($expiring_pieces, 'battery') === false ) {
                            $expiring_pieces .= ($expiring_pieces != '' ? ', ' : '') . 'battery';
                        }
                        $lowest_expiration_ts = $aed['primary_battery_expiration'];
                    }
                    if( $aed['secondary_battery_expiration'] >= $start_date_ts && $aed['secondary_battery_expiration'] <= $lowest_expiration_ts ) {
                        if( $aed['secondary_battery_expiration'] < $lowest_expiration_ts ) {
                            $expiring_pieces = 'battery';
                        } elseif( strstr($expiring_pieces, 'battery') === false ) {
                            $expiring_pieces .= ($expiring_pieces != '' ? ', ' : '') . 'battery';
                        }
                        $lowest_expiration_ts = $aed['secondary_battery_expiration'];
                    }
                    if( $aed['primary_adult_pads_expiration'] >= $start_date_ts && $aed['primary_adult_pads_expiration'] <= $lowest_expiration_ts ) {
                        if( $aed['primary_adult_pads_expiration'] < $lowest_expiration_ts ) {
                            $expiring_pieces = 'pads';
                        } elseif( strstr($expiring_pieces, 'pads') === false ) {
                            $expiring_pieces .= ($expiring_pieces != '' ? ', ' : '') . 'pads';
                        }
                        $lowest_expiration_ts = $aed['primary_adult_pads_expiration'];
                    }
                    if( $aed['secondary_adult_pads_expiration'] >= $start_date_ts && $aed['secondary_adult_pads_expiration'] <= $lowest_expiration_ts ) {
                        if( $aed['secondary_adult_pads_expiration'] < $lowest_expiration_ts ) {
                            $expiring_pieces = 'pads';
                        } elseif( strstr($expiring_pieces, 'pads') === false ) {
                            $expiring_pieces .= ($expiring_pieces != '' ? ', ' : '') . 'pads';
                        }
                        $lowest_expiration_ts = $aed['secondary_adult_pads_expiration'];
                    }
                    if( $aed['primary_child_pads_expiration'] >= $start_date_ts && $aed['primary_child_pads_expiration'] <= $lowest_expiration_ts ) {
                        if( $aed['primary_child_pads_expiration'] < $lowest_expiration_ts ) {
                            $expiring_pieces = 'pads';
                        } elseif( strstr($expiring_pieces, 'pads') === false ) {
                            $expiring_pieces .= ($expiring_pieces != '' ? ', ' : '') . 'pads';
                        }
                        $lowest_expiration_ts = $aed['primary_child_pads_expiration'];
                    }
                    if( $aed['secondary_child_pads_expiration'] >= $start_date_ts && $aed['secondary_child_pads_expiration'] <= $lowest_expiration_ts ) {
                        if( $aed['secondary_child_pads_expiration'] < $lowest_expiration_ts ) {
                            $expiring_pieces = 'pads';
                        } elseif( strstr($expiring_pieces, 'pads') === false ) {
                            $expiring_pieces .= ($expiring_pieces != '' ? ', ' : '') . 'pads';
                        }
                        $lowest_expiration_ts = $aed['secondary_child_pads_expiration'];
                    }
                }
            }
            $dt = new DateTime('@' . $lowest_expiration_ts, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone($intl_timezone));
            $dt->setTime(0,0,0);
            $appointment = array(
                'id'=>'aedcustomer-' . $customer['customer_id'],
                'calendar'=>'AEDs',
                'app'=>'ciniki.fatt.aeds',
                'subject'=>$customer['display_name'],
                'secondary_text'=>$expiring_pieces,
                'colour'=>'#ffcccc',
                'allday'=>'yes',
                'start_ts'=>$dt->format('U'), //$lowest_expiration_ts,
                'start_date'=>$dt->format($datetime_format),
                'date'=>$dt->format('Y-m-d'),
                'time'=>'00:00', //$dt->format('H:i'),
                '12hour'=>'00:00', //$dt->format('h:i'),
                );
            $appointments[] = array('appointment'=>$appointment);
        }
    }

    return array('stat'=>'ok', 'appointments'=>$appointments);;
}
?>
