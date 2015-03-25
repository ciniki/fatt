<?php
//
// Description
// -----------
// This method will return the list of locations for a business.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get locations for.
//
// Returns
// -------
//
function ciniki_fatt_locationList($ciniki) {
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.locationList');
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
	// Get the list of locations
	//
	$strsql = "SELECT ciniki_fatt_locations.id, "
		. "ciniki_fatt_locations.name, "
		. "ciniki_fatt_locations.status, "
		. "ciniki_fatt_locations.status AS status_text, "
		. "ciniki_fatt_locations.city "
		. "FROM ciniki_fatt_locations "
		. "WHERE ciniki_fatt_locations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY name "
		. "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
		array('container'=>'locations', 'fname'=>'id', 'name'=>'location',
			'fields'=>array('id', 'name', 'city', 'status', 'status_text'),
			'maps'=>array('status_text'=>$maps['location']['status'])),
		));
	return $rc;
}
?>
