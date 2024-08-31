<?php
//
// Description
// -----------
// This method returns the list of customers and their certifications that are going to expire, or have expired.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get certs for.
// cert_ids:        The IDs of the certs to check for expirations. 0 if all certs.
// start_date:      Look for any expiration on or after this date.
// end_date:        Look for any expirations before this date.
//
// Returns
// -------
//
function ciniki_fatt_certCustomerExpirations($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Expire after date'), 
        'end_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Expire before date'), 
        'stats'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Stats'), 
        'grouping'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Certifications'), 
        'timespan'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Timespan'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //  
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.certCustomerExpirations');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the time information for tenant and user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    $php_date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    //
    // Get the current date in the tenant timezone
    //
    $cur_date = new DateTime('now', new DateTimeZone($intl_timezone));
    $last_date = new DateTime('now', new DateTimeZone($intl_timezone));
    $last_date->add(new DateInterval('P180D'));

    //
    // Load fatt maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'maps');
    $rc = ciniki_fatt_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $rsp = array('stat'=>'ok');

    //
    // Get the stats
    //
    if( isset($args['stats']) && $args['stats'] == 'yes' ) {
        $strsql = "SELECT ciniki_fatt_certs.grouping, "
            . "FLOOR((DATEDIFF(ciniki_fatt_cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, $cur_date->format('Y-m-d')) . "'))/30) AS timespan, "
            . "COUNT(ciniki_fatt_cert_customers.id) AS num_expirations "
            . "FROM ciniki_fatt_certs "
            . "LEFT JOIN ciniki_fatt_cert_customers ON ("
                . "ciniki_fatt_certs.id = ciniki_fatt_cert_customers.cert_id "
                . "AND ciniki_fatt_cert_customers.date_expiry >= '" . ciniki_core_dbQuote($ciniki, $cur_date->format('Y-m-d')) . "' "
                . "AND ciniki_fatt_cert_customers.date_expiry <= '" . ciniki_core_dbQuote($ciniki, $last_date->format('Y-m-d')) . "' "
                . "AND ciniki_fatt_cert_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_fatt_certs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_fatt_certs.grouping <> '' "
            . "GROUP BY ciniki_fatt_certs.grouping, timespan "
            . "ORDER BY ciniki_fatt_certs.grouping "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'groups', 'fname'=>'grouping', 'name'=>'group',
                'fields'=>array('name'=>'grouping')),
            array('container'=>'expirations', 'fname'=>'timespan', 'name'=>'expiration',
                'fields'=>array('timespan', 'num_expirations')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['stats'] = array();
        if( isset($rc['groups']) ) {
            foreach($rc['groups'] as $group) {
                $g = array('name'=>$group['group']['name'],
                    'ag0'=>0,
                    'ag1'=>0,
                    'ag2'=>0,
                    'ag3'=>0,
                    'ag4'=>0,
                    'ag5'=>0,
                    );
                if( isset($group['group']['expirations']) ) {
                    foreach($group['group']['expirations'] as $exp) {
                        $g['ag'.$exp['expiration']['timespan']] = $exp['expiration']['num_expirations'];
                    }
                }
                $rsp['stats'][] = array('group'=>$g);
            }
        }
    }

    //
    // Check if we should return the list of expirations
    //
    if( (isset($args['grouping']) && $args['grouping'] != '') 
        || (isset($args['timespan']) && $args['timespan'] != '')
        ) {
        $start_date = new DateTime('now', new DateTimeZone($intl_timezone));
        $end_date = new DateTime('now', new DateTimeZone($intl_timezone));
        if( isset($args['timespan']) && $args['timespan'] != '' ) {
            $end_date->add(new DateInterval('P' . (($args['timespan']+1)*30) . 'D'));
            if( $args['timespan'] > 0 ) {
                $start_date->add(new DateInterval('P' . (($args['timespan']*30)+1) . 'D'));
            }
        } else {
            $end_date->add(new DateInterval('P180D'));
        }

        $strsql = "SELECT ciniki_fatt_cert_customers.id, "
            . "ciniki_fatt_cert_customers.cert_id, "
            . "ciniki_fatt_cert_customers.customer_id, "
            . "ciniki_customers.display_name, "
            . "ciniki_fatt_certs.name, "
            . "ciniki_fatt_certs.years_valid, "
            . "DATE_FORMAT(ciniki_fatt_cert_customers.date_received, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_received, "
            . "DATE_FORMAT(ciniki_fatt_cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_expiry, "
            . "DATEDIFF(ciniki_fatt_cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, $cur_date->format('Y-m-d')) . "') AS days_till_expiry "
            . "FROM ciniki_fatt_cert_customers "
            . "INNER JOIN ciniki_fatt_certs ON ("
                . "ciniki_fatt_cert_customers.cert_id = ciniki_fatt_certs.id "
                . ((isset($args['grouping']) && $args['grouping'] != '' )?"AND ciniki_fatt_certs.grouping = '" . ciniki_core_dbQuote($ciniki, $args['grouping']) . "' ":"")
                . "AND ciniki_fatt_certs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers ON ("
                . "ciniki_fatt_cert_customers.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_fatt_cert_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_fatt_cert_customers.date_expiry >= '" . ciniki_core_dbQuote($ciniki, $start_date->format('Y-m-d')) . "' "
            . "AND ciniki_fatt_cert_customers.date_expiry <= '" . ciniki_core_dbQuote($ciniki, $end_date->format('Y-m-d')) . "' "
            . "ORDER BY days_till_expiry ASC "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'certs', 'fname'=>'id', 'name'=>'cert',
                'fields'=>array('id', 'customer_id', 'display_name', 'name', 'date_received', 'date_expiry', 'days_till_expiry', 'years_valid')),
            ));
        $rsp['certs'] = array();
        if( isset($rc['certs']) ) {
            $rsp['certs'] = $rc['certs'];
            foreach($rsp['certs'] as $cid => $cert) {
                if( $cert['cert']['years_valid'] > 0 ) {
                    $age = $cert['cert']['days_till_expiry'];
                    if( $age > 0 ) {
                        $rsp['certs'][$cid]['cert']['expiry_text'] = "Expiring in " . abs($age) . " day" . ($age>1?'s':'');
                    } elseif( $age == 0 ) {
                        $rsp['certs'][$cid]['cert']['expiry_text'] = "Expired today";
                    } elseif( $age < 0 ) {
                        $rsp['certs'][$cid]['cert']['expiry_text'] = "Expired " . abs($age) . " day" . ($age<1?'s':'') . " ago";
                    }
                } else {
                    $rsp['certs'][$cid]['cert']['date_expiry'] = 'No Expiration';
                    $rsp['certs'][$cid]['cert']['expiry_text'] = 'No Expiration';
                }
            }
        }
    }


