<?php
//
// Description
// ===========
// This method will return all the information about a offering.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the offering is attached to.
// registration_id:     The ID of the offering registration to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_offeringRegistrationLoad($ciniki, $business_id, $registration_id) {
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

    //
    // Get the registration details
    //
    $strsql = "SELECT ciniki_fatt_offering_registrations.id, "
        . "ciniki_fatt_offering_registrations.offering_id, "
        . "ciniki_fatt_offering_registrations.customer_id, "
        . "ciniki_fatt_offering_registrations.student_id, "
        . "ciniki_fatt_offering_registrations.invoice_id, "
        . "ciniki_fatt_offering_registrations.status, "
        . "ciniki_fatt_offering_registrations.customer_notes, "
        . "ciniki_fatt_offering_registrations.notes, "
        . "ciniki_fatt_offering_registrations.test_results, "
        . "ciniki_fatt_offerings.course_id, "
        . "IFNULL(ciniki_fatt_courses.name, '') AS course_name, "
        . "ciniki_fatt_offerings.permalink, "
        . "ciniki_fatt_offerings.price, "
        . "ciniki_fatt_offerings.flags, "
        . "ciniki_fatt_offerings.flags AS flags_display, "
        . "ciniki_fatt_offerings.start_date, "
        . "ciniki_fatt_offerings.date_string, "
        . "ciniki_fatt_offerings.location, "
        . "ciniki_fatt_offerings.max_seats, "
        . "ciniki_fatt_offerings.seats_remaining "
        . "FROM ciniki_fatt_offering_registrations "
        . "INNER JOIN ciniki_fatt_offerings ON ("
            . "ciniki_fatt_offering_registrations.offering_id = ciniki_fatt_offerings.id "
            . "AND ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_courses ON ("
            . "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
            . "AND ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_fatt_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_fatt_offering_registrations.id = '" . ciniki_core_dbQuote($ciniki, $registration_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.offerings', array(
        array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
            'fields'=>array('id', 'offering_id', 'customer_id', 'student_id', 'invoice_id', 'status',
                'customer_notes', 'notes', 'test_results',
                'course_id', 'course_name', 'permalink', 'price', 'flags', 'flags_display',
                'start_date', 'date_string', 'location', 'max_seats', 'seats_remaining'),
            'flags'=>array('flags_display'=>$maps['offering']['flags']),
            ),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registrations']) || !isset($rc['registrations'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.21', 'msg'=>'Unable to find registration'));
    }
    $rsp['registration'] = $rc['registrations'][0]['registration'];
    $rsp['registration']['price'] = numfmt_format_currency($intl_currency_fmt, $rsp['registration']['price'], $intl_currency);
    
    //
    // Get the customer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
    if( $rsp['registration']['customer_id'] > 0 ) {
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $business_id, array('customer_id'=>$rsp['registration']['customer_id'], 
            'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes', 'subscriptions'=>'no'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['registration']['customer_name'] = $rc['customer']['display_name'];
        $rsp['registration']['customer_details'] = $rc['details'];
    }

    //
    // Get the student details
    //
    if( $rsp['registration']['customer_id'] != $rsp['registration']['student_id'] && $rsp['registration']['student_id'] > 0 ) {
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $business_id, array('customer_id'=>$rsp['registration']['student_id'], 
            'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes', 'subscriptions'=>'no'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['registration']['student_details'] = $rc['details'];
    }

    //
    // Get the invoice item details
    //
    if( $rsp['registration']['invoice_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
        $rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $business_id, $rsp['registration']['invoice_id'], 
            'ciniki.fatt.offeringregistration', $rsp['registration']['id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['invoice']) ) {
            $rsp['registration']['invoice_details'][] = array('detail'=>array('label'=>'Invoice', 'value'=>'#' . $rc['invoice']['invoice_number'] . ' - ' . $rc['invoice']['status_text']));
            $rsp['registration']['invoice_details'][] = array('detail'=>array('label'=>'Date', 'value'=>$rc['invoice']['invoice_date']));
            $rsp['registration']['invoice_status'] = $rc['invoice']['status'];
        }
        if( isset($rc['item']) ) {
            $rsp['registration']['item_id'] = $rc['item']['id'];
            $rsp['registration']['unit_amount'] = $rc['item']['unit_amount_display'];
            $rsp['registration']['unit_discount_amount'] = $rc['item']['unit_discount_amount_display'];
            $rsp['registration']['unit_discount_percentage'] = $rc['item']['unit_discount_percentage'];
            $rsp['registration']['taxtype_id'] = $rc['item']['taxtype_id'];
        } else {
            $rsp['registration']['item_id'] = 0;
            $rsp['registration']['unit_amount'] = '';
            $rsp['registration']['unit_discount_amount'] = '';
            $rsp['registration']['unit_discount_percentage'] = '';
            $rsp['registration']['taxtype_id'] = 0;
        }
    }

    return $rsp;
}
?>
