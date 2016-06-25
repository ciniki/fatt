<?php
//
// Description
// ===========
// This method will return a list of invoices.
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_fatt_offeringList(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'year'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Year'), 
        'month'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Month'), 
        'years'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Years'), 
//        'course_ids'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Courses'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.offeringList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'maps');
    $rc = ciniki_fatt_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of years 
    //
    $rsp = array('stat'=>'ok');
    if( isset($args['years']) && $args['years'] == 'yes' ) {
        $strsql = "SELECT DISTINCT 'list' AS id, DATE_FORMAT(start_date, '%Y') AS years "
            . "FROM ciniki_fatt_offerings "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND start_date <> '0000-00-00 00:00:00' "
            . "ORDER BY years DESC ";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'years', 'fname'=>'id', 
                'fields'=>array('years'), 'dlists'=>array('years'=>',')),
            ));
        if( $rc['stat'] != 'ok') {
            return $rc;
        }
        if( isset($rc['years']['list']) ) {
            $rsp['years'] = $rc['years']['list']['years'];
        }
        //
        // Check for any offerings with no dates attached
        //
        $strsql = "SELECT COUNT(id) AS num "
            . "FROM ciniki_fatt_offerings "
            . "WHERE ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_fatt_offerings.date_string = '' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'nodate');
        if( $rc['stat'] != 'ok') {
            return $rc;
        }
        if( isset($rc['nodate']['num']) && $rc['nodate']['num'] > 0 ) {
            if( !isset($rsp['years']) ) {
                $rsp['years'] = '????';
            } else {
                $rsp['years'] .= ($rsp['years']!=''?',':'') . '????';
            }
        }
    }

    if( isset($args['year']) && $args['year'] != '' && $args['year'] != '????' ) {
        //
        // Set the start and end date for the business timezone, then convert to UTC
        //
        $tz = new DateTimeZone($intl_timezone);
        if( isset($args['month']) && $args['month'] != '' && $args['month'] > 0 ) {
            $start_date = new DateTime($args['year'] . '-' . $args['month'] . '-01 00.00.00', $tz);
            $end_date = clone $start_date;
            // Find the end of the month
            $end_date->add(new DateInterval('P1M'));
        } else {
            $start_date = new DateTime($args['year'] . '-01-01 00.00.00', $tz);
            $end_date = clone $start_date;
            // Find the end of the year
            $end_date->add(new DateInterval('P1Y'));
        }
        $start_date->setTimezone(new DateTimeZone('UTC'));
        $end_date->setTimeZone(new DateTimeZone('UTC'));
    }

    //
    // Build the query to get the list of offerings
    //
    $strsql = "SELECT ciniki_fatt_offerings.id, "
        . "ciniki_fatt_offerings.course_id, "
        . "ciniki_fatt_courses.code AS course_code, "
        . "ciniki_fatt_courses.name AS course_name, "
        . "ciniki_fatt_courses.num_seats_per_instructor AS instructor_seats, "
//      . "ciniki_fatt_locations.name AS location_name, "
//      . "ciniki_fatt_locations.num_seats AS location_seats, "
        . "UNIX_TIMESTAMP(ciniki_fatt_offerings.start_date) AS start_date_ts, "
        . "ciniki_fatt_offerings.date_string, "
        . "ciniki_fatt_offerings.location, "
        . "ciniki_fatt_offerings.max_seats, "
        . "ciniki_fatt_offerings.seats_remaining, "
//      . "IFNULL(ciniki_fatt_offering_dates.start_date, '') AS start_date, "
        . "COUNT(ciniki_fatt_offering_registrations.id) AS num_registrations "
        . "FROM ciniki_fatt_offerings "
        . "LEFT JOIN ciniki_fatt_courses ON ("
            . "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
            . "AND ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
//      . "LEFT JOIN ciniki_fatt_offering_instructors ON ("
//          . "ciniki_fatt_offerings.id = ciniki_fatt_offering_instructors.offering_id "
//          . "AND ciniki_fatt_offering_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//          . ") "
//      . "LEFT JOIN ciniki_fatt_instructors ON ("
//          . "ciniki_fatt_offering_instructors.instructor_id = ciniki_fatt_instructors.id "
//          . "AND ciniki_fatt_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//          . ") "
//      . "LEFT JOIN ciniki_fatt_offering_dates ON ("
//          . "ciniki_fatt_offerings.id = ciniki_fatt_offering_dates.offering_id "
//          . "AND ciniki_fatt_offering_dates.day_number = 1 "
//          . "AND ciniki_fatt_offering_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//          . ") "
//      . "LEFT JOIN ciniki_fatt_locations ON ("
//          . "ciniki_fatt_offering_dates.location_id = ciniki_fatt_locations.id "
//          . "AND ciniki_fatt_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
//          . ") "
        . "LEFT JOIN ciniki_fatt_offering_registrations ON ("
            . "ciniki_fatt_offerings.id = ciniki_fatt_offering_registrations.offering_id "
            . "AND ciniki_fatt_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
    if( isset($args['year']) && $args['year'] != '' ) {
        if( $args['year'] == '????' ) {
            $strsql .= "AND ciniki_fatt_offerings.date_string = '' ";
        } else {
            $strsql .= "AND ciniki_fatt_offerings.start_date >= '" . $start_date->format('Y-m-d H:i:s') . "' "
                . "AND ciniki_fatt_offerings.start_date < '" . $end_date->format('Y-m-d H:i:s') . "' "
                . "";
        }
    }
    $strsql .= "GROUP BY ciniki_fatt_offerings.id "
        . "ORDER BY ciniki_fatt_offerings.start_date "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'offerings', 'fname'=>'id', 'name'=>'offering',
            'fields'=>array('id', 'course_id', 'course_code', 'course_name', 
                'instructor_seats', 'start_date_ts', 'date_string', 'location', 'num_registrations',
                'max_seats', 'seats_remaining', 
                ),
//          'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format)),
            ), 
        ));
    if( $rc['stat'] != 'ok') {
        return $rc;
    }
    if( isset($rc['offerings']) ) {
        $rsp['offerings'] = $rc['offerings'];
    } else {
        $rsp['offerings'] = array();
    }

    return $rsp;
}
?>
