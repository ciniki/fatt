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
	// Get the list of businesses which have fatt certification expiration messages to be sent out
	//
	$strsql = "SELECT DISTINCT business_id "
		. "FROM ciniki_fatt_cert_customers "
		. "WHERE next_message_date <= UTC_TIMESTAMP() "
		. "AND date_expiry <> '0000-00-00' "
		. "AND (flags&0x03) = 0x01 "	// Certifications that still have messages to go out, and aren't finished
		. "";
	$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.fatt', 'businesses', 'business_id');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2628', 'msg'=>'Unable to get list of businesses with FATT certifications', 'err'=>$rc['err']));
	}
	if( !isset($rc['businesses']) || count($rc['businesses']) == 0 ) {
		$businesses = array();
	} else {
		$businesses = $rc['businesses'];
	}

	//
	// For each business, load their mail settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'cronSendCertExpirationMessages');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	foreach($businesses as $business_id) {
		ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'0', 'msg'=>'Sending certfication expiration messages', 'severity'=>'10'));

		//
		// Process the emails for the business
		//
		$rc = ciniki_fatt_cronSendCertExpirationMessages($ciniki, $business_id, 0x07);
		if( $rc['stat'] != 'ok' ) {
			ciniki_cron_logMsg($ciniki, $business_id, array('code'=>'2629', 'msg'=>'Unable to send certification expiration messages', 
				'severity'=>50, 'err'=>$rc['err']));
			continue;
		}
	}

	//
	// Get the list of businesses which have aed expirations and want a message
	//
    $strsql = "SELECT DISTINCT s1.business_id, s2.detail_key, s2.detail_value "
        . "FROM ciniki_fatt_settings AS s1, ciniki_fatt_settings AS s2 "
        . "WHERE s1.detail_key = 'aeds-expirations-message-enabled' "
        . "AND s1.detail_value = 'yes' "
        . "AND s1.business_id = s2.business_id "
        . "AND s2.detail_key = 'aeds-expirations-message-next' "
        . "AND s2.detail_value <= UTC_TIMESTAMP() "
        . "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'business');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3472', 'msg'=>'Unable to get list of businesses with AED expiration messages', 'err'=>$rc['err']));
		return $rc;
	}
    if( isset($rc['rows']) ) {
		$businesses = $rc['rows'];
	} else {
		$businesses = array();
	}

	//
	// For each business, load their mail settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'cronSendAEDExpirations');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	foreach($businesses as $business) {
		ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'0', 'msg'=>'Sending AED expiration message', 'severity'=>'10'));

		//
		// Process the emails for the business
		//
		$rc = ciniki_fatt_cronSendAEDExpirations($ciniki, $business['business_id']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'3473', 'msg'=>'Unable to send AED expiration message', 
				'severity'=>50, 'err'=>$rc['err']));
			continue;
		} else {
            //
            // Update the next date
            //
            $dt = new DateTime($business['detail_value'], new DateTimeZone('UTC'));
            $dt->add(new DateInterval('P7D'));
            $strsql = "UPDATE ciniki_fatt_settings "
                . "SET detail_value = '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d H:i:s')) . "' "
                . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business['business_id']) . "' "
                . "AND detail_key = '" . ciniki_core_dbQuote($ciniki, $business['detail_key']) . "' "
                . "";
            $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.fatt');
            if( $rc['stat'] != 'ok' ) {
                ciniki_cron_logMsg($ciniki, $business['business_id'], array('code'=>'3474', 'msg'=>'Unable to send AED expiration message', 
                    'severity'=>50, 'err'=>$rc['err']));
                continue;
            }
			ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.fatt', 'ciniki_fatt_history', $business['business_id'], 
				2, 'ciniki_fatt_settings', $business['detail_key'], 'detail_value', $dt->format('Y-m-d H:i:s'));

        }
	}

	return array('stat'=>'ok');
}
