<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get events for.
//
// Returns
// -------
//
function ciniki_fatt_hooks_uiCustomersData($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');

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
    // Setup current date in tenant timezone
    //
    $cur_date = new DateTime('now', new DateTimeZone($intl_timezone));

    //
    // Default response
    //
    $rsp = array('stat'=>'ok', 'tabs'=>array());

    //
    // Get the list of current and past certifications for customer(s)
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.fatt', 0x10) && (isset($args['customer_id']) || isset($args['customer_ids'])) ) {
        //
        // Get the list of registrations for the customer
        //
        $sections['ciniki.fatt.registrations.upcoming'] = array(
            'label' => 'Upcoming Registrations',
            'type' => 'simplegrid', 
            'num_cols' => 3,
            'headerValues' => array('Name', 'Course', 'Date'),
            'cellClasses' => array('', '', ''),
            'noData' => 'No upcoming registrations',
            'editApp' => array('app'=>'ciniki.fatt.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
            'cellValues' => array(
                '0' => "d.first+' '+(d.last[0]!=null?d.last[0]:'')",
                '1' => "d.code",
                '2' => "d.date_string",
                ),
            'data' => array(),
            );
        $sections['ciniki.fatt.registrations.past'] = array(
            'label' => 'Past Registrations',
            'type' => 'simplegrid', 
            'num_cols' => 4,
            'headerValues' => array('Name', 'Course', 'Date', 'Status'),
            'cellClasses' => array('', '', '', ''),
            'noData' => 'No past registrations',
            'editApp' => array('app'=>'ciniki.fatt.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
            'cellValues' => array(
                '0' => "d.first+' '+(d.last[0]!=null?d.last[0]:'')",
                '1' => "d.code",
                '2' => "d.date_string",
                '3' => "d.status_text",
                ),
            'data' => array(),
            );
        $strsql = "SELECT regs.id, regs.customer_id, regs.student_id, "
            . "regs.status AS status_text, "
            . "IFNULL(customers.type, 0) AS customer_type, "
            . "IFNULL(customers.first, '') AS first, "
            . "IFNULL(customers.last, '') AS last, "
            . "offerings.date_string, "
            . "courses.code, "
            . "courses.name, "
            . "DATEDIFF('" . ciniki_core_dbQuote($ciniki, $cur_date->format('Y-m-d')) . "', offerings.start_date) AS days_till_start "
            . "FROM ciniki_fatt_offering_registrations AS regs "
            . "INNER JOIN ciniki_fatt_offerings AS offerings ON ("
                . "regs.offering_id = offerings.id "
                . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_fatt_courses AS courses ON ("
                . "offerings.course_id = courses.id "
                . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "regs.student_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE regs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        if( isset($args['customer_id']) ) {
            $strsql .= "AND regs.student_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
        } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
            $strsql .= "AND regs.student_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
        } else {
            return array('stat'=>'ok');
        }
        $strsql .= "ORDER BY customers.display_name, offerings.start_date DESC, courses.name "
            . "";
            error_log($strsql);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'customer_id', 'student_id', 'customer_type', 'first', 'last', 'date_string', 'status_text', 'code', 'name', 'days_till_start'),
                'maps'=>array('status_text'=>$maps['offeringregistration']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['registrations']) && count($rc['registrations']) > 0 ) {
            foreach($rc['registrations'] as $reg) {
                if( $reg['customer_type'] == 30 && $reg['customer_id'] == $reg['student_id'] ) {
                    $reg['first'] = 'Saved Seat';
                    $reg['last'] = '';
                }
                if( $reg['days_till_start'] > 0 ) {
                    $sections['ciniki.fatt.registrations.past']['data'][] = $reg;
                } else {
                    $sections['ciniki.fatt.registrations.upcoming']['data'][] = $reg;
                }
            }
        }
        $rsp['tabs'][] = array(
            'id' => 'ciniki.fatt.registrations',
            'label' => 'Registrations',
            'sections' => $sections,
            );
        $sections = array();

        //
        // Get the certs for the customer
        //
        $strsql = "SELECT ciniki_fatt_cert_customers.id, "
            . "ciniki_fatt_cert_customers.customer_id, "
            . "IFNULL(ciniki_customers.first, '') AS first, "
            . "IFNULL(ciniki_customers.last, '') AS last, "
            . "ciniki_fatt_certs.id AS cert_id, "
            . "DATE_FORMAT(ciniki_fatt_cert_customers.date_received, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_received, "
            . "DATE_FORMAT(ciniki_fatt_cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_expiry, "
            . "DATEDIFF('" . ciniki_core_dbQuote($ciniki, $cur_date->format('Y-m-d')) . "', ciniki_fatt_cert_customers.date_expiry) AS age, "
            . "ciniki_fatt_certs.name, "
            . "ciniki_fatt_certs.years_valid "
            . "FROM ciniki_fatt_cert_customers "
            . "LEFT JOIN ciniki_fatt_certs ON ("
                . "ciniki_fatt_cert_customers.cert_id = ciniki_fatt_certs.id "
                . "AND ciniki_fatt_certs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers ON ("
                . "ciniki_fatt_cert_customers.customer_id = ciniki_customers.id "
                . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_fatt_cert_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        if( isset($args['customer_id']) ) {
            $strsql .= "AND ciniki_fatt_cert_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' ";
        } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
            $strsql .= "AND ciniki_fatt_cert_customers.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") ";
        } else {
            return array('stat'=>'ok');
        }
        $strsql .= "ORDER BY ciniki_fatt_cert_customers.date_expiry DESC, ciniki_fatt_certs.name, ciniki_customers.display_name "
            . "";
        if( isset($args['limit']) && $args['limit'] > 0 ) {
            $strsql .= "LIMIT " . $args['limit'] . " ";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $curcerts = array();
        $pastcerts = array();
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'cert');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        $sections['ciniki.fatt.certs.current'] = array(
            'label' => 'Certifications',
            'type' => 'simplegrid', 
            'num_cols' => 2,
            'headerValues' => array('Certification', 'Expiration'),
            'cellClasses' => array('multiline', 'multiline'),
            'noData' => 'No current certifications',
            'addTxt' => 'Add Certification',
            'addApp' => array('app'=>'ciniki.fatt.reports', 'args'=>array('certcustomer_id'=>'0', 'customer_id'=>0)),
            'editApp' => array('app'=>'ciniki.fatt.reports', 'args'=>array('certcustomer_id'=>'d.id;', 'customer_id'=>'d.customer_id;')),
            'cellValues' => array(),
            'data' => array(),
            );
        $sections['ciniki.fatt.certs.past'] = array(
            'label' => 'History',
            'type' => 'simplegrid', 
            'num_cols' => 2,
            'headerValues' => array('Certification', 'Expiration'),
            'cellClasses' => array('multiline', 'multiline'),
            'noData' => 'No expired certifications',
            'editApp' => array('app'=>'ciniki.fatt.reports', 'args'=>array('certcustomer_id'=>'d.id;', 'customer_id'=>'d.customer_id;')),
            'cellValues' => array(),
            'data' => array(),
            );
