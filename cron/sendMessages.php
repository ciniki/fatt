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
function ciniki_fatt_cron_sendMessages($ciniki) {
	print("CRON: Checking fatt certification expirations for mail to be sent\n");
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');

	//
	// Get the list of businesses which have fatt certification expiration messages to be sent out
	//
	$strsql = "SELECT DISTINCT business_id "
		. "FROM ciniki_fatt_cert_customers "
		. "WHERE next_message_date <= UTC_TIMESTAMP() "
		. "AND (flags&0x03) = 0x01 "	// Certifications that still have messages to go out, and aren't finished
		. "";
	$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.fatt', 'businesses', 'business_id');
	if( $rc['stat'] != 'ok' ) {
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
		print("CRON: Sending fatt certification expiration messages for $business_id\n");

		//
		// Process the emails for the business
		//
		$rc = ciniki_fatt_cronSendCertExpirationMessages($ciniki, $business_id, 0x07);
		if( $rc['stat'] != 'ok' ) {
			error_log("CRON-ERR: Unable to send cert expiration messages for $business_id (" . serialize($rc) . ")");
			continue;
		}
	}

	return array('stat'=>'ok');
}
