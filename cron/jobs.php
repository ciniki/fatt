<?php
//
// Description
// -----------
// This function checks the certification expirations for any expiration messages that should be sent.
//
// Arguments
// ---------
// ciniki:
// 
// Returns
// -------
//
function ciniki_fatt_cron_jobs(&$ciniki) {
    ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for fatt jobs', 'severity'=>'5'));
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');

    //
    // Get the list of tenants which have fatt certification expiration messages to be sent out
    //
    $strsql = "SELECT DISTINCT tnid "
        . "FROM ciniki_fatt_cert_customers "
        . "WHERE next_message_date <= UTC_TIMESTAMP() "
        . "AND date_expiry <> '0000-00-00' "
        . "AND (flags&0x03) = 0x01 "    // Certifications that still have messages to go out, and aren't finished
        . "";
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.fatt', 'tenants', 'tnid');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.1', 'msg'=>'Unable to get list of tenants with FATT certifications', 'err'=>$rc['err']));
    }
    if( !isset($rc['tenants']) || count($rc['tenants']) == 0 ) {
        $tenants = array();
    } else {
        $tenants = $rc['tenants'];
    }

    //
    // For each tenant, load their mail settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'cronSendCertExpirationMessages');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    foreach($tenants as $tnid) {
        ciniki_cron_logMsg($ciniki, $tnid, array('code'=>'0', 'msg'=>'Sending certfication expiration messages', 'severity'=>'10'));

        //
        // Process the emails for the tenant
        //
        $rc = ciniki_fatt_cronSendCertExpirationMessages($ciniki, $tnid, 0x07);
        if( $rc['stat'] != 'ok' ) {
            ciniki_cron_logMsg($ciniki, $tnid, array('code'=>'ciniki.fatt.145', 'msg'=>'Unable to send certification expiration messages', 
                'severity'=>50, 'err'=>$rc['err']));
            continue;
        }
    }

    //
    // Get the list of tenants which have aed expirations and want a message
    //
    $strsql = "SELECT DISTINCT s1.tnid, s2.detail_key, s2.detail_value "
        . "FROM ciniki_fatt_settings AS s1, ciniki_fatt_settings AS s2 "
        . "WHERE s1.detail_key = 'aeds-expirations-message-enabled' "
        . "AND s1.detail_value = 'yes' "
        . "AND s1.tnid = s2.tnid "
        . "AND s2.detail_key = 'aeds-expirations-message-next' "
        . "AND s2.detail_value <= UTC_TIMESTAMP() "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'tenant');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.2', 'msg'=>'Unable to get list of tenants with AED expiration messages', 'err'=>$rc['err']));
        return $rc;
    }
    if( isset($rc['rows']) ) {
        $tenants = $rc['rows'];
    } else {
        $tenants = array();
    }

    //
    // For each tenant, load their mail settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'cronSendAEDExpirations');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    foreach($tenants as $tenant) {
        ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'0', 'msg'=>'Sending AED expiration message', 'severity'=>'10'));

        //
        // Process the emails for the tenant
        //
        $rc = ciniki_fatt_cronSendAEDExpirations($ciniki, $tenant['tnid']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'ciniki.fatt.146', 'msg'=>'Unable to send AED expiration message', 
                'severity'=>50, 'err'=>$rc['err']));
            continue;
        } else {
            //
            // Update the next date
            //
            $dt = new DateTime($tenant['detail_value'], new DateTimeZone('UTC'));
            $dt->add(new DateInterval('P7D'));
            $strsql = "UPDATE ciniki_fatt_settings "
                . "SET detail_value = '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d H:i:s')) . "' "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tenant['tnid']) . "' "
                . "AND detail_key = '" . ciniki_core_dbQuote($ciniki, $tenant['detail_key']) . "' "
                . "";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.fatt');
            if( $rc['stat'] != 'ok' ) {
                ciniki_cron_logMsg($ciniki, $tenant['tnid'], array('code'=>'ciniki.fatt.147', 'msg'=>'Unable to send AED expiration message', 
                    'severity'=>50, 'err'=>$rc['err']));
                continue;
            }
            ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.fatt', 'ciniki_fatt_history', $tenant['tnid'], 
                2, 'ciniki_fatt_settings', $tenant['detail_key'], 'detail_value', $dt->format('Y-m-d H:i:s'));

        }
    }

    return array('stat'=>'ok');
}
