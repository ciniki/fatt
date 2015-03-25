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
function ciniki_fatt_certAdd(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
		'status'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Status'), 
		'years_valid'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Valid For'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
	$rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.certAdd');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
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
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.fatt.cert', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
		return $rc;
	}
	$cert_id = $rc['id'];

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

	return array('stat'=>'ok', 'id'=>$cert_id);
}
?>
