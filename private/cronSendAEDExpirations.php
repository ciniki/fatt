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
function ciniki_fatt_cronSendAEDExpirations(&$ciniki, $business_id) {

    error_log('Check for AED expirations');
    //
    // Load business settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $ltz = new DateTimeZone($intl_timezone);
    $dt = new DateTime('now', new DateTimeZone($intl_timezone));
    $today = $dt->format('Y-m-d');

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
        . "ciniki_fatt_aeds.primary_battery_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.primary_battery_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS primary_battery_expiration_days, "
        . "ciniki_fatt_aeds.secondary_battery_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.secondary_battery_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS secondary_battery_expiration_days, "
        . "ciniki_fatt_aeds.primary_adult_pads_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.primary_adult_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS primary_adult_pads_expiration_days, "
        . "ciniki_fatt_aeds.secondary_adult_pads_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.secondary_adult_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS secondary_adult_pads_expiration_days, "
        . "ciniki_fatt_aeds.primary_child_pads_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.primary_child_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS primary_child_pads_expiration_days, "
        . "ciniki_fatt_aeds.secondary_child_pads_expiration, "
        . "DATEDIFF(ciniki_fatt_aeds.secondary_child_pads_expiration, '" . ciniki_core_dbQuote($ciniki, $today) . "') AS secondary_child_pads_expiration_days "
        . "FROM ciniki_fatt_aeds "
        . "LEFT JOIN ciniki_customers ON ("
            . "ciniki_fatt_aeds.customer_id = ciniki_customers.id "
            . "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_fatt_aeds.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'aeds', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name', 'location', 'status', 'flags', 'make', 'model', 'serial', 
                'device_expiration', 'device_expiration_days', 
                'primary_battery_expiration', 'primary_battery_expiration_days', 
                'secondary_battery_expiration', 'secondary_battery_expiration_days', 
                'primary_adult_pads_expiration', 'primary_adult_pads_expiration_days', 
                'secondary_adult_pads_expiration', 'secondary_adult_pads_expiration_days', 
                'primary_child_pads_expiration', 'primary_child_pads_expiration_days', 
                'secondary_child_pads_expiration', 'secondary_child_pads_expiration_days',
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
            $lowest_expiration_date = '';
            if( $aed['device_expiration_days'] <= $lowest_expiration ) {
                if( strstr($aeds[$aid]['expiring_pieces'], 'device') === false ) {
                    $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'device';
                }
                $lowest_expiration = $aed['device_expiration_days'];
                $lowest_expiration_date = $aed['device_expiration'];
            }
            if( $aed['primary_battery_expiration_days'] <= $lowest_expiration ) {
                if( $aed['primary_battery_expiration_days'] < $lowest_expiration ) {
                    $aeds[$aid]['expiring_pieces'] = 'battery';
                } elseif( strstr($aeds[$aid]['expiring_pieces'], 'battery') === false ) {
                    $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'battery';
                }
                $lowest_expiration = $aed['primary_battery_expiration_days'];
                $lowest_expiration_date = $aed['primary_battery_expiration'];
            }
            if( ($aed['flags']&0x01) == 0x01 && $aed['secondary_battery_expiration_days'] <= $lowest_expiration ) {
                if( $aed['secondary_battery_expiration_days'] < $lowest_expiration ) {
                    $aeds[$aid]['expiring_pieces'] = 'battery';
                } elseif( strstr($aeds[$aid]['expiring_pieces'], 'batter') === false ) {
                    $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'battery';
                } else {
                    $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . str_replace($aeds[$aid]['expiring_pieces'], 'batteries', 'battery');
                }
                $lowest_expiration = $aed['secondary_battery_expiration_days'];
                $lowest_expiration_date = $aed['secondary_battery_expiration'];
            }
            if( ($aed['flags']&0x10) == 0x10 && $aed['primary_adult_pads_expiration_days'] <= $lowest_expiration ) {
                if( $aed['primary_adult_pads_expiration_days'] < $lowest_expiration ) {
                    $aeds[$aid]['expiring_pieces'] = 'pads';
                } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                    $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'pads';
                }
                $lowest_expiration = $aed['primary_adult_pads_expiration_days'];
                $lowest_expiration_date = $aed['primary_adult_pads_expiration'];
            }
            if( ($aed['flags']&0x20) == 0x20 && $aed['secondary_adult_pads_expiration_days'] <= $lowest_expiration ) {
                if( $aed['secondary_adult_pads_expiration_days'] < $lowest_expiration ) {
                    $aeds[$aid]['expiring_pieces'] = 'pads';
                } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                    $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'pads';
                }
                $lowest_expiration = $aed['secondary_adult_pads_expiration_days'];
                $lowest_expiration_date = $aed['secondary_adult_pads_expiration'];
            }
            if( ($aed['flags']&0x0100) == 0x0100 && $aed['primary_child_pads_expiration_days'] <= $lowest_expiration ) {
                if( $aed['primary_child_pads_expiration_days'] < $lowest_expiration ) {
                    $aeds[$aid]['expiring_pieces'] = 'pads';
                } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                    $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'pads';
                }
                $lowest_expiration = $aed['primary_child_pads_expiration_days'];
                $lowest_expiration_date = $aed['primary_child_pads_expiration'];
            }
            if( ($aed['flags']&0x0200) == 0x0200 && $aed['secondary_child_pads_expiration_days'] < $lowest_expiration ) {
                if( $aed['secondary_child_pads_expiration_days'] < $lowest_expiration ) {
                    $aeds[$aid]['expiring_pieces'] = 'pads';
                } elseif( strstr($aeds[$aid]['expiring_pieces'], 'pads') === false ) {
                    $aeds[$aid]['expiring_pieces'] .= ($aeds[$aid]['expiring_pieces'] != '' ? ', ' : '') . 'pads';
                }
                $lowest_expiration = $aed['secondary_child_pads_expiration_days'];
                $lowest_expiration_date = $aed['secondary_child_pads_expiration'];
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
            $edt = new DateTime($lowest_expiration_date, $ltz);
            $aeds[$aid]['expiration_date'] = $edt->format('M j, Y');

            if( $lowest_expiration > 1 ) {
                $aeds[$aid]['expiration_days_text'] = $lowest_expiration . ' days';
            } elseif( $lowest_expiration == 1 ) {
                $aeds[$aid]['expiration_days_text'] = 'tomorrow';
            } elseif( $lowest_expiration == 0 ) {
                $aeds[$aid]['expiration_days_text'] = 'today';
            } elseif( $lowest_expiration < 0 ) {
                $aeds[$aid]['expiration_days_text'] = abs($lowest_expiration) . ' days ago';
            }
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

    //
    // Create the email to send
    //
    $text_content = '';
    foreach($aeds as $aed) {
        //
        // Check if finished with expirations in the next 120 days
        //
        if( $aed['expiration_days'] > 120 ) { 
            break; 
        }
        
        //
        // Add the AED to the message
        //
        $text_content .= $aed['expiration_date'] . ' (' . $aed['expiration_days_text'] . ') - ' . $aed['display_name'] . "\n"
            . "    " . $aed['expiring_pieces'] . "\n\n";

    }
    
    //
    // If there is expiring AEDs send the email
    //
    if( $text_content != '' ) {
        error_log('emailing aed expirations');
        $text_content = "You have the following upcoming AED Expirations\n\n" . $text_content;
        ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'hooks', 'businessOwners');
        $rc = ciniki_businesses_hooks_businessOwners($ciniki, $business_id, array());
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3481', 'msg'=>'Unable to get business owners', 'err'=>$rc['err']));
        }
        $owners = $rc['users'];
        ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'hooks', 'emailUser');
        foreach($owners as $user_id => $owner) {
            $rc = ciniki_users_hooks_emailUser($ciniki, $business_id, array('user_id'=>$user_id,
                'subject'=>'AED Expirations',
                'textmsg'=>$text_content,
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }
    
    //
    // Updating of the next date is done by the cron/jobs.php script
    //
    return array('stat'=>'ok');    
}
?>
