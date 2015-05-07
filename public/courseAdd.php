<?php
//
// Description
// -----------
// This method will add a new course for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to add the course to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_fatt_courseAdd(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'), 
		'code'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Code'), 
		'permalink'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Permalink'), 
		'status'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Status'), 
		'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
		'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Synopsis'),
		'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
		'price'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Price'), 
		'taxtype_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Taxes'), 
		'num_days'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Number of Days'), 
		'num_hours'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Number of Hours'), 
		'num_seats_per_instructor'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Number of Seats per Instructor'), 
		'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Flags'), 
		'cert_form'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Certification Form'), 
		'categories'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Categories'), 
		'certs'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Certifications'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
	$rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.courseAdd');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Create the permalink
	//
	if( !isset($args['permalink']) || $args['permalink'] == '' ) {	
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
		$args['permalink'] = ciniki_core_makePermalink($ciniki, $args['name']);
	}

	//
	// Check the permalink doesn't already exist
	//
	$strsql = "SELECT id "
		. "FROM ciniki_fatt_courses "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'course');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2316', 'msg'=>'You already have a course with this name, please choose another name.'));
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
	// Add the course to the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.fatt.course', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
		return $rc;
	}
	$course_id = $rc['id'];

	//
	// Update the categories
	//
	if( isset($args['categories']) && count($args['categories']) > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'courseUpdateCategories');
		$rc = ciniki_fatt_courseUpdateCategories($ciniki, $args['business_id'], $course_id, $args['categories']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
			return $rc;
		}
	}

	//
	// Update the certs
	//
	if( isset($args['certs']) && count($args['certs']) > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'courseUpdateCerts');
		$rc = ciniki_fatt_courseUpdateCerts($ciniki, $args['business_id'], $course_id, $args['certs']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
			return $rc;
		}
	}

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

	return array('stat'=>'ok', 'id'=>$course_id);
}
?>