/*
    //
    // Get the list of certs that have expirations
    //
    if( !isset($args['cert_ids']) ) {
        $strsql = "SELECT ciniki_fatt_certs.id "
            . "FROM ciniki_fatt_certs "
            . "WHERE ciniki_fatt_certs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND years_valid > 0 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.fatt', 'cert_ids', 'id');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['cert_ids']) ) {
            $args['cert_ids'] = $rc['cert_ids'];
        }
    }


    //
    // Get the list of certs that are going to expire between the start and end dates
    //
    $strsql = "SELECT ciniki_fatt_cert_customers.id, "
        . "ciniki_fatt_cert_customers.cert_id, "
        . "ciniki_fatt_cert_customers.customer_id, "
        . "ciniki_customers.display_name, "
        . "ciniki_fatt_certs.name, "
        . "ciniki_fatt_certs.years_valid, "
        . "DATE_FORMAT(ciniki_fatt_cert_customers.date_received, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_received, "
        . "DATE_FORMAT(ciniki_fatt_cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_expiry, "
        . "DATEDIFF(ciniki_fatt_cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, $cur_date->format('Y-m-d')) . "') AS days_till_expiry "
        . "FROM ciniki_fatt_cert_customers "
        . "INNER JOIN ciniki_fatt_certs ON ("
            . "ciniki_fatt_cert_customers.cert_id = ciniki_fatt_certs.id "
            . "AND ciniki_fatt_certs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_fatt_cert_customers.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_fatt_cert_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_fatt_cert_customers.date_expiry >= '" . ciniki_core_dbQuote($ciniki, $args['start_date']) . "' "
        . "AND ciniki_fatt_cert_customers.date_expiry < '" . ciniki_core_dbQuote($ciniki, $args['end_date']) . "' "
        . "ORDER BY days_till_expiry ASC "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'certs', 'fname'=>'id', 'name'=>'cert',
            'fields'=>array('id', 'customer_id', 'display_name', 'name', 'date_received', 'date_expiry', 'days_till_expiry', 'years_valid')),
        ));
    $rsp['certs'] = array();
    if( isset($rc['certs']) ) {
        $rsp['certs'] = $rc['certs'];
        foreach($rsp['certs'] as $cid => $cert) {
            if( $cert['cert']['years_valid'] > 0 ) {
                $age = $cert['cert']['days_till_expiry'];
                if( $age > 0 ) {
                    $rsp['certs'][$cid]['cert']['expiry_text'] = "Expiring in " . abs($age) . " day" . ($age>1?'s':'');
                } elseif( $age == 0 ) {
                    $rsp['certs'][$cid]['cert']['expiry_text'] = "Expired today";
                } elseif( $age < 0 ) {
                    $rsp['certs'][$cid]['cert']['expiry_text'] = "Expired " . abs($age) . " day" . ($age<1?'s':'') . " ago";
                }
            } else {
                $rsp['certs'][$cid]['cert']['date_expiry'] = 'No Expiration';
                $rsp['certs'][$cid]['cert']['expiry_text'] = 'No Expiration';
            }
        }
    }
*/
    return $rsp;
}
?>
