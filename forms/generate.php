<?php
//
// Description
// -----------
// This function generates a PDF of the forms required for the offering(s).
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
//
function ciniki_fatt_forms_generate($ciniki, $tnid, $args) {

    if( !isset($args['offering_ids']) || !is_array($args['offering_ids']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.6', 'msg'=>'No offering(s) specified'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');

    //
    // Load the forms
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'forms', 'list');
    $rc = ciniki_fatt_forms_list($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['cover_letters']) ) {
        $forms = array_merge($rc['forms'], $rc['cover_letters']);
    } else {
        $forms = $rc['forms'];
    }

    //
    // Load tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'tenantDetails');
    $rc = ciniki_tenants_hooks_tenantDetails($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Load timezone
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $ltz = new DateTimeZone($intl_timezone);

    //
    // Get the list of registrations for the offering(s), if any.
    //
    $strsql = "SELECT ciniki_fatt_offerings.course_id, "
        . "ciniki_fatt_offering_registrations.offering_id, "
        . "ciniki_fatt_offering_registrations.customer_id, "
        . "ciniki_fatt_offering_registrations.student_id, "
        . "ciniki_fatt_courses.name, "
        . "ciniki_fatt_courses.cover_letter, "
        . "ciniki_fatt_courses.cert_form1, "
        . "ciniki_fatt_courses.cert_form2 "
        . "FROM ciniki_fatt_offerings "
        . "INNER JOIN ciniki_fatt_courses ON ("
            . "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
            . "AND ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_fatt_offering_registrations ON ("
            . "ciniki_fatt_offerings.id = ciniki_fatt_offering_registrations.offering_id "
            . "AND ciniki_fatt_offering_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_fatt_offering_registrations.status = 10 "
            . ") "
        . "WHERE ciniki_fatt_offerings.id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['offering_ids']) . ") "
        . "AND ciniki_fatt_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY cover_letter DESC, cert_form1, cert_form2 "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'offering');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.7', 'msg'=>'No registrations found'));
    }
    $registrations = $rc['rows'];

    //
    // Get the last date of the offering, and the location
    //
    $strsql = "SELECT ciniki_fatt_offering_dates.offering_id, "
        . "ciniki_fatt_offering_dates.day_number, "
        . "ciniki_fatt_offering_dates.start_date, "
