<?php
//
// Description
// ===========
// This method will return all the information about a cert.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the cert is attached to.
// cert_id:		The ID of the cert to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_certCustomerGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'certcustomer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Certification'), 
        'cert_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Cert'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'), 
		'certs'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Certs'),
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.certCustomerGet'); 
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

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$mysql_date_format = ciniki_users_dateFormat($ciniki, 'mysql');
	$php_date_format = ciniki_users_dateFormat($ciniki, 'php');

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

	if( $args['certcustomer_id'] == 0 ) {	
		//
		// Return the default settings for a new cert
		//
		$dt = new DateTime("now", new DateTimeZone($intl_timezone));
		$rsp = array('stat'=>'ok', 'certcustomer'=>array(
			'cert_id'=>((isset($args['cert_id'])&&$args['cert_id']!='')?$args['cert_id']:'0'),
			'customer_id'=>((isset($args['customer_id']) && $args['customer_id']!='')?$args['customer_id']:'0'),
			'date_received'=>$dt->format($php_date_format),
			'date_expiry'=>'',
			));
	} else {
		//
		// Get the cert customer details
		//
		$strsql = "SELECT ciniki_fatt_cert_customers.id, "
			. "ciniki_fatt_cert_customers.cert_id, "
			. "ciniki_fatt_cert_customers.customer_id, "
			. "DATE_FORMAT(ciniki_fatt_cert_customers.date_received, '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "') AS date_received, "
			. "DATE_FORMAT(ciniki_fatt_cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "') AS date_expiry, "
			. "ciniki_fatt_cert_customers.flags "
			. "FROM ciniki_fatt_cert_customers "
			. "WHERE ciniki_fatt_cert_customers.id = '" . ciniki_core_dbQuote($ciniki, $args['certcustomer_id']) . "' "
			. "AND ciniki_fatt_cert_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.certs', 'certcustomer');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['certcustomer']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2322', 'msg'=>'Unable to find cert'));
		}
		$rsp = array('stat'=>'ok', 'certcustomer'=>$rc['certcustomer']);
	}

	//
	// Get the customer details
	//
	if( $rsp['certcustomer']['customer_id'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
		$rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'],
		array('customer_id'=>$args['customer_id'], 'addresses'=>'yes', 'phones'=>'yes', 'emails'=>'yes'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$rsp['certcustomer']['customer_details'] = $rc['details'];
	} else {
		$rsp['certcustomer']['customer_details'] = array();
	}

	//
	// Get the certs for the business
	//
	if( isset($args['certs']) && $args['certs'] == 'yes' ) {
		$strsql = "SELECT ciniki_fatt_certs.id, "
			. "ciniki_fatt_certs.name, "
			. "ciniki_fatt_certs.years_valid "
			. "FROM ciniki_fatt_certs "
			. "WHERE ciniki_fatt_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "ORDER BY ciniki_fatt_certs.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'certs', 'fname'=>'id', 'name'=>'cert',
				'fields'=>array('id', 'name', 'years_valid')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$rsp['certs'] = array();
		if( isset($rc['certs']) ) {
			$rsp['certs'] = $rc['certs'];
		}
	}

	return $rsp;
}
?>