/*        if( isset($args['customer_ids']) ) { */
            $sections['ciniki.fatt.certs.current']['num_cols'] = 3;
            $sections['ciniki.fatt.certs.current']['headerValues'] = array('Name', 'Certification', 'Expiration');
            $sections['ciniki.fatt.certs.current']['cellClasses'] = array('', 'multiline', 'multiline');
            $sections['ciniki.fatt.certs.past']['num_cols'] = 3;
            $sections['ciniki.fatt.certs.past']['headerValues'] = array('Name', 'Certification', 'Expiration');
            $sections['ciniki.fatt.certs.past']['cellClasses'] = array('', 'multiline', 'multiline');
            $sections['ciniki.fatt.certs.current']['cellValues'] = array(
                '0' => "d.first+' '+d.last[0]",
                '1' => "'<span class=\"maintext\">'+d.name+'</span><span class=\"subtext\">'+d.date_received+'</span>'",
                '2' => "'<span class=\"maintext\">'+d.expiry_text+'</span><span class=\"subtext\">'+d.date_expiry+'</span>'",
                );
            $sections['ciniki.fatt.certs.past']['cellValues'] = array(
                '0' => "d.first+' '+d.last[0]",
                '1' => "'<span class=\"maintext\">'+d.name+'</span><span class=\"subtext\">'+d.date_received+'</span>'",
                '2' => "'<span class=\"maintext\">'+d.expiry_text+'</span><span class=\"subtext\">'+d.date_expiry+'</span>'",
                );
/*        } else {
            $sections['ciniki.fatt.certs.current']['cellValues'] = array(
                '0' => "'<span class=\"maintext\">'+d.name+'</span><span class=\"subtext\">'+d.date_received+'</span>'",
                '1' => "'<span class=\"maintext\">'+d.expiry_text+'</span><span class=\"subtext\">'+d.date_expiry+'</span>'",
                );
            $sections['ciniki.fatt.certs.past']['cellValues'] = array(
                '0' => "'<span class=\"maintext\">'+d.name+'</span><span class=\"subtext\">'+d.date_received+'</span>'",
                '1' => "'<span class=\"maintext\">'+d.expiry_text+'</span><span class=\"subtext\">'+d.date_expiry+'</span>'",
                );
        } */
        foreach($rc['rows'] as $row) {
            $cert = array(
                'id'=>$row['id'],
                'cert_id'=>$row['cert_id'],
                'customer_id'=>$row['customer_id'],
                'first'=>$row['first'],
                'last'=>$row['last'],
                'name'=>$row['name'],
                'age'=>$row['age'],
                'date_received'=>$row['date_received'],
                'date_expiry'=>'No Expiration',
                'expiry_text'=>'No Expiration',
                );
            //
            // If an expiring certificate
            //
            if( $row['years_valid'] > 0 ) {
                $cert['date_expiry'] = $row['date_expiry'];
                $cert['days_till_expiry'] = $row['age'];
                if( $row['age'] < 0 ) {
                    $cert['expiry_text'] = "Expiring in " . abs($row['age']) . " day" . ($row['age']<1?'s':'');
                    array_unshift($sections['ciniki.fatt.certs.current']['data'], $cert);
                } elseif( $row['age'] == 0 ) {
                    $cert['expiry_text'] = "Expired today";
                    $sections['ciniki.fatt.certs.past']['data'][] = $cert;
                } elseif( $row['age'] > 0 ) {
                    $cert['expiry_text'] = "Expired " . $row['age'] . " day" . ($row['age']>1?'s':'') . " ago";
                    $sections['ciniki.fatt.certs.past']['data'][] = $cert;
                }
            }
        }

        //
        // Add a tab the customer UI data screen with the certificate list
        //
        $rsp['tabs'][] = array(
            'id' => 'ciniki.fatt.certs',
            'label' => 'Certs',
            'sections' => $sections,
            );
    }

    return $rsp;
}
?>
