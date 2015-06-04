<?php
//
// Description
// ===========
// This method returns all the information for a class (a group of offerings at the same time location)
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the offering is attached to.
// offering_id:		The ID of the offering to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_classGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'class_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Class'), 
        'location_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Location'), 
        'start_ts'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Start Date'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.classGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Load the class details
	//
	$rsp = array('stat'=>'ok');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'classLoad');
	$rc = ciniki_fatt_classLoad($ciniki, $args['business_id'], $args);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['class']) ) {
		$rsp['class'] = $rc['class'];
	}

	return $rsp;
}
?>
