<?php
//
// Description
// -----------
// This method will return the list of AEDs for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get AED for.
//
// Returns
// -------
//
function ciniki_fatt_aedDeviceList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'output'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Output'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.aedDeviceList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

//    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');
    $date_format = "%b %e, %Y";

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $dt = new DateTime('now', new DateTimeZone($intl_timezone));
    $today = $dt->format('Y-m-d');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'aedFormatExpirationAge');

    //
    // Get the list of aeds
    //
    $strsql = "SELECT ciniki_fatt_aeds.id, "
        . "ciniki_fatt_aeds.customer_id, "
        . "IFNULL(ciniki_customers.display_name, 'Unregistered') AS display_name, "
        . "ciniki_fatt_aeds.location, "
        . "ciniki_fatt_aeds.status, "
        . "ciniki_fatt_aeds.flags, "
        . "ciniki_fatt_aeds.make, "
        . "ciniki_fatt_aeds.model, "
        . "ciniki_fatt_aeds.serial, "
        . "ciniki_fatt_aeds.device_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.device_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS device_expiration_days, "
        . "IFNULL(DATE_FORMAT(ciniki_fatt_aeds.device_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS device_expiration_text, "
        . "ciniki_fatt_aeds.primary_battery_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.primary_battery_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS primary_battery_expiration_days, "
        . "IFNULL(DATE_FORMAT(ciniki_fatt_aeds.primary_battery_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS primary_battery_expiration_text, "
        . "ciniki_fatt_aeds.secondary_battery_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.secondary_battery_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS secondary_battery_expiration_days, "
        . "IFNULL(DATE_FORMAT(ciniki_fatt_aeds.secondary_battery_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS secondary_battery_expiration_text, "
        . "ciniki_fatt_aeds.primary_adult_pads_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.primary_adult_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS primary_adult_pads_expiration_days, "
        . "IFNULL(DATE_FORMAT(ciniki_fatt_aeds.primary_adult_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS primary_adult_pads_expiration_text, "
        . "ciniki_fatt_aeds.secondary_adult_pads_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.secondary_adult_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS secondary_adult_pads_expiration_days, "
        . "IFNULL(DATE_FORMAT(ciniki_fatt_aeds.secondary_adult_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS secondary_adult_pads_expiration_text, "
        . "ciniki_fatt_aeds.primary_child_pads_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.primary_child_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS primary_child_pads_expiration_days, "
        . "IFNULL(DATE_FORMAT(ciniki_fatt_aeds.primary_child_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS primary_child_pads_expiration_text, "
        . "ciniki_fatt_aeds.secondary_child_pads_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.secondary_child_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS secondary_child_pads_expiration_days, "
        . "IFNULL(DATE_FORMAT(ciniki_fatt_aeds.secondary_child_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS secondary_child_pads_expiration_text "
        . "FROM ciniki_fatt_aeds "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_fatt_aeds.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_fatt_aeds.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['status']) && $args['status'] != '' ) {
        $strsql .= "AND ciniki_fatt_aeds.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    if( isset($args['customer_id']) && $args['customer_id'] != '' ) {
        $strsql .= "AND ciniki_fatt_aeds.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "ORDER BY ciniki_fatt_aeds.customer_id ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'aeds', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name', 'location', 'status', 'flags', 'make', 'model', 'serial', 
                'device_expiration', 'device_expiration_text', 'device_expiration_days', 
                'primary_battery_expiration', 'primary_battery_expiration_text', 'primary_battery_expiration_days', 
                'secondary_battery_expiration', 'secondary_battery_expiration_text', 'secondary_battery_expiration_days', 
                'primary_adult_pads_expiration', 'primary_adult_pads_expiration_text', 'primary_adult_pads_expiration_days', 
                'secondary_adult_pads_expiration', 'secondary_adult_pads_expiration_text', 'secondary_adult_pads_expiration_days', 
                'primary_child_pads_expiration', 'primary_child_pads_expiration_text', 'primary_child_pads_expiration_days', 
                'secondary_child_pads_expiration', 'secondary_child_pads_expiration_text', 'secondary_child_pads_expiration_days',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['aeds']) ) {
        $aeds = $rc['aeds'];
        foreach($aeds as $aid => $aed) {
            $warranty_device = ($aed['flags']&0x010000) > 0 ? '*' : '';
            $warranty_batteries = ($aed['flags']&0x020000) > 0 ? '*' : '';
            $warranty_pads = ($aed['flags']&0x040000) > 0 ? '*' : '';
            $aeds[$aid]['device_expiration_text'] = ($aeds[$aid]['device_expiration_text'] != '' ? $warranty_device . $aeds[$aid]['device_expiration_text'] : '');
            $aeds[$aid]['primary_battery_expiration_text'] = ($aeds[$aid]['primary_battery_expiration_text'] != '' ? $warranty_batteries . $aeds[$aid]['primary_battery_expiration_text'] : '');
            $aeds[$aid]['secondary_battery_expiration_text'] = ($aeds[$aid]['secondary_battery_expiration_text'] != '' ? $warranty_batteries . $aeds[$aid]['secondary_battery_expiration_text'] : '');
            $aeds[$aid]['primary_adult_pads_expiration_text'] = ($aeds[$aid]['primary_adult_pads_expiration_text'] != '' ? $warranty_pads . $aeds[$aid]['primary_adult_pads_expiration_text'] : '');
            $aeds[$aid]['secondary_adult_pads_expiration_text'] = ($aeds[$aid]['secondary_adult_pads_expiration_text'] != '' ? $warranty_pads . $aeds[$aid]['secondary_adult_pads_expiration_text'] : '');
            $aeds[$aid]['primary_child_pads_expiration_text'] = ($aeds[$aid]['primary_child_pads_expiration_text'] != '' ? $warranty_pads . $aeds[$aid]['primary_child_pads_expiration_text'] : '');
            $aeds[$aid]['secondary_child_pads_expiration_text'] = ($aeds[$aid]['secondary_child_pads_expiration_text'] != '' ? $warranty_pads . $aeds[$aid]['secondary_child_pads_expiration_text'] : '');
            $aeds[$aid]['warranty_device'] = $warranty_device;
            $aeds[$aid]['warranty_batteries'] = $warranty_batteries;
            $aeds[$aid]['warranty_pads'] = $warranty_pads;
            $aeds[$aid]['alert_level'] = 'green';       // Default to everything ok
            $aeds[$aid]['expiring_pieces'] = '';
            $lowest_expiration = 999999;                    // Number of days until the first piece of equipment expires
            $aeds[$aid]['device_expiration_days_text'] = '';
            $aeds[$aid]['primary_battery_expiration_days_text'] = '';
            $aeds[$aid]['secondary_battery_expiration_days_text'] = '';
            $aeds[$aid]['primary_adult_pads_expiration_days_text'] = '';
            $aeds[$aid]['secondary_adult_pads_expiration_days_text'] = '';
            $aeds[$aid]['primary_child_pads_expiration_days_text'] = '';
            $aeds[$aid]['secondary_child_pads_expiration_days_text'] = '';
            if( ($aed['flags']&0x01) == 0x01 ) {
                if( $aed['device_expiration_days'] <= $lowest_expiration ) {
                    if( strstr($aeds[$aid]['expiring_pieces'], 'device') === false ) {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . $warranty_device . 'device';
                    }
                    $lowest_expiration = $aed['device_expiration_days'];
                }
                $aeds[$aid]['device_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['device_expiration_days']);
            }
            if( $aed['primary_battery_expiration_days'] <= $lowest_expiration ) {
                if( $aed['primary_battery_expiration_days'] < $lowest_expiration ) {
                    $aeds[$aid]['expiring_pieces'] = $warranty_batteries . 'battery';
                } elseif( strstr($aeds[$aid]['expiring_pieces'], 'battery') === false ) {
                    $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . $warranty_batteries . 'battery';
                }
                $lowest_expiration = $aed['primary_battery_expiration_days'];
            }
            $aeds[$aid]['primary_battery_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['primary_battery_expiration_days']);
            if( ($aed['flags']&0x04) == 0x04 ) {
                if( $aed['secondary_battery_expiration_days'] <= $lowest_expiration ) {
                    if( $aed['secondary_battery_expiration_days'] < $lowest_expiration ) {
                        $aeds[$aid]['expiring_pieces'] = $warranty_batteries . 'battery';
                    } elseif( strstr($aeds[$aid]['expiring_pieces'], 'batter') === false ) {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . $warranty_batteries . 'battery';
                    } else {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . str_replace($aeds[$aid]['expiring_pieces'], 'batteries', 'battery');
                    }
                    $lowest_expiration = $aed['secondary_battery_expiration_days'];
                }
                $aeds[$aid]['secondary_battery_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['secondary_battery_expiration_days']);
            }
            if( ($aed['flags']&0x10) == 0x10 ) {
                if( $aed['primary_adult_pads_expiration_days'] <= $lowest_expiration ) {
                    if( $aed['primary_adult_pads_expiration_days'] < $lowest_expiration ) {
                        $aeds[$aid]['expiring_pieces'] = $warranty_pads . 'pads';
                    } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . $warranty_pads . 'pads';
                    }
                    $lowest_expiration = $aed['primary_adult_pads_expiration_days'];
                }
                $aeds[$aid]['primary_adult_pads_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['primary_adult_pads_expiration_days']);
            }
            if( ($aed['flags']&0x20) == 0x20 ) {
                if( $aed['secondary_adult_pads_expiration_days'] <= $lowest_expiration ) {
                    if( $aed['secondary_adult_pads_expiration_days'] < $lowest_expiration ) {
                        $aeds[$aid]['expiring_pieces'] = $warranty_pads . 'pads';
                    } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . $warranty_pads . 'pads';
                    }
                    $lowest_expiration = $aed['secondary_adult_pads_expiration_days'];
                }
                $aeds[$aid]['secondary_adult_pads_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['secondary_adult_pads_expiration_days']);
            }
            if( ($aed['flags']&0x0100) == 0x0100 ) {
                if( $aed['primary_child_pads_expiration_days'] <= $lowest_expiration ) {
                    if( $aed['primary_child_pads_expiration_days'] < $lowest_expiration ) {
                        $aeds[$aid]['expiring_pieces'] = $warranty_pads . 'pads';
                    } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . $warranty_pads . 'pads';
                    }
                    $lowest_expiration = $aed['primary_child_pads_expiration_days'];
                }
                $aeds[$aid]['primary_child_pads_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['primary_child_pads_expiration_days']);
            }
            if( ($aed['flags']&0x0200) == 0x0200 ) {
                if( $aed['secondary_child_pads_expiration_days'] < $lowest_expiration ) {
                    if( $aed['secondary_child_pads_expiration_days'] < $lowest_expiration ) {
                        $aeds[$aid]['expiring_pieces'] = $warranty_pads . 'pads';
                    } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . $warranty_pads . 'pads';
                    }
                    $lowest_expiration = $aed['secondary_child_pads_expiration_days'];
                }
                $aeds[$aid]['secondary_child_pads_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['secondary_child_pads_expiration_days']);
            }

            //
            // Determine alert level
            //
            if( $lowest_expiration <= 30 ) {
                $aeds[$aid]['alert_level'] = 'red';
            } elseif( $lowest_expiration <= 90 ) {
                $aeds[$aid]['alert_level'] = 'orange';
            }

            $aeds[$aid]['expiration_days'] = $lowest_expiration;
            $aeds[$aid]['expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $lowest_expiration);
/*            if( $lowest_expiration > 1 ) {
                $aeds[$aid]['expiration_days_text'] = $lowest_expiration . ' days';
            } elseif( $lowest_expiration == 1 ) {
                $aeds[$aid]['expiration_days_text'] = 'tomorrow';
            } elseif( $lowest_expiration == 0 ) {
                $aeds[$aid]['expiration_days_text'] = 'today';
            } elseif( $lowest_expiration < 0 ) {
                $aeds[$aid]['expiration_days_text'] = abs($lowest_expiration) . ' days ago';
            } */
        }
        //
        // Sort aeds based on expiration_days then company
        //
        usort($aeds, function($a, $b) {
            if( $a['expiration_days'] == $b['expiration_days'] ) { 
                return 0; 
            }
            return $a['expiration_days'] < $b['expiration_days'] ? -1 : 1;
        });
    } else {
        $aeds = array();
    }

    $rsp = array('stat'=>'ok', 'aeds'=>$aeds);

    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        //
        // Get the customer details
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], array('customer_id'=>$aed['customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['customer'] = $rc['customer'];
        $rsp['customer_details'] = $rc['details'];
    }

    if( isset($args['output']) && $args['output'] == 'excel' ) {
        require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

        $col = 0;
        $row = 1;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Company', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Location', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Make', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Model', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Serial', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Device', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Battery (A)', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Battery (B)', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Adult Pads (A)', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Adult Pads (B)', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Child Pads (A)', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Child Pads (B)', false);

        $objPHPExcelWorksheet->getStyle('A1:L1')->getFont()->setBold(true);
      
        $row++;
        foreach($aeds as $aed) {
            $col = 0;
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['display_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['location'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['make'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['model'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['serial'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['device_expiration_text'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['primary_battery_expiration_text'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['secondary_battery_expiration_text'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['primary_adult_pads_expiration_text'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['secondary_adult_pads_expiration_text'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['primary_child_pads_expiration_text'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $aed['secondary_child_pads_expiration_text'], false);
            $row++;
        }

        $objPHPExcelWorksheet->getColumnDimension('A')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('B')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('C')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('D')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('E')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('F')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('G')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('H')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('I')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('J')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('K')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('L')->setAutoSize(true);
        $objPHPExcelWorksheet->freezePane('A2');

        $filename = 'AEDs';

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    if( isset($args['output']) && $args['output'] == 'pdf' ) {
        
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'templates', 'aeds');
        $rc = ciniki_fatt_templates_aeds($ciniki, $args['tnid'], $aeds);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output($rc['filename'], 'D');
            return array('stat'=>'exit');
        }
    }
    
    return $rsp;
}
?>
