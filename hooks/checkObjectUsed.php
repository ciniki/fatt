<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_fatt_hooks_checkObjectUsed($ciniki, $business_id, $args) {

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');

	// Set the default to not used
	$used = 'no';
	$count = 0;
	$msg = '';

	if( $args['object'] == 'ciniki.customers.customer' ) {
		//
		// Check the customer certifications
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_fatt_cert_customers "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.fatt', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count certification" . ($count==1?'':'s') . " for this customer.";
		}
		//
		// Check the course offering registrations
		//
		$strsql = "SELECT 'items', COUNT(*) "
			. "FROM ciniki_fatt_offering_registrations "
			. "WHERE customer_id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.fatt', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['items']) && $rc['num']['items'] > 0 ) {
			$used = 'yes';
			$count = $rc['num']['items'];
			$msg .= ($msg!=''?' ':'') . "There " . ($count==1?'is':'are') . " $count course registrations" . ($count==1?'':'s') . " for this customer.";
		}
	}

	return array('stat'=>'ok', 'used'=>$used, 'count'=>$count, 'msg'=>$msg);
}
?>
