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
// tnid:     The ID of the tenant to get events for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_fatt_web_offeringDetails(&$ciniki, $settings, $tnid, $uuid) {
    
    if( !isset($ciniki['tenant']['modules']['ciniki.fatt']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.fatt.166', 'msg'=>"I'm sorry, the file you requested does not exist."));
    }

    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

    //
    // Load the upcoming classes
    //
    $strsql = "SELECT ciniki_fatt_offerings.id, "
        . "ciniki_fatt_offerings.uuid, "
        . "ciniki_fatt_offerings.permalink, "
        . "ciniki_fatt_courses.code, "
        . "ciniki_fatt_courses.name, "
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
        . "INNER JOIN ciniki_fatt_courses ON ("
            . "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
            . "AND ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_offering_dates ON ("
            . "ciniki_fatt_offerings.id = ciniki_fatt_offering_dates.offering_id "
            . "AND ciniki_fatt_offering_dates.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_fatt_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_fatt_offerings.uuid = '" . ciniki_core_dbQuote($ciniki, $uuid) . "' "
        . "AND (ciniki_fatt_offerings.flags&0x01) = 0x01 "
        . "AND ciniki_fatt_offerings.start_date >= UTC_TIMESTAMP() "
        . "ORDER BY ciniki_fatt_offerings.start_date "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');  
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'offerings', 'fname'=>'id',
            'fields'=>array('id', 'uuid', 'permalink', 'code', 'name', 'unit_amount', 'flags',
                'start_date', 'date_string', 'city', 'seats_remaining', 'date_id', 'start_time', 'end_time'),
            'utctotz'=>array('start_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
                'end_time'=>array('timezone'=>$intl_timezone, 'format'=>$time_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.167', 'msg'=>'Unable to load offering', 'err'=>$rc['err']));
    }
    if( !isset($rc['offerings'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.168', 'msg'=>'Unable to find requested offering'));
    }
    $offering = $rc['offerings'][0];
    
    return array('stat'=>'ok', 'offering'=>$offering);
}
?>
