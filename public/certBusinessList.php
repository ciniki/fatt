<?php
//
// Description
// -----------
// This method will return the list of businesses and their certification statistics.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get certs for.
//
// Returns
// -------
//
function ciniki_fatt_certBusinessList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.certBusinessList');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Load fatt maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'maps');
	$rc = ciniki_fatt_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// Get the list of businesses
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerList');
	$rc = ciniki_customers_hooks_customerList($ciniki, $args['business_id'], array('type'=>2));
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$customers = $rc['customers'];

	return array('stat'=>'ok', 'customers'=>$customers);
}
?>
