<?php
//
// Description
// -----------
// This method will add a new cert for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to add the cert to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_fatt_certCustomerAdd(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'cert_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Certification'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Customer'), 
		'date_received'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Certification Date'), 
		'date_expiry'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Certification Expiry'), 
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
	$rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.certCustomerAdd');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Get the time information for business and user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	date_default_timezone_set($intl_timezone);

	if( !isset($args['date_expiry']) || $args['date_expiry'] == '' ) {
		//
		// Load the cert information
		//
		$strsql = "SELECT id, name, years_valid "
			. "FROM ciniki_fatt_certs "
			. "WHERE ciniki_fatt_certs.id = '" . ciniki_core_dbQuote($ciniki, $args['cert_id']) . "' "
			. "AND ciniki_fatt_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'cert');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['cert']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2321', 'msg'=>'The certification does not exist'));
		}
		$cert = $rc['cert'];

		//
		// Setup the expiry date, based on date received and years_valid from cert 
		//
		if( $cert['years_valid'] > 0 ) {
			$dt = new DateTime($args['date_received'], new DateTimeZone($intl_timezone));
			$dt->add(new DateInterval('P1Y'));
			$args['date_expiry'] = $dt->format('Y-m-d');
		} else {
			$args['date_expiry'] = '';
		}
	}

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.fatt');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add the cert to the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.fatt.cert_customer', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
		return $rc;
	}
	$certcustomer_id = $rc['id'];

	//
	// Commit the transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.fatt');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'fatt');

	return array('stat'=>'ok', 'id'=>$certcustomer_id);
}
?>
