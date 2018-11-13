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
// tnid:         The ID of the tenant the offering is attached to.
// registration_id:     The ID of the offering registration to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_registrationWelcomeEmailSend(&$ciniki, $tnid, $registration_id) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'core', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringRegistrationLoad');
    $rc = ciniki_fatt_offeringRegistrationLoad($ciniki, $tnid, $registration_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.153', 'msg'=>'Registration does not exist'));
    }
    $registration = $rc['registration'];

    //
    // Load the course welcome_msg details 
    //
    $strsql = "SELECT name, welcome_msg "
        . "FROM ciniki_fatt_courses "
        . "WHERE ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_fatt_courses.id = '" . ciniki_core_dbQuote($ciniki, $registration['course_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.156', 'msg'=>'Unable to load course', 'err'=>$rc['err']));
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.157', 'msg'=>'Unable to find course'));
    }
    $course = $rc['item'];

    //
    // Load the dates for this course
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
            . "AND ciniki_fatt_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_fatt_offering_dates.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_fatt_offering_dates.offering_id = '" . ciniki_core_dbQuote($ciniki, $registration['offering_id']) . "' "
        . "ORDER BY ciniki_fatt_offering_dates.start_date, day_number "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.offerings', array(
        array('container'=>'dates', 'fname'=>'id', 
            'fields'=>array('id', 'day_number', 'start_date', 'num_hours', 'location_id', 'location_name', 'location_flags', 
                'address1', 'address2', 'city', 'postal', 'latitude', 'longitude'),
//            'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>'M b, Y'),
//                'start_time'=>array('timezone'=>$intl_timezone, 'format'=>'H:i:s'),
//                ),
            ),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $dates = '';
    if( isset($rc['dates']) ) {
        foreach($rc['dates'] as $did => $odate) {
            $dt = new DateTime($odate['start_date'], new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone($intl_timezone));
            $dates .= $dt->format('M j, Y');
            $dates .= ' ' . $dt->format('g:i a');
            $dt->add(new DateInterval('PT' . $odate['num_hours'] . 'H'));
            $dates .= ' - ' . $dt->format('g:i a');
            $dates .= "\n";
        }
    }

    //
    // Lookup student information
    //
    if( $registration['student_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, array('customer_id'=>$registration['student_id'], 'emails'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['customer']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.158', 'msg'=>'Student not found', 'err'=>$rc['err']));
        }
        $customer = $rc['customer'];
        if( !isset($customer['emails'][0]['email']['address']) ) {
            //
            // Check for parent email
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, array('customer_id'=>$registration['customer_id'], 'emails'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($rc['customer']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.170', 'msg'=>'No email for student', 'err'=>$rc['err']));
            }
            $customer = $rc['customer'];
            if( !isset($customer['emails'][0]['email']['address']) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.171', 'msg'=>'No email for customer'));
            } 
        }
    } else {
        // No student yet, nothing to send
        return array('stat'=>'ok');
    }

    //
    // Load welcome email message
    //
    $strsql = "SELECT ciniki_fatt_messages.id, "
        . "ciniki_fatt_messages.object, "
        . "ciniki_fatt_messages.object_id, "
        . "ciniki_fatt_messages.status, "
        . "ciniki_fatt_messages.days, "
        . "ciniki_fatt_messages.subject, "
        . "ciniki_fatt_messages.message, "
        . "ciniki_fatt_messages.parent_subject, "
        . "ciniki_fatt_messages.parent_message "
        . "FROM ciniki_fatt_messages "
        . "WHERE ciniki_fatt_messages.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_fatt_messages.object = 'ciniki.fatt.welcomemsg' "
        . "LIMIT 1 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.154', 'msg'=>'Unable to load welcome message.', 'err'=>$rc['err']));
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.155', 'msg'=>'Unable to find welcome message.'));
    }
    $message = $rc['item'];
    
    //
    // Run substitutions
    //
    $subject = $message['subject'];
    $content = $message['message'];
    $subject = str_ireplace("{_firstname_}", $customer['first'], $subject);
    $content = str_ireplace("{_firstname_}", $customer['first'], $content);

    $subject = str_ireplace("{_coursename_}", $course['name'], $subject);
    $content = str_ireplace("{_coursename_}", $course['name'], $content);

    $content = str_ireplace("{_coursedates_}", $dates, $content);

    //
    // Addition detail from course
    //
    $content = str_ireplace("{_coursewelcomemsg_}", $course['welcome_msg'], $content);

    //
    // Send the welcome message
    //
    $msg = array(
        'customer_email'=>$customer['emails'][0]['email']['address'],
        'customer_name'=>$customer['display_name'],
        'object'=>'ciniki.fatt.registration',
        'object_id'=>$registration['id'],
        'subject'=>$subject,
        'html_content'=>$content,
        'text_content'=>$content,
        );
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
    $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, $msg);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    } 
    $ciniki['emailqueue'][] = array('mail_id'=>$rc['id'], 'tnid'=>$tnid);

    return array('stat'=>'ok');
}
?>
