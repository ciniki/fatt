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
function ciniki_fatt_offeringRegistrationUpdateCerts($ciniki, $business_id, $registration_id) {
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'certCustomerAdd');

    //
    // Get the registration
    //
    $strsql = "SELECT ciniki_fatt_offering_registrations.id, "
        . "ciniki_fatt_offering_registrations.offering_id, "
        . "ciniki_fatt_offering_registrations.student_id, "
        . "ciniki_fatt_offering_registrations.status "
        . "FROM ciniki_fatt_offering_registrations "
        . "WHERE ciniki_fatt_offering_registrations.id = '" . ciniki_core_dbQuote($ciniki, $registration_id) . "' "
        . "AND ciniki_fatt_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2443', 'msg'=>'Registration not found'));
    }
    $registration = $rc['registration'];

    //
    // Get the last date of the offering for the certification date
    //
    $strsql = "SELECT MAX(start_date) AS date_received "
        . "FROM ciniki_fatt_offering_dates "
        . "WHERE offering_id = '" . ciniki_core_dbQuote($ciniki, $registration['offering_id']) . "' "
        . "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'date');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['date']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2447', 'msg'=>'There are no dates associated with the offering, we are unable to add the certification.'));
    }
    $date_received = $rc['date']['date_received'];
    
    //
    // Get list of certifications the student should have a the completion of a course
    //
    $strsql = "SELECT ciniki_fatt_course_certs.cert_id "
        . "FROM ciniki_fatt_offerings "
        . "INNER JOIN ciniki_fatt_courses ON ("
            . "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
            . "AND ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_course_certs ON ("
            . "ciniki_fatt_courses.id = ciniki_fatt_course_certs.course_id "
            . "AND ciniki_fatt_course_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_fatt_offerings.id = '" . ciniki_core_dbQuote($ciniki, $registration['offering_id']) . "' "
        . "AND ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'certs', 'fname'=>'cert_id',
            'fields'=>array('id'=>'cert_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['certs']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2444', 'msg'=>'Registration not found'));
    }
    $course_certs = $rc['certs'];

    //
    // Load any current certs for this customer for the registration
    //
    $strsql = "SELECT ciniki_fatt_cert_customers.id, "
        . "ciniki_fatt_cert_customers.uuid, "
        . "ciniki_fatt_cert_customers.cert_id, "
        . "ciniki_fatt_cert_customers.date_received, "
        . "ciniki_fatt_cert_customers.date_expiry "
        . "FROM ciniki_fatt_cert_customers "
        . "WHERE ciniki_fatt_cert_customers.offering_id = '" . ciniki_core_dbQuote($ciniki, $registration['offering_id']) . "' "
        . "AND ciniki_fatt_cert_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $registration['student_id']) . "' "
        . "AND ciniki_fatt_cert_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'certs', 'fname'=>'cert_id',
            'fields'=>array('id', 'uuid', 'cert_id', 'date_received', 'date_expiry')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $customer_certs = array();
    if( isset($rc['certs']) ) {
        $customer_certs = $rc['certs'];
    }

    //
    // If the student has passed make sure their certs are applied
    //
    if( $registration['status'] == '10' ) {
        foreach($course_certs as $cert_id => $cert) {
            if( !isset($customer_certs[$cert_id]) ) {
                //
                // Add the cert
                //
                $rc = ciniki_fatt__certCustomerAdd($ciniki, $business_id, array(
                    'cert_id'=>$cert_id,
                    'offering_id'=>$registration['offering_id'],
                    'customer_id'=>$registration['student_id'],
                    'date_received'=>$date_received,
                    ));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2445', 'msg'=>'Unable to add certification', 'err'=>$rc['err']));
                }
            }
        }
    }

    //
    // Otherwise make sure the certs are removed from the student
    //
    else {
        foreach($course_certs as $cert_id => $cert) {
            if( isset($customer_certs[$cert_id]) ) {
                //
                // Delete the cert
                //
                $rc = ciniki_core_objectDelete($ciniki, $business_id, 'ciniki.fatt.certcustomer', 
                    $customer_certs[$cert_id]['id'], $customer_certs[$cert_id]['uuid'], 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2446', 'msg'=>'Unable to remove certification', 'err'=>$rc['err']));
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
