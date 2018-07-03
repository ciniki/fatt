<?php
//
// Description
// -----------
// Return the report of upcoming certificate expirations
//
// Arguments
// ---------
// ciniki:
// tnid:         
// args:                The options for the query.
//
// Additional Arguments
// --------------------
// days:                The number of days forward to look for certificate expirations.
// 
// Returns
// -------
//
function ciniki_fatt_reporting_blockCertExpirations(&$ciniki, $tnid, $args) {
    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'private', 'maps');
    $rc = ciniki_customers_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    if( isset($args['days']) && $args['days'] != '' && $args['days'] > 0 && $args['days'] < 366 ) {
        $days = $args['days'];
    } else {
        $days = 7;
    }

    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $end_dt = clone $start_dt;
    $end_dt->add(new DateInterval('P' . $days . 'D'));

    //
    // Store the report block chunks
    //
    $chunks = array();

    //
    // Get the certs that are expiring
    //
    $strsql = "SELECT cc.id, "
        . "cc.cert_id, "
        . "cc.customer_id, "
        . "customers.display_name, "
        . "customers.parent_id, "
        . "IFNULL(parents.display_name, '') AS parent_name, "
        . "certs.name, "
        . "certs.years_valid, "
        . "DATE_FORMAT(cc.date_received, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_received, "
        . "DATE_FORMAT(cc.date_expiry, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_expiry, "
        . "DATEDIFF(cc.date_expiry, '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "') AS days_till_expiry "
        . "FROM ciniki_fatt_cert_customers AS cc "
        . "INNER JOIN ciniki_fatt_certs AS certs ON ("
            . "cc.cert_id = certs.id "
