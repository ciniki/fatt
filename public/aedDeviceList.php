<?php
//
// Description
// -----------
// This method will return the list of AEDs for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to get AED for.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.aedDeviceList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
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
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "WHERE ciniki_fatt_aeds.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "";
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
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'device';
                    }
                    $lowest_expiration = $aed['device_expiration_days'];
                }
                $aeds[$aid]['device_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['device_expiration_days']);
            }
            if( $aed['primary_battery_expiration_days'] <= $lowest_expiration ) {
                if( $aed['primary_battery_expiration_days'] < $lowest_expiration ) {
                    $aeds[$aid]['expiring_pieces'] = 'battery';
                } elseif( strstr($aeds[$aid]['expiring_pieces'], 'battery') === false ) {
                    $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'battery';
                }
                $lowest_expiration = $aed['primary_battery_expiration_days'];
            }
            $aeds[$aid]['primary_battery_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['primary_battery_expiration_days']);
            if( ($aed['flags']&0x04) == 0x04 ) {
                if( $aed['secondary_battery_expiration_days'] <= $lowest_expiration ) {
                    if( $aed['secondary_battery_expiration_days'] < $lowest_expiration ) {
                        $aeds[$aid]['expiring_pieces'] = 'battery';
                    } elseif( strstr($aeds[$aid]['expiring_pieces'], 'batter') === false ) {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'battery';
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
                        $aeds[$aid]['expiring_pieces'] = 'pads';
                    } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'pads';
                    }
                    $lowest_expiration = $aed['primary_adult_pads_expiration_days'];
                }
                $aeds[$aid]['primary_adult_pads_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['primary_adult_pads_expiration_days']);
            }
            if( ($aed['flags']&0x20) == 0x20 ) {
                if( $aed['secondary_adult_pads_expiration_days'] <= $lowest_expiration ) {
                    if( $aed['secondary_adult_pads_expiration_days'] < $lowest_expiration ) {
                        $aeds[$aid]['expiring_pieces'] = 'pads';
                    } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'pads';
                    }
                    $lowest_expiration = $aed['secondary_adult_pads_expiration_days'];
                }
                $aeds[$aid]['secondary_adult_pads_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['secondary_adult_pads_expiration_days']);
            }
            if( ($aed['flags']&0x0100) == 0x0100 ) {
                if( $aed['primary_child_pads_expiration_days'] <= $lowest_expiration ) {
                    if( $aed['primary_child_pads_expiration_days'] < $lowest_expiration ) {
                        $aeds[$aid]['expiring_pieces'] = 'pads';
                    } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'pads';
                    }
                    $lowest_expiration = $aed['primary_child_pads_expiration_days'];
                }
                $aeds[$aid]['primary_child_pads_expiration_days_text'] = ciniki_fatt_aedFormatExpirationAge($ciniki, $aed['primary_child_pads_expiration_days']);
            }
            if( ($aed['flags']&0x0200) == 0x0200 ) {
                if( $aed['secondary_child_pads_expiration_days'] < $lowest_expiration ) {
                    if( $aed['secondary_child_pads_expiration_days'] < $lowest_expiration ) {
                        $aeds[$aid]['expiring_pieces'] = 'pads';
                    } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                        $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'pads';
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
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'], array('customer_id'=>$aed['customer_id'], 'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['customer'] = $rc['customer'];
        $rsp['customer_details'] = $rc['details'];
    }
    
    return $rsp;
}
?>
