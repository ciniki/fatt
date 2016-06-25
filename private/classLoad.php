<?php
//
// Description
// ===========
// This function will load an offering and all associated data
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business the offering is attached to.
// offering_id:     The ID of the offering to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_classLoad($ciniki, $business_id, $args) {
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Check for an class_id and split into start_ts and location_id
    //
    if( isset($args['class_id']) && $args['class_id'] != '' ) {
        $sp = explode('-', $args['class_id']);
        if( count($sp) < 2 ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2420', 'msg'=>'Invalid appointment'));
        }
        $args['start_ts'] = $sp[0];
        $args['location_id'] = $sp[1];
    }

    //
    // Check if start and location not specified
    //
    if( !isset($args['start_ts']) || !isset($args['location_id']) || $args['start_ts'] == '' || $args['location_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2421', 'msg'=>'You must specifiy a start time and location.'));
    }

    //
    // Load the offerings that are part of the class
    //
    $strsql = "SELECT ciniki_fatt_offerings.id, "
        . "ciniki_fatt_offerings.id AS offering_ids, "
        . "CONCAT_WS('-', UNIX_TIMESTAMP(ciniki_fatt_offering_dates.start_date), ciniki_fatt_locations.id) AS appointment_id, "
        . "ciniki_fatt_offerings.course_id, "
        . "ciniki_fatt_courses.code AS course_codes, "
        . "ciniki_fatt_courses.name AS course_name, "
        . "ciniki_fatt_courses.num_hours AS course_hours, "
        . "ciniki_fatt_courses.num_seats_per_instructor AS instructor_seats, "
        . "ciniki_fatt_locations.code AS location_code, "
        . "ciniki_fatt_locations.name AS location_name, "
        . "ciniki_fatt_locations.flags AS location_flags, "
        . "ciniki_fatt_locations.num_seats AS location_seats, "
        . "ciniki_fatt_offering_dates.start_date AS start_date, "
        . "ciniki_fatt_offering_dates.start_date AS date, "
        . "ciniki_fatt_offerings.date_string, "
        . "ciniki_fatt_offerings.location, "
        . "ciniki_fatt_offerings.max_seats, "
        . "ciniki_fatt_offerings.seats_remaining, "
        . "ciniki_fatt_offering_dates.address1, "
        . "ciniki_fatt_offering_dates.address2, "
        . "ciniki_fatt_offering_dates.city, "
        . "ciniki_fatt_offering_dates.province, "
        . "ciniki_fatt_offering_dates.postal, "
        . "ciniki_fatt_offering_dates.latitude, "
        . "ciniki_fatt_offering_dates.longitude "
        . "FROM ciniki_fatt_offering_dates "
        . "LEFT JOIN ciniki_fatt_offerings ON ("
            . "ciniki_fatt_offering_dates.offering_id = ciniki_fatt_offerings.id "
            . "AND ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_courses ON ("
            . "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
            . "AND ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_locations ON ("
            . "ciniki_fatt_offering_dates.location_id = ciniki_fatt_locations.id "
            . "AND ciniki_fatt_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_fatt_offering_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND UNIX_TIMESTAMP(ciniki_fatt_offering_dates.start_date) = '" . ciniki_core_dbQuote($ciniki, $args['start_ts']) . "' "
        . "AND ciniki_fatt_offering_dates.location_id = '" . ciniki_core_dbQuote($ciniki, $args['location_id']) . "' "
        . "GROUP BY ciniki_fatt_offerings.id "
        . "ORDER BY ciniki_fatt_courses.num_hours, ciniki_fatt_courses.code "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'classes', 'fname'=>'appointment_id', 'name'=>'class',
            'fields'=>array('appointment_id', 'course_codes', 'start_date', 'date', 'location_code', 'location_name', 'location_flags', 'offering_ids',
                'address1', 'address2', 'city', 'province', 'postal', 'latitude', 'longitude'),
            'dlists'=>array('course_codes'=>'/', 'offering_ids'=>','),
            'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                )),
        array('container'=>'offerings', 'fname'=>'id', 'name'=>'offering', 
            'fields'=>array('id', 'course_code'=>'course_codes', 'course_hours', 'course_name', 'course_id', 'max_seats', 'seats_remaining')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['classes'][0]['class']) ) {
        return array('stat'=>'noexist', 'err'=>array('pkg'=>'ciniki', 'code'=>'2422', 'msg'=>'Unable to find class'));
    }
    $class = $rc['classes'][0]['class'];

    //
    // Build the offering array
    //
    $offerings = array();
    $seats_remaining = 9999;
    foreach($class['offerings'] AS $oid => $offering) {
        $offerings[$offering['offering']['id']] = $offering['offering'];
        if( $offering['offering']['seats_remaining'] < $seats_remaining ) {
            $seats_remaining = $offering['offering']['seats_remaining'];
        }
        $class['offerings'][$oid]['offering']['num_registrations'] = 0;
        $offerings[$offering['offering']['id']]['num_registrations'] = 0;
    }
    if( $seats_remaining < 9999 ) {
        $class['seats_remaining'] = $seats_remaining;
    }
    $class['num_registrations'] = 0;
    
    //
    // Get the list of instructors for the class
    //
    $strsql = "SELECT DISTINCT ciniki_fatt_offering_instructors.instructor_id, "
        . "ciniki_fatt_instructors.name, "
        . "ciniki_fatt_instructors.email, "
        . "ciniki_fatt_instructors.phone "
        . "FROM ciniki_fatt_offering_instructors, ciniki_fatt_instructors "
        . "WHERE ciniki_fatt_offering_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_fatt_offering_instructors.offering_id IN (" . ciniki_core_dbQuoteIDs($ciniki, explode(',', $class['offering_ids'])) . ") "
        . "AND ciniki_fatt_offering_instructors.instructor_id = ciniki_fatt_instructors.id "
        . "AND ciniki_fatt_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.offerings', array(
        array('container'=>'instructors', 'fname'=>'instructor_id', 'name'=>'instructor',
            'fields'=>array('id'=>'instructor_id', 'name', 'email', 'phone')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['instructors']) ) {
        $class['instructors'] = $rc['instructors'];
    } else {
        $class['instructors'] = array();
    }

    //
    // Get the list of registrations for the class
    //
    $strsql = "SELECT ciniki_fatt_offering_registrations.id, "
        . "ciniki_fatt_offering_registrations.offering_id, "
        . "ciniki_fatt_offering_registrations.customer_id, "
        . "ciniki_fatt_offering_registrations.student_id, "
        . "ciniki_fatt_offering_registrations.invoice_id, "
        . "ciniki_fatt_offering_registrations.offering_id, "
        . "ciniki_fatt_offering_registrations.status, "
        . "ciniki_fatt_offering_registrations.customer_notes, "
        . "ciniki_fatt_offering_registrations.notes, "
        . "IFNULL(c1.display_name, '') AS customer_display_name, "
        . "IFNULL(c1.type, '') AS customer_type, "
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
        . "LEFT JOIN ciniki_fatt_offerings ON ("
            . "ciniki_fatt_offering_registrations.offering_id = ciniki_fatt_offerings.id "
            . "AND ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_fatt_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_fatt_offering_registrations.offering_id IN (" . ciniki_core_dbQuoteIDs($ciniki, explode(',', $class['offering_ids'])) . ") "
        . "ORDER BY student_display_name, customer_display_name "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.offerings', array(
        array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
            'fields'=>array('id', 'offering_id', 'invoice_id', 'status',
                'customer_id', 'customer_display_name', 'customer_type', 
                'student_id', 'student_display_name',
                'customer_notes', 'notes')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $course_codes = array();
    if( isset($rc['registrations']) ) {
        $class['registrations'] = $rc['registrations'];
        //
        // Get the invoice status for each registration
        //
        $invoice_ids = array();
        foreach($class['registrations'] as $rid => $reg) {
            $invoice_ids[$reg['registration']['invoice_id']] = $rid;
            $class['registrations'][$rid]['registration']['course_hours'] = $offerings[$reg['registration']['offering_id']]['course_hours'];
            $class['registrations'][$rid]['registration']['course_code'] = $offerings[$reg['registration']['offering_id']]['course_code'];
            $offerings[$reg['registration']['offering_id']]['num_registrations']++;
            $course_codes[] = $offerings[$reg['registration']['offering_id']]['course_code'];
        }
        $course_codes = array_unique($course_codes);
        sort($course_codes);
        $class['course_codes'] = implode('/', $course_codes);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceStatus');
        $rc = ciniki_sapos_hooks_invoiceStatus($ciniki, $business_id, array('invoice_ids'=>array_keys($invoice_ids)));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['invoices']) ) {
            foreach($class['registrations'] as $rid => $registration) {
                if( isset($rc['invoices'][$registration['registration']['invoice_id']]) ) {
                    $class['registrations'][$rid]['registration']['invoice_status'] = $rc['invoices'][$registration['registration']['invoice_id']]['status_text'];
                    $class['registrations'][$rid]['registration']['invoice_customer_id'] = $rc['invoices'][$registration['registration']['invoice_id']]['customer_id'];
                    $class['registrations'][$rid]['registration']['invoice_number'] = $rc['invoices'][$registration['registration']['invoice_id']]['invoice_number'];
                }
            }
        }
        // Sort by class code
        usort($class['registrations'], function($a, $b) { 
            if( $a['registration']['course_hours'] == $b['registration']['course_hours'] ) {
                if( $a['registration']['course_code'] == $b['registration']['course_hours'] ) {
                    return 0;
                } else {
                    return strnatcmp($a['registration']['course_code'], $b['registration']['course_code']);
                }
            }
            return ($a['registration']['course_hours']<$b['registration']['course_hours'])?-1:1; 
        }); 
        // Fill in registrations into offerings
        foreach($class['offerings'] as $oid => $offering) {
            $class['offerings'][$oid]['offering']['num_registrations'] = $offerings[$offering['offering']['id']]['num_registrations'];
        }
    } else {
        $class['registrations'] = array();
    }

    return array('stat'=>'ok', 'class'=>$class);
}
?>