//            . ((isset($args['grouping']) && $args['grouping'] != '' )?"AND certs.grouping = '" . ciniki_core_dbQuote($ciniki, $args['grouping']) . "' ":"")
            . "AND certs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "cc.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS parents ON ("
            . "customers.parent_id = parents.id "
            . "AND parents.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE cc.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND cc.date_expiry >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
        . "AND cc.date_expiry <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
        . "ORDER BY days_till_expiry ASC, customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'certs', 'fname'=>'id',
            'fields'=>array('id', 'customer_id', 'display_name', 'name', 'parent_id', 'parent_name', 
                'date_received', 'date_expiry', 'days_till_expiry', 'years_valid')),
        ));
    $certs = array();
    $customer_ids = array();
    if( isset($rc['certs']) ) {
        $certs = $rc['certs'];
        foreach($certs as $cid => $cert) {
            if( $cert['years_valid'] > 0 ) {
                $age = $cert['days_till_expiry'];
                if( $age > 0 ) {
                    $certs[$cid]['expiry_text'] = "Expiring in " . abs($age) . " day" . ($age>1?'s':'');
                } elseif( $age == 0 ) {
                    $certs[$cid]['expiry_text'] = "Expired today";
                } elseif( $age < 0 ) {
                    $certs[$cid]['expiry_text'] = "Expired " . abs($age) . " day" . ($age<1?'s':'') . " ago";
                }
                if( !in_array($cert['customer_id'], $customer_ids) ) {
                    $customer_ids[] = $cert['customer_id'];
                }
            } else {
                $certs[$cid]['date_expiry'] = 'No Expiration';
                $certs[$cid]['expiry_text'] = 'No Expiration';
            }
        }
    }

    //
    // Get the emails and addresses
    //
    if( count($customer_ids) > 0 ) {
        //
        // Get the valid certifications for expiring customers
        //
        $strsql = "SELECT cc.id, "
            . "cc.cert_id, "
            . "cc.customer_id, "
            . "certs.name, "
//            . "certs.years_valid, "
//            . "DATE_FORMAT(cc.date_received, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_received, "
            . "DATE_FORMAT(cc.date_expiry, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_expiry "
//            . "DATEDIFF(cc.date_expiry, '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "') AS days_till_expiry "
            . "FROM ciniki_fatt_cert_customers AS cc "
            . "INNER JOIN ciniki_fatt_certs AS certs ON ("
                . "cc.cert_id = certs.id "
                . "AND certs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE cc.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND cc.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND cc.date_expiry > '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d')) . "' "
            . "ORDER BY cc.customer_id, certs.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'certs', 'fname'=>'id',
                'fields'=>array('id', 'customer_id', 'name', 'date_expiry')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.162', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $valid_certs = isset($rc['customers']) ? $rc['customers'] : array();

        //
        // Get the upcoming registrations for the expiring customers
        //
        $strsql = "SELECT registrations.id, "
            . "registrations.customer_id, "
            . "courses.code, "
            . "DATE_FORMAT(offerings.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS odate "
            . "FROM ciniki_fatt_offering_registrations AS registrations "
            . "INNER JOIN ciniki_fatt_offerings AS offerings ON ("
                . "registrations.offering_id = offerings.id "
                . "AND offerings.start_date >= '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d')) . "' "
                . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_fatt_courses AS courses ON ("
                . "offerings.course_id = courses.id "
                . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE registrations.student_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array('customer_id')),
            array('container'=>'registrations', 'fname'=>'id', 'fields'=>array('id', 'code', 'course_date'=>'odate')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.162', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
        }
        $registrations = isset($rc['customers']) ? $rc['customers'] : array();

        //
        // Get the emails
        //
        $strsql = "SELECT id, customer_id, email "
            . "FROM ciniki_customer_emails "
            . "WHERE customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x10) = 0 " // Only get emails that want to receive emails
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'emails', 'fname'=>'id', 'fields'=>array('email')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $emails = $rc['customers'];

        //
        // Get the addresses
        //
        $strsql = "SELECT id, customer_id, address1, address2, city, province, postal, country "
            . "FROM ciniki_customer_addresses "
            . "WHERE customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $customer_ids) . ") "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (flags&0x04) = 0x04 " // Only get mailing addresses
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.customers', array(
            array('container'=>'customers', 'fname'=>'customer_id', 'fields'=>array()),
            array('container'=>'addresses', 'fname'=>'id', 'fields'=>array('address1', 'address2', 'city', 'province', 'postal', 'country')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $addresses = $rc['customers'];

        //
        // Create the report blocks
        //
        $chunk = array(
            'type'=>'table',
            'columns'=>array(
                array('label'=>'Name', 'pdfwidth'=>'21%', 'field'=>'display_name'),
                array('label'=>'Certificate', 'pdfwidth'=>'13%', 'field'=>'name'),
                array('label'=>'Expiration', 'pdfwidth'=>'14%', 'field'=>'date_expiry'),
                array('label'=>'Valid Certs', 'pdfwidth'=>'26%', 'field'=>'valid_certs'),
                array('label'=>'Upcoming Registration(s)', 'pdfwidth'=>'26%', 'field'=>'registrations'),
//                array('label'=>'Email', 'pdfwidth'=>'30%', 'field'=>'email'),
//                array('label'=>'Address', 'pdfwidth'=>'30%', 'field'=>'address'),
                ),
            'data'=>array(),
            'textlist'=>'',
            );


//
// FIXME: Not currently using merge with customers info, needs to be fixed so will include email, address, employer
//
//        $prev_parent = 0;
        foreach($certs as $cid => $cert) {

            //
            // Check if there is a valid cert for the one expiring
            //
            $cert['valid_certs'] = '';
            if( isset($valid_certs[$cert['customer_id']]['certs']) ) {
                foreach($valid_certs[$cert['customer_id']]['certs'] as $valid_cert) {
                    if( $cert['valid_certs'] != '' ) {
                        $cert['valid_certs'] .= "\n";
                    }
                    $cert['valid_certs'] .= $valid_cert['name'] . ' - ' . $valid_cert['date_expiry'];
                }
            }

            
            //
            // Add registrations to customer
            //
            $cert['registrations'] = '';
            if( isset($registrations[$cert['customer_id']]['registrations']) ) {
                foreach($registrations[$cert['customer_id']]['registrations'] as $reg) {
                    if( $cert['registrations'] != '' ) {
                        $cert['registrations'] .= "\n";
                    }
                    $cert['registrations'] .= $reg['code'] . ' on ' . $reg['course_date'];
                }
            }

            //
            // Add emails to customer
            //
            $chunk['textlist'] .= $cert['display_name'] . "\n";
            $chunk['textlist'] .= $cert['name'] . "\n";
            if( isset($emails[$cert['id']]['emails']) ) {
                foreach($emails[$cert['id']]['emails'] as $email) {
                    $chunk['textlist'] .= $email['email'] . "\n";
                    if( !isset($cert['email']) ) {
                        $cert['email'] = $email['email'];
                    } else {
                        $cert['email'] .= ', ' . $email['email'];
                    }
                }
            }
            //
            // Add addresses to customer
            //
            if( isset($addresses[$cert['id']]['addresses']) ) {
                foreach($addresses[$cert['id']]['addresses'] as $address) {
                    $addr = '';
                    if( isset($address['address1']) && $address['address1'] != '' ) {
                        $addr .= $address['address1'];
                    }
                    if( isset($address['address2']) && $address['address2'] != '' ) {
                        $addr .= ($addr != '' ? "\n" : '') . $address['address2'];
                    }
                    $city = '';
                    if( isset($address['city']) && $address['city'] != '' ) {
                        $city .= $address['city'];
                    }
                    if( isset($address['province']) && $address['province'] != '' ) {
                        $city .= ($city != '' ? ', ' : '') . $address['province'];
                    }
                    if( isset($address['postal']) && $address['postal'] != '' ) {
                        $city .= ($city != '' ? '  ' : '') . $address['postal'];
                    }
                    if( $city != '' ) {
                        $addr .= ($addr != '' ? "\n" : '') . $city;
                    }
                    if( isset($address['country']) && $address['country'] != '' ) {
                        $addr .= ($addr != '' ? "\n" : '') . $address['country'];
                    }
                    if( $addr != '' ) {
                        $chunk['textlist'] .= $addr . "\n";
                        if( !isset($certs[$cid]['address']) ) {
                            $cert['address'] = $addr;
                        } else {
                            $cert['address'] .= "\n" . $addr;
                        }
                    }
                }
            }
            $chunk['textlist'] .= "\n";
            
/*            if( $prev_parent != $cert['parent_id'] ) {
                if( $cert['parent_id'] == 0 ) {
                    $chunk['data'][] = array('display_name'=>'Individuals');
                } else {
                    $chunk['data'][] = array('display_name'=>$cert['parent_name']);
                }
            }  */
            if( $cert['parent_name'] != '' ) {
                $cert['display_name'] .= ' (' . $cert['parent_name'] . ')';
            }
            
            $chunk['data'][] = $cert;
            $prev_parent = $cert['parent_id'];
        }
        $chunks[] = $chunk;
    } 

    //
    // No customers 
    //
    else {
        $chunks[] = array('type'=>'message', 'content'=>'No upcoming certificate expirations in the next ' . ($days == 1 ? 'day' : $days . ' days') . '.');
    }
    
    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
