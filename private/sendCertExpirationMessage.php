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
// tnid:     The ID of the tenant the course is attached to.
// 
function ciniki_fatt_sendCertExpirationMessage($ciniki, $tnid, $args, $tmsupdate=0x07) {

    //
    // FIXME: allow the certcustomer_id passed instead and function will lookup ciniki_fatt_cert_customers.
    // FIXME: allow the message_id passed and function will lookup in ciniki_fatt_messages.
    // FIXME: Setup default for status
    //
    if( !isset($args['certcustomer']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.30', 'msg'=>'No certification customer specified'));
    }
    if( !isset($args['message']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.31', 'msg'=>'No message specified'));
    }

    if( $args['certcustomer']['last_message_day'] == $args['message']['days'] ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.32', 'msg'=>'Message has already been sent.'));
    }
    if( !isset($args['message_status']) ) {
        $args['message_status'] = '10'; // Default to mail queue
    }

    //
    // Load the customer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerEmails');
    $rc = ciniki_customers_hooks_customerEmails($ciniki, $tnid, array('customer_id'=>$args['certcustomer']['customer_id']));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['customer']['emails']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.33', 'msg'=>'Customer does not have any emails.'));
    }
    $customer = $rc['customer'];

    $subject = $args['message']['subject'];
    $html_content = $args['message']['message'];
    $text_content = $args['message']['message'];
    $parent_subject = $args['message']['parent_subject'];
    $parent_html_content = $args['message']['parent_message'];
    $parent_text_content = $args['message']['parent_message'];

    //
    // Run substitutions on message
    //
    $substitutions = array(
        '{_customer_name_}'=>$customer['display_name'],
        '{_employee_name_}'=>$customer['display_name'],
        );

    $parent = NULL;
    if( isset($customer['parent_id']) && $customer['parent_id'] > 0 ) {
        $rc = ciniki_customers_hooks_customerEmails($ciniki, $tnid, array('customer_id'=>$customer['parent_id']));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['customer']['emails']) ) {
            $parent = $rc['customer'];
            $substitutions['{_parent_name_}'] = $parent['display_name'];
            $substitutions['{_employer_name_}'] = $parent['display_name'];
        }
    }
    foreach($substitutions as $keyword => $replacement) {
        $args['message']['subject'] = preg_replace("/$keyword/", $replacement, $args['message']['subject']);
        $args['message']['message'] = preg_replace("/$keyword/", $replacement, $args['message']['message']);
        $args['message']['parent_subject'] = preg_replace("/$keyword/", $replacement, $args['message']['parent_subject']);
        $args['message']['parent_message'] = preg_replace("/$keyword/", $replacement, $args['message']['parent_message']);
    }

    //
    // Start transaction
    //
    if( ($tmsupdate&0x01) == 1 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
        $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.fatt');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    //
    // Lock the certcustomer to make sure nobody else is working on this
    //
    $strsql = "UPDATE ciniki_fatt_cert_customers "  
        . "SET last_message_day = '" . ciniki_core_dbQuote($ciniki, $args['message']['days']) . "' "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['certcustomer']['id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND last_message_day <> '" . ciniki_core_dbQuote($ciniki, $args['message']['days']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }
    if( $rc['num_affected_rows'] < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.34', 'msg'=>'Unable to lock the cert customer.'));
    }

    //
    // Send email
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
    foreach($customer['emails'] as $customer_email) {
        $email_args = array(
            'customer_id'=>$customer['id'],
            'customer_name'=>$customer['display_name'],
            'customer_email'=>$customer_email['address'],
            'subject'=>$args['message']['subject'],
            'html_content'=>$args['message']['message'],
            'text_content'=>$args['message']['message'],
            'status'=>$args['message_status'],
            'object'=>'ciniki.fatt.message',
            'object_id'=>$args['message']['id'],
            );

        //
        // Add to pending mail
        //
        $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, $email_args);
        if( $rc['stat'] != 'ok' ) {
            $rsp = array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.35', 'msg'=>'Unable to send customer message', 'err'=>$rc['err']));
            // Remove "lock" and reset the last_message_day so can try again
            $strsql = "UPDATE ciniki_fatt_cert_customers "  
                . "SET last_message_day = '" . ciniki_core_dbQuote($ciniki, $args['certcustomer']['last_message_day']) . "' "
                . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['certcustomer']['id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND last_message_day = '" . ciniki_core_dbQuote($ciniki, $args['message']['days']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.fatt');
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                return $rc;
            }
            if( $rc['num_affected_rows'] < 1 ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.36', 'msg'=>'Unable to unlock the cert customer.'));
            }
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rsp;
        }
    }

    if( isset($customer['parent_id']) && $customer['parent_id'] > 0 && isset($parent) && $parent != NULL 
        && $args['message']['parent_subject'] != '' && $args['message']['parent_message'] != '' 
        ) {
        foreach($parent['emails'] as $customer_email) {
            $email_args = array(
                'customer_id'=>$parent['id'],
                'customer_name'=>$parent['display_name'],
                'customer_email'=>$customer_email['address'],
                'subject'=>$args['message']['parent_subject'],
                'html_content'=>$args['message']['parent_message'],
                'text_content'=>$args['message']['parent_message'],
                'status'=>$args['message_status'],
                'object'=>'ciniki.fatt.message',
                'object_id'=>$args['message']['id'],
                );
            //
            // Add to pending mail
            //
            $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, $email_args);
            if( $rc['stat'] != 'ok' ) {
                $rsp = array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.37', 'msg'=>'Unable to send parent message', 'err'=>$rc['err']));
                // Remove "lock" and reset the last_message_day so can try again
                $strsql = "UPDATE ciniki_fatt_cert_customers "  
                    . "SET last_message_day = '" . ciniki_core_dbQuote($ciniki, $args['certcustomer']['last_message_day']) . "' "
                    . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['certcustomer']['id']) . "' "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                    . "AND last_message_day = '" . ciniki_core_dbQuote($ciniki, $args['message']['days']) . "' "
                    . "";
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
                $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.fatt');
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                    return $rc;
                }
                if( $rc['num_affected_rows'] < 1 ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.38', 'msg'=>'Unable to unlock the cert customer.'));
                }
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                return $rsp;
            }
        }
    }

    //
    // Commit the transaction
    //
    if( ($tmsupdate&0x01) == 1 ) {
        $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.fatt');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }
            
    return array('stat'=>'ok');
}
?>
