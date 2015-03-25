<?php
//
// Description
// -----------
// This method will return the list of certs for a business.  
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
function ciniki_fatt_certList($ciniki) {
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.certList');
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
	// Get the list of certs
	//
	$strsql = "SELECT ciniki_fatt_certs.id, "
		. "ciniki_fatt_certs.name, "
		. "ciniki_fatt_certs.status, "
		. "ciniki_fatt_certs.status AS status_text, "
		. "ciniki_fatt_certs.years_valid "
		. "FROM ciniki_fatt_certs "
		. "WHERE ciniki_fatt_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY name "
		. "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
		array('container'=>'certs', 'fname'=>'id', 'name'=>'cert',
			'fields'=>array('id', 'name', 'status', 'status_text', 'years_valid'),
			'maps'=>array('status_text'=>$maps['cert']['status'])),
		));
	return $rc;
}
?>
