<?php
//
// Description
// -----------
// This function will return the appointment for a tenant
//
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to get the details for.
// args:                The args passed through the API.
//
// Returns
// -------
//  </appointments>
//
function ciniki_fatt_hooks_appointmentSearch($ciniki, $tnid, $args) {
    //
    // Load date settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
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
            . "AND ciniki_fatt_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_courses ON ("
            . "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
            . "AND ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_offering_instructors ON ("
            . "ciniki_fatt_offerings.id = ciniki_fatt_offering_instructors.offering_id "
            . "AND ciniki_fatt_offering_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_instructors ON ("
            . "ciniki_fatt_offering_instructors.instructor_id = ciniki_fatt_instructors.id "
            . "AND ciniki_fatt_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_locations ON ("
            . "ciniki_fatt_offering_dates.location_id = ciniki_fatt_locations.id "
            . "AND ciniki_fatt_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_offering_registrations ON ("
            . "ciniki_fatt_offerings.id = ciniki_fatt_offering_registrations.id "
            . "AND ciniki_fatt_offering_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_fatt_offering_dates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
//      . "AND ciniki_fatt_offering_dates.start_date >= '" . $start_date->format('Y-m-d H:i:s') . "' "
//      . "AND ciniki_fatt_offering_dates.start_date < '" . $end_date->format('Y-m-d H:i:s') . "' "
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
//          . "OR ciniki_products.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//          . "OR ciniki_products.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//          . "OR DATE_FORMAT(bottling_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") ";
    }
    $strsql .= "GROUP BY ciniki_fatt_offerings.id "
        . "ORDER BY ciniki_fatt_offering_dates.start_date "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.fatt', array(
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
        $appointments[$aid]['allday'] = 'no';
        $appointments[$aid]['repeat_type'] = '0';
        $appointments[$aid]['repeat_interval'] = '1';
        $appointments[$aid]['colour'] = '#ffcccc';
        $appointments[$aid]['calendar'] = 'Courses';
        $appointments[$aid]['module'] = 'ciniki.fatt';
//      if( $appointment['location_name'] != '' ) {
//          $appointments[$aid]['secondary_text'] = $appointment['location_name'];
//      }
        if( $appointment['seats_remaining'] < 0 ) {
            $appointments[$aid]['secondary_text'] .= ($appointments[$aid]['secondary_text']!=''?' - ':'') . abs($appointment['seats_remaining']) . ' oversold';
        } elseif( $appointment['seats_remaining'] == 0 ) {
            $appointments[$aid]['secondary_text'] .= ($appointments[$aid]['secondary_text']!=''?' - ':'') . 'Sold Out';
        } elseif( $appointment['seats_remaining'] > 0 ) {
            $appointments[$aid]['secondary_text'] .= ($appointments[$aid]['secondary_text']!=''?' - ':'') . $appointment['seats_remaining'] . ' left';
        }
        if( $appointment['instructors'] != '' ) {
            $appointments[$aid]['secondary_text'] .= ($appointments[$aid]['secondary_text']!=''?' [':'') . $appointment['instructors'] . ']';
        }
    }

    return array('stat'=>'ok', 'appointments'=>$appointments);;
}
?>
