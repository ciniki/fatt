<?php
//
// Description
// ===========
// This function will update the list of certs to which a course is assigned.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the course is attached to.
// 
function ciniki_fatt_cronSendCertExpirationMessages($ciniki, $business_id, $tmsupdate=0x07) {

    // Default delivery time, will need to be a setting in the future.
    $delivery_time = '06:00:00';

    // Default the outgoing messages into pending for approval before sending
    $message_status = 7;

    // Functions required
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessages');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'sendCertExpirationMessage');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');

    //
    // Load the business time zone information
    //
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Get the cert messages
    // Sort by days: if we missed expiration emails, we want the sent the more relavent one, 
    //     not start at 90 days, then immediately send 60, etc.
    //
    $strsql = "SELECT id, object, object_id AS cert_id, "
        . "days, "
        . "subject, message, parent_subject, parent_message "
        . "FROM ciniki_fatt_messages "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND (status = 10 || status = 20) "
        . "AND object = 'ciniki.fatt.cert' "
        . "ORDER BY cert_id, days ASC "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'certs', 'fname'=>'cert_id',
            'fields'=>array('object', 'cert_id')),
        array('container'=>'messages', 'fname'=>'id',
            'fields'=>array('id', 'days', 'subject', 'message', 'parent_subject', 'parent_message')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.18', 'msg'=>'Unable to get messages', 'err'=>$rc['err']));
    }
    if( !isset($rc['certs']) ) {
        return array('stat'=>'ok');
    }
    $cert_messages = $rc['certs'];

    //
    // Setup the current date in the business timezone
    //
    $dt = new DateTime('now', new DateTimeZone($intl_timezone));

    //
    // Get the list of cert customers that need to be checked for messages
    //
    $strsql = "SELECT id, cert_id, flags, customer_id, offering_id, date_received, date_expiry, "
        . "DATEDIFF('" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "', date_expiry) AS days_till_expiry, "
        . "last_message_day, "
        . "next_message_date "
        . "FROM ciniki_fatt_cert_customers "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND date_expiry <> '0000-00-00' "
        . "AND (flags&0x02) = 0 "       // Emails aren't marked as finished yet
        . "AND next_message_date <= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d H:m:s')) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.19', 'msg'=>'Unable to get cert customer expirations', 'err'=>$rc['err']));
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'ok');
    }
    $cert_customers = $rc['rows'];


    //
    // Process the cert customers and check for what messages should be sent
    //
    foreach($cert_customers as $cc) {
        //
        // Check there are cert messages for the customers certification
        //
        if( !isset($cert_messages[$cc['cert_id']]['messages']) ) {
            if( $cc['days_till_expiry'] > 0 ) {
                error_log("CRON: No expiration messages for certification " . $cc['id']);
                if( ($cc['flags']&0x02) == 0 ) {
                    $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.fatt.certcustomer', $cc['id'], 
                        array('flags'=>($cc['flags']|=0x02)), $tmsupdate);
                    if( $rc['stat'] != 'ok' ) {
                        error_log("CRON-ERR: Unable to update customer cert " . $cc['id'] . " for $business_id . (" . serialize($rc['err']) . ")");
                        continue;
                    }
                }
            }
            continue;
        }
        //
        // Double check the flags setting to make sure we are still to send to this customer
        //
        if( ($cc['flags']&0x03) != 0x01 ) {
            continue;
        }

        //
        // Go through cert messages and determine which should be sent
        //
        $cur_message_to_send = NULL;
        $next_message_to_send = NULL;
        foreach($cert_messages[$cc['cert_id']]['messages'] as $message ) {
//            error_log('message ' . $cc['id'] . ' days ' . $cc['days_till_expiry'] . ' message ' . $message['days']);

            //
            // Check if message could be sent. It must be exactly on the day to send
            // as the number of days till expiry is specified in the email.
            //
            if( $message['days'] == $cc['days_till_expiry'] ) {
                $cur_message_to_send = $message;
            }

            //
            // Check if expiry time is still before message days (negative numbers)
            //
            elseif( $message['days'] > $cc['days_till_expiry'] ) {
                $next_message_to_send = $message;
 //               error_log('break');
                break;
            }
            
        }

        //
        // Send the message
        //
        if( $cur_message_to_send != NULL ) {
            //
            // Check to make sure message hasn't already been sent
            //
            $rc = ciniki_mail_hooks_objectMessages($ciniki, $business_id, 
                array('object'=>'ciniki.fatt.message', 'object_id'=>$cur_message_to_send['id'], 'customer_id'=>$cc['customer_id']));
            if( $rc['stat'] != 'ok' ) {
                error_log("CRON-ERR: Unable to get objectMessages for $business_id . (" . serialize($rc['err']) . ")");
            }
            elseif( !isset($rc['messages']) || count($rc['messages']) == 0 ) {
                //
                // Add the message to the customer
                //
                $rc = ciniki_fatt_sendCertExpirationMessage($ciniki, $business_id, 
                    array('certcustomer'=>$cc, 'message'=>$cur_message_to_send, 'message_status'=>$message_status), $tmsupdate);
                if( isset($rc['err']['code']) && $rc['err']['code'] == 'ciniki.fatt.33' ) {
                    //
                    // No emails for customer, mark as finished
                    //
                    error_log("CRON-ERR: No email addresses for customer " . $cc['customer_id']);
                    if( ($cc['flags']&0x02) == 0 ) {
                        $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.fatt.certcustomer', $cc['id'], 
                            array('flags'=>($cc['flags']|=0x02)), $tmsupdate);
                        if( $rc['stat'] != 'ok' ) {
                            error_log("CRON-ERR: Unable to update customer cert " . $cc['id'] . " for $business_id . (" . serialize($rc['err']) . ")");
                            continue;
                        }
                    }
                }
                elseif( $rc['stat'] != 'ok' ) {
                    error_log("CRON-ERR: Unable to send message for $business_id . (" . serialize($rc['err']) . ")");
                    continue;
                }
            }
        }

        //
        // Set the next send date
        //
        if( $next_message_to_send != NULL ) {
            $days_till_next_message = $next_message_to_send['days'] - $cc['days_till_expiry'];
            if( $days_till_next_message > 0 ) {
                $next_dt = clone $dt;
                $next_dt->add(New DateInterval('P' . $days_till_next_message . 'D'));
                $delivery_time_pieces = explode(':', $delivery_time);
                $next_dt->setTime($delivery_time_pieces[0], $delivery_time_pieces[1]);
                $next_dt->setTimezone(new DateTimeZone('UTC'));
                $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.fatt.certcustomer', $cc['id'], 
                    array('next_message_date'=>$next_dt->format('Y-m-d H:i:s')), $tmsupdate);
                if( $rc['stat'] != 'ok' ) {
                    error_log("CRON-ERR: Unable to send message for $business_id . (" . serialize($rc['err']) . ")");
                    continue;
                }
            }
        } 
        //
        // No more messages to send for the customer, turn off notifications
        //
        else {
            error_log("CRON: No more messages for customer " . $cc['customer_id']);
            if( ($cc['flags']&0x02) == 0 ) {
                $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.fatt.certcustomer', $cc['id'], 
                    array('flags'=>($cc['flags']|=0x02)), $tmsupdate);
                if( $rc['stat'] != 'ok' ) {
                    error_log("CRON-ERR: Unable to update customer cert " . $cc['id'] . " for $business_id . (" . serialize($rc['err']) . ")");
                    continue;
                }
            }
        }
    }

    return array('stat'=>'ok');
}
?>
