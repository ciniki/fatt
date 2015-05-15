<?php
//
// Description
// -----------
// This method will return the list of instructors for a business.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get instructors for.
//
// Returns
// -------
//
function ciniki_fatt_instructorList($ciniki) {
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.instructorList');
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
	// Load the list of instructors
	//
	$strsql = "SELECT ciniki_fatt_instructors.id, "
		. "ciniki_fatt_instructors.name, "
		. "ciniki_fatt_instructors.initials, "
		. "ciniki_fatt_instructors.status, "
		. "ciniki_fatt_instructors.status AS status_text, "
		. "ciniki_fatt_instructors.id_number, "
		. "ciniki_fatt_instructors.phone, "
		. "ciniki_fatt_instructors.email "
		. "FROM ciniki_fatt_instructors "
		. "WHERE ciniki_fatt_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY name "
		. "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
		array('container'=>'instructors', 'fname'=>'id', 'name'=>'instructor',
			'fields'=>array('id', 'name', 'initials', 'status', 'status_text',
				'id_number', 'phone', 'email'),
			'maps'=>array('status_text'=>$maps['instructor']['status'])),
		));
	return $rc;
}
?>
