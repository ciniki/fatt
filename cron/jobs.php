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
function ciniki_fatt_cron_jobs($ciniki) {
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
		return $rc;
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

	return array('stat'=>'ok');
}