//        . "ciniki_fatt_offering_dates.start_date AS year, "
//        . "ciniki_fatt_offering_dates.start_date AS month, "
//        . "ciniki_fatt_offering_dates.start_date AS day, "
        . "IF(location_id > 0 && (ciniki_fatt_locations.flags&0x01) = 0, ciniki_fatt_locations.address1, ciniki_fatt_offering_dates.address1) AS address1, "
        . "IF(location_id > 0 && (ciniki_fatt_locations.flags&0x01) = 0, ciniki_fatt_locations.address2, ciniki_fatt_offering_dates.address2) AS address2, "
        . "IF(location_id > 0 && (ciniki_fatt_locations.flags&0x01) = 0, ciniki_fatt_locations.city, ciniki_fatt_offering_dates.city) AS city, "
        . "IF(location_id > 0 && (ciniki_fatt_locations.flags&0x01) = 0, ciniki_fatt_locations.province, ciniki_fatt_offering_dates.province) AS province, "
        . "IF(location_id > 0 && (ciniki_fatt_locations.flags&0x01) = 0, ciniki_fatt_locations.postal, ciniki_fatt_offering_dates.postal) AS postal "
        . "FROM ciniki_fatt_offering_dates "
        . "LEFT JOIN ciniki_fatt_locations ON ("
            . "ciniki_fatt_offering_dates.location_id = ciniki_fatt_locations.id "
            . "AND ciniki_fatt_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_fatt_offering_dates.offering_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['offering_ids']) . ") "
        . "AND ciniki_fatt_offering_dates.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY ciniki_fatt_offering_dates.offering_id, ciniki_fatt_offering_dates.day_number DESC "
        . "";
    // Because query is sorted by day_number, automatically last item will be put in the result.
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'offerings', 'fname'=>'offering_id', 
            'fields'=>array('day_number', 'start_date', 'address1', 'address2', 'city', 'province', 'postal'), 
            'utctotz'=>array('start_date'=>array('format'=>'Y-m-d', 'timezone'=>$intl_timezone)),
            )
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['offerings']) || count($rc['offerings']) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.8', 'msg'=>'No dates available for offerings.'));
    }
    $dates = $rc['offerings'];

    //
    // Setup the registration information
    //
    $reg_forms = array();

    //
    // Assign to a form
    //
    foreach($registrations as $reg_id => $reg) {
        for($i = 0; $i <= 2; $i++) {
            if( $i == 0 ) {
                $cert_form_name = 'cover_letter';
            } else {
                $cert_form_name = 'cert_form' . $i;
            }

            //
            // Skip if no form specified
            //
            if( $reg[$cert_form_name] == '' || !isset($forms[$reg[$cert_form_name]]) ) {
                continue;
            }
            $sdt = new DateTime($dates[$reg['offering_id']]['start_date'] . ' 00:00:00', $ltz);

            $form_name = $reg[$cert_form_name];
            if( !isset($reg_forms[$form_name]) ) {
                $reg_forms[$form_name] = $forms[$form_name];
                $reg_forms[$form_name]['registrations'] = array();
                $reg_forms[$form_name]['exam_date'] = $sdt;
                $reg_forms[$form_name]['location'] = $dates[$reg['offering_id']]['city'] . ', ' . $dates[$reg['offering_id']]['province'];
                $reg_forms[$form_name]['host_name'] = isset($tenant_details['name']) ? $tenant_details['name'] : '';
                if( preg_match("/\(?([0-9][0-9][0-9])\)?-([0-9][0-9][0-9]).*([0-9][0-9][0-9][0-9])/", $tenant_details['contact.phone.number'], $matches) ) {
                    $reg_forms[$form_name]['host_area_code'] = $matches[1];
                    $reg_forms[$form_name]['host_phone'] = $matches[2] . '-' . $matches[3];
                } else {
                    $reg_forms[$form_name]['host_area_code'] = '';
                    $reg_forms[$form_name]['host_phone'] = '';
                }
                $reg_forms[$form_name]['host_street'] = isset($tenant_details['contact.address.street1']) ? $tenant_details['contact.address.street1'] : '';
                $reg_forms[$form_name]['host_city'] = isset($tenant_details['contact.address.city']) ? $tenant_details['contact.address.city'] : '';
                $reg_forms[$form_name]['host_province'] = isset($tenant_details['contact.address.province']) ? $tenant_details['contact.address.province'] : '';
                $reg_forms[$form_name]['host_postal'] = isset($tenant_details['contact.address.postal']) ? $tenant_details['contact.address.postal'] : '';

                //
                // Get the list of instructors for the course
                //
                $strsql = "SELECT ciniki_fatt_instructors.name, "
                    . "ciniki_fatt_instructors.id_number, "
                    . "ciniki_fatt_instructors.email, "
                    . "ciniki_fatt_instructors.phone "
                    . "FROM ciniki_fatt_offering_instructors "
                    . "INNER JOIN ciniki_fatt_instructors ON ("
                        . "ciniki_fatt_offering_instructors.instructor_id = ciniki_fatt_instructors.id "
                        . "AND ciniki_fatt_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                        . ") "
                    . "WHERE ciniki_fatt_offering_instructors.offering_id = '" . ciniki_core_dbQuote($ciniki, $reg['offering_id']) . "' "
                    . "AND ciniki_fatt_offering_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "LIMIT 1 "
                    . "";
                $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'instructor');
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $reg_forms[$form_name]['instructor_name'] = '';
                $reg_forms[$form_name]['instructor_id'] = '';
                $reg_forms[$form_name]['instructor_email'] = '';
                $reg_forms[$form_name]['instructor_area_code'] = '';
                $reg_forms[$form_name]['instructor_phone'] = '';
                $reg_forms[$form_name]['examiner_name'] = '';
                $reg_forms[$form_name]['examiner_id'] = '';
                $reg_forms[$form_name]['examiner_email'] = '';
                $reg_forms[$form_name]['examiner_area_code'] = '';
                $reg_forms[$form_name]['examiner_phone'] = '';
                if( isset($rc['instructor']) ) {
                    $reg_forms[$form_name]['instructor_name'] = $rc['instructor']['name'];
                    $reg_forms[$form_name]['instructor_id'] = $rc['instructor']['id_number'];
                    $reg_forms[$form_name]['instructor_email'] = $rc['instructor']['email'];
                    if( preg_match("/\(?([0-9][0-9][0-9])\)?-([0-9][0-9][0-9]).*([0-9][0-9][0-9][0-9])/", $rc['instructor']['phone'], $matches) ) {
                        $reg_forms[$form_name]['instructor_area_code'] = $matches[1];
                        $reg_forms[$form_name]['instructor_phone'] = $matches[2] . '-' . $matches[3];
                    }
                }
                // 
                // Duplicated for now, but when a separate examiner is required, it can be changed below
                //
                if( isset($rc['instructor']) ) {
                    $reg_forms[$form_name]['examiner_name'] = $rc['instructor']['name'];
                    $reg_forms[$form_name]['examiner_id'] = $rc['instructor']['id_number'];
                    $reg_forms[$form_name]['examiner_email'] = $rc['instructor']['email'];
                    if( preg_match("/\(?([0-9][0-9][0-9])\)?-([0-9][0-9][0-9]).*([0-9][0-9][0-9][0-9])/", $rc['instructor']['phone'], $matches) ) {
                        $reg_forms[$form_name]['examiner_area_code'] = $matches[1];
                        $reg_forms[$form_name]['examiner_phone'] = $matches[2] . '-' . $matches[3];
                    }
                }
            }

            //
            // Get the customer details
            //
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, array('customer_id'=>$reg['student_id'], 'addresses'=>'yes', 'phones'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['customer']) ) {
                $registrations[$reg_id]['display_name'] = $rc['customer']['display_name'];

                $registrations[$reg_id]['age'] = '';
                $age = '';
                $registrations[$reg_id]['birthyear'] = '';
                $registrations[$reg_id]['birthmonth'] = '';
                $registrations[$reg_id]['birthday'] = '';
                if( isset($rc['customer']['birthdate']) && $rc['customer']['birthdate'] != '0000-00-00' ) {
                    $bdt = new DateTime($rc['customer']['birthdate'] . ' 00:00:00', $ltz);
                    $registrations[$reg_id]['birthyear'] = $bdt->format('y');
                    $registrations[$reg_id]['birthmonth'] = $bdt->format('m');
                    $registrations[$reg_id]['birthday'] = $bdt->format('d');
                    $b = date_diff($sdt, $bdt);
                    if( $b->format('%y') > 0 ) {
                        $registrations[$reg_id]['age'] = $b->format('%y');
                    }
                }

                //
                // Setup address
                //
                $registrations[$reg_id]['address'] = '';
                $registrations[$reg_id]['apt'] = '';
                $registrations[$reg_id]['city'] = '';
                $registrations[$reg_id]['postal'] = '';
                $registrations[$reg_id]['email'] = '';
                $registrations[$reg_id]['phone'] = '';
                if( isset($rc['customer']['addresses']) ) {
                    foreach($rc['customer']['addresses'] as $address) {
                        $address = $address['address'];
                        if( ($address['flags']&0x04) > 0 ) {
                            if( preg_match("/(.*)\s(apt|suite|unit|\#)[\s\.]*([a-zA-Z0-9]+)/i", $address['address1'], $matches) ) {
                                $registrations[$reg_id]['address'] = $matches[1];
                                $registrations[$reg_id]['apt'] = $matches[3];
                            } else {
                                $registrations[$reg_id]['address'] = $address['address1'];
                                if( preg_match("/(apt|suite|unit|\#)[\s\.]*([a-zA-Z0-9]+)/i", $address['address2'], $matches) ) {
                                    $registrations[$reg_id]['apt'] = $matches[2];
                                }
                            }
                            $registrations[$reg_id]['city'] = $address['city'];
                            $registrations[$reg_id]['postal'] = $address['postal'];

                            break;
                        }
                    }
                }
                if( isset($rc['customer']['emails']) ) {
                    foreach($rc['customer']['emails'] as $email) {
                        $email = $email['email'];
                        $registrations[$reg_id]['email'] = $email['address'];
                        break;
                    }
                } 
                if( isset($rc['customer']['phones']) ) {
                    foreach($rc['customer']['phones'] as $phone) {
                        $registrations[$reg_id]['phone'] = $phone['phone_number'];
                        break;
                    }
                } 
            }

            //
            // Get the customer if different from student details
            //
            if( $reg['student_id'] != $reg['customer_id'] ) {
                $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, array('customer_id'=>$reg['customer_id'], 'addresses'=>'yes', 'phones'=>'yes'));
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                if( isset($rc['customer']) && $rc['customer']['type'] == 30 ) {
                    if( !isset($reg_forms[$form_name]['businesses']) ) {
                        $reg_forms[$form_name]['businesses'] = array();
                    }
                    if( !isset($reg_forms[$form_name]['businesses'][$reg['customer_id']]) ) {
                        $reg_forms[$form_name]['businesses'][$reg['customer_id']] = array(
                            'id'=>$reg['customer_id'],
                            'display_name'=>$rc['customer']['display_name'],
                            'address1'=>'',
                            'address1'=>'',
                            'city'=>'',
                            'province'=>'',
                            'postal'=>'',
                            'registrations'=>array(),
                            );
                        if( isset($rc['customer']['addresses']) ) {
                            foreach($rc['customer']['addresses'] as $address) {
                                $address = $address['address'];
                                if( ($address['flags']&0x04) > 0 ) {
                                    $reg_forms[$form_name]['businesses'][$reg['customer_id']]['address1'] = $address['address1'];
                                    $reg_forms[$form_name]['businesses'][$reg['customer_id']]['address2'] = $address['address2'];
                                    $reg_forms[$form_name]['businesses'][$reg['customer_id']]['city'] = $address['city'];
                                    $reg_forms[$form_name]['businesses'][$reg['customer_id']]['province'] = $address['province'];
                                    $reg_forms[$form_name]['businesses'][$reg['customer_id']]['postal'] = $address['postal'];
                                    $reg_forms[$form_name]['businesses'][$reg['customer_id']]['country'] = $address['country'];
                                    break;
                                }
                            }
                        }
                    }
                    $reg_forms[$form_name]['businesses'][$reg['customer_id']]['registrations'][] = $registrations[$reg_id];
                }
            }

            $reg_forms[$form_name]['registrations'][] = $registrations[$reg_id];
        }
    }

    //
    // Setup the PDF
    //
    require_once($ciniki['config']['core']['lib_dir'] . '/tcpdf/tcpdf.php');
    class MYPDF extends TCPDF {
        public function Header() { }
        public function Footer() { }
    }
    //$pdf = new TCPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);
    $pdf = new TCPDF('P', PDF_UNIT, 'LETTER', true, 'ISO-8859-1', false);
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetHeaderMargin(0);
    $pdf->SetFooterMargin(0);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetAutoPageBreak(false);
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
//    $pdf->SetFont('helvetica', '10');

    //
    // Add the forms to the pdf
    //
    foreach($reg_forms as $form_id => $form) {
        $rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'forms', 'process' . $form['processor']);
        if( $rc['stat'] == 'ok' ) {
            $fn = $rc['function_call'];
            $rc = $fn($ciniki, $tnid, $pdf, $form);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok', 'pdf'=>$pdf);
}
?>
