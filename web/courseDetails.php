<?php
//
// Description
// -----------
// This function will return the menu items for the main menu.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get events for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_fatt_web_courseDetails(&$ciniki, $settings, $business_id, $permalink) {
    
    if( !isset($ciniki['business']['modules']['ciniki.fatt']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.fatt.131', 'msg'=>"I'm sorry, the file you requested does not exist."));
    }

    //
    // Get the time information for business and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the course
    //
    $strsql = "SELECT ciniki_fatt_courses.id, "
        . "ciniki_fatt_courses.name, "
        . "ciniki_fatt_courses.code, "
        . "ciniki_fatt_courses.permalink, "
        . "ciniki_fatt_courses.status, "
        . "ciniki_fatt_courses.primary_image_id AS image_id, "
        . "ciniki_fatt_courses.synopsis, "
        . "ciniki_fatt_courses.description, "
        . "ciniki_fatt_courses.price, "
        . "ciniki_fatt_courses.taxtype_id, "
        . "ciniki_fatt_courses.num_days, "
        . "ciniki_fatt_courses.num_hours, "
        . "ciniki_fatt_courses.flags "
        . "FROM ciniki_fatt_courses "
        . "WHERE ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_fatt_courses.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'course');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['course']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.fatt.132', 'msg'=>"I'm sorry, the course you requested does not exist."));
    }
    $course = $rc['course'];

    //
    // Load the upcoming classes
    //
    $strsql = "SELECT ciniki_fatt_offerings.id, "
        . "ciniki_fatt_offerings.permalink, "
        . "ciniki_fatt_offerings.price AS unit_amount, "
        . "ciniki_fatt_offerings.flags, "
        . "ciniki_fatt_offerings.start_date, "
        . "ciniki_fatt_offerings.date_string, "
        . "ciniki_fatt_offerings.location, "
        . "ciniki_fatt_offerings.city, "
        . "ciniki_fatt_offerings.seats_remaining, "
        . "ciniki_fatt_offering_dates.id AS date_id, "
        . "ciniki_fatt_offering_dates.start_date AS start_time, "
        . "ADDTIME(ciniki_fatt_offering_dates.start_date, CONCAT_WS(':', ciniki_fatt_offering_dates.num_hours, 0, 0)) AS end_time "
        . "FROM ciniki_fatt_offerings "
        . "LEFT JOIN ciniki_fatt_offering_dates ON ("
            . "ciniki_fatt_offerings.id = ciniki_fatt_offering_dates.offering_id "
            . "AND ciniki_fatt_offering_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_fatt_offerings.course_id = '" . ciniki_core_dbQuote($ciniki, $course['id']) . "' "
        . "AND (ciniki_fatt_offerings.flags&0x01) = 0x01 "
        . "AND ciniki_fatt_offerings.start_date >= UTC_TIMESTAMP() "
        . "ORDER BY ciniki_fatt_offerings.start_date "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');  
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'offerings', 'fname'=>'id', 
            'fields'=>array('id', 'permalink', 'unit_amount', 'flags', 'start_date', 'date_string', 'location', 'city', 'seats_remaining')),
        array('container'=>'dates', 'fname'=>'date_id',
            'fields'=>array('start_time', 'end_time'),
            'utctotz'=>array(
                'start_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:ia'),
                'end_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:ia'),
            )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['offerings']) ) {
        $course['offerings'] = array();
    } else {
        $course['offerings'] = $rc['offerings'];
        foreach($course['offerings'] as $oid => $offering) {
            $course['offerings'][$oid]['times'] = '';
            if( isset($offering['dates']) ) {
                foreach($offering['dates'] as $did => $date) {
                    $times = $date['start_time'] . ' - ' . $date['end_time'];
                    if( $times != $course['offerings'][$oid]['times'] ) {
                        $course['offerings'][$oid]['times'] .= ($course['offerings'][$oid]['times']!=''?', ':'') . $times;
                    }
                }
//              unset($offering['dates']);
            }
        }
    }

    return array('stat'=>'ok', 'course'=>$course);
}
?>
