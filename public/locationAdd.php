<?php
//
// Description
// -----------
// This method will add a new location for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to add the location to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_fatt_locationAdd(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'code'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Code'), 
		'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
		'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
		'status'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Status'), 
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Flags'), 
		'address1'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address Line 1'), 
		'address2'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Address Line 2'), 
		'city'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'City'), 
		'province'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Province'), 
		'postal'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Postal Code'), 
		'latitude'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Latitude'), 
		'longitude'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Longitude'), 
		'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Website'),
		'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'),
		'num_seats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Number of Seats'), 
		'colour'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Colour'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	if( !isset($args['permalink']) || $args['permalink'] == '' ) {	
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
	}

	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
	$rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.locationAdd');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check the permalink doesn't already exist
	//
	$strsql = "SELECT id "
		. "FROM ciniki_fatt_locations "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'location');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2286', 'msg'=>'You already have a location with this name, please choose another name.'));
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
	// Add the location to the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.fatt.location', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
		return $rc;
	}
	$location_id = $rc['id'];

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

	return array('stat'=>'ok', 'id'=>$location_id);
}
?>
