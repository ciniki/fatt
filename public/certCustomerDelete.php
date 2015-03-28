<?php
//
// Description
// -----------
// This method will delete a cert from the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business the cert is attached to.
// cert_id:			The ID of the cert to be removed.
//
// Returns
// -------
// <rsp stat="ok">
//
function ciniki_fatt_certCustomerDelete(&$ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'certcustomer_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Certification'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Check access to business_id as owner
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
	$rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.certCustomerDelete');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Get the uuid of the cert to be deleted
	//
	$strsql = "SELECT uuid "
		. "FROM ciniki_fatt_cert_customers "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['certcustomer_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'cert');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['cert']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2323', 'msg'=>'The certification does not exist'));
	}
	$cert_uuid = $rc['cert']['uuid'];

	//
	// Remove the cert
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
	return ciniki_core_objectDelete($ciniki, $args['business_id'], 'ciniki.fatt.certcustomer', $args['certcustomer_id'], $cert_uuid, 0x07);
}
?>
