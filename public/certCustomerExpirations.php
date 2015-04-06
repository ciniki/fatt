<?php
//
// Description
// -----------
// This method returns the list of customers and their certifications that are going to expire, or have expired.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get certs for.
// cert_ids:		The IDs of the certs to check for expirations. 0 if all certs.
// start_date:		Look for any expiration on or after this date.
// end_date:		Look for any expirations before this date.
//
// Returns
// -------
//
function ciniki_fatt_certCustomerExpirations($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'cert_ids'=>array('required'=>'no', 'blank'=>'no', 'type'=>'idlist', 'name'=>'Certifications'), 
		'start_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Expire after date'), 
		'end_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Expire before date'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.certCustomerExpirations');
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
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	$php_date_format = ciniki_users_dateFormat($ciniki, 'php');

	//
	// Get the current date in the business timezone
	//
	$cur_date = new DateTime('now', new DateTimeZone($intl_timezone));

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
	// Get the list of certs that have expirations
	//
	if( !isset($args['cert_ids']) ) {
		$strsql = "SELECT ciniki_fatt_certs.id "
			. "FROM ciniki_fatt_certs "
			. "WHERE ciniki_fatt_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND years_valid > 0 "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
		$rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.fatt', 'cert_ids', 'id');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['cert_ids']) ) {
			$args['cert_ids'] = $rc['cert_ids'];
		}
	}

	$rsp = array('stat'=>'ok');

	//
	// Get the list of certs that are going to expire between the start and end dates
	//
	$strsql = "SELECT ciniki_fatt_cert_customers.id, "
		. "ciniki_fatt_cert_customers.cert_id, "
		. "ciniki_fatt_cert_customers.customer_id, "
		. "ciniki_customers.display_name, "
		. "ciniki_fatt_certs.name, "
		. "ciniki_fatt_certs.years_valid, "
		. "DATE_FORMAT(ciniki_fatt_cert_customers.date_received, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_received, "
		. "DATE_FORMAT(ciniki_fatt_cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_expiry, "
		. "DATEDIFF(ciniki_fatt_cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, $cur_date->format('Y-m-d')) . "') AS days_till_expiry "
		. "FROM ciniki_fatt_cert_customers "
		. "INNER JOIN ciniki_fatt_certs ON ("
			. "ciniki_fatt_cert_customers.cert_id = ciniki_fatt_certs.id "
			. "AND ciniki_fatt_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_customers ON ("
			. "ciniki_fatt_cert_customers.customer_id = ciniki_customers.id "
			. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_fatt_cert_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_fatt_cert_customers.date_expiry >= '" . ciniki_core_dbQuote($ciniki, $args['start_date']) . "' "
		. "AND ciniki_fatt_cert_customers.date_expiry < '" . ciniki_core_dbQuote($ciniki, $args['end_date']) . "' "
		. "ORDER BY days_till_expiry ASC "
		. "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
		array('container'=>'certs', 'fname'=>'id', 'name'=>'cert',
			'fields'=>array('id', 'customer_id', 'display_name', 'name', 'date_received', 'date_expiry', 'days_till_expiry', 'years_valid')),
		));
	$rsp['certs'] = array();
	if( isset($rc['certs']) ) {
		$rsp['certs'] = $rc['certs'];
		foreach($rsp['certs'] as $cid => $cert) {
			if( $cert['cert']['years_valid'] > 0 ) {
				$age = $cert['cert']['days_till_expiry'];
				if( $age > 0 ) {
					$rsp['certs'][$cid]['cert']['expiry_text'] = "Expiring in " . abs($age) . " day" . ($age>1?'s':'');
				} elseif( $age == 0 ) {
					$rsp['certs'][$cid]['cert']['expiry_text'] = "Expired today";
				} elseif( $age < 0 ) {
					$rsp['certs'][$cid]['cert']['expiry_text'] = "Expired " . abs($age) . " day" . ($age<1?'s':'') . " ago";
				}
			} else {
				$rsp['certs'][$cid]['cert']['date_expiry'] = 'No Expiration';
				$rsp['certs'][$cid]['cert']['expiry_text'] = 'No Expiration';
			}
		}
	}

	return $rsp;
}
?>
