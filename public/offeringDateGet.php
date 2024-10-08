<?php
//
// Description
// ===========
// This method will return all the information about a offering date.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the offering date is attached to.
// offering_id:     The ID of the offering to get the defaults for.
// date_id:         The ID of the offering date to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_offeringDateGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'offering_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Offering'), 
        'date_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Date'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.offeringDateGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Load timezone
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    if( $args['date_id'] == 0 ) {
        $rsp = array('stat'=>'ok', 'offeringdate'=>array(
            'day_number'=>'1',
            'start_date'=>'',
            'num_hours'=>'',
            'location_id'=>'0',
            ));
        //
        // If a offering was provided, then lookup some details so we can
        // better prepare the default values
        //
        if( isset($args['offering_id']) && $args['offering_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectGet');
            $rc = ciniki_core_objectGet($ciniki, $args['tnid'], 'ciniki.fatt.offering', $args['offering_id']);
            if( $rc['stat'] == 'ok' && isset($rc['offering']) ) {
                $offering = $rc['offering'];
                $rc = ciniki_core_objectGet($ciniki, $args['tnid'], 'ciniki.fatt.course', $offering['course_id']);
                if( $rc['stat'] == 'ok' && isset($rc['course']) ) {
                    if( $rc['course']['num_days'] > 1 ) {
                        $rsp['offeringdate']['num_hours'] = '7';
                    }
                }
            }
            //
            // Check for dates already added
            //
            $strsql = "SELECT MAX(start_date) AS start_date, MAX(day_number) AS day_number, MAX(location_id) AS location_id "
                . "FROM ciniki_fatt_offering_dates "
                . "WHERE ciniki_fatt_offering_dates.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
                . "AND ciniki_fatt_offering_dates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'max');
            if( $rc['stat'] == 'ok' && isset($rc['max']['day_number']) ) {
                $rsp['offeringdate']['day_number'] = $rc['max']['day_number'] + 1;
                $rsp['offeringdate']['location_id'] = $rc['max']['location_id'];
                $tz = new DateTimeZone('UTC');
                $start_date = new DateTime($rc['max']['start_date'], $tz);
                $tz = new DateTimeZone($intl_timezone);
                $start_date->setTimezone($tz);
                $start_date->add(new DateInterval('P1D'));
                $rsp['offeringdate']['start_date'] = $start_date->format($datetime_format);
                $rsp['offeringdate']['date'] = $start_date->format('Y-m-d');
                $rsp['offeringdate']['time'] = $start_date->format('H:m');
            }
        }

        return $rsp;
    }

    //
    // Get the offering_date details
    //
    $strsql = "SELECT ciniki_fatt_offering_dates.id, "
        . "ciniki_fatt_offering_dates.day_number, "
        . "ciniki_fatt_offering_dates.start_date, "
        . "UNIX_TIMESTAMP(ciniki_fatt_offering_dates.start_date) AS start_date_ts, "
        . "ciniki_fatt_offering_dates.start_date AS date, "
        . "ciniki_fatt_offering_dates.start_date AS time, "
        . "ciniki_fatt_offering_dates.num_hours, "
        . "ciniki_fatt_offering_dates.location_id, "
        . "ciniki_fatt_offering_dates.address1, "
        . "ciniki_fatt_offering_dates.address2, "
        . "ciniki_fatt_offering_dates.city, "
        . "ciniki_fatt_offering_dates.province, "
        . "ciniki_fatt_offering_dates.postal, "
        . "ciniki_fatt_offering_dates.latitude, "
        . "ciniki_fatt_offering_dates.longitude "
        . "FROM ciniki_fatt_offering_dates "
        . "WHERE ciniki_fatt_offering_dates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_fatt_offering_dates.id = '" . ciniki_core_dbQuote($ciniki, $args['date_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'dates', 'fname'=>'id', 'name'=>'date',
            'fields'=>array('id', 'day_number', 'start_date', 'start_date_ts', 'date', 'time', 'num_hours', 'location_id',
                'address1', 'address2', 'city', 'province', 'postal', 'latitude', 'longitude'),
            'utctotz'=>array(
                'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                'date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d'),
                'time'=>array('timezone'=>$intl_timezone, 'format'=>'H:m'),
            )),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['dates']) || !isset($rc['dates'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.102', 'msg'=>'Unable to find date'));
    }
    $date = $rc['dates'][0]['date'];
    $date['num_hours'] = (float)$date['num_hours'];

    return array('stat'=>'ok', 'offeringdate'=>$date);
}
?>
