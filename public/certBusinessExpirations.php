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
function ciniki_fatt_certBusinessExpirations($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.certBusinessEmployees');
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

	//
	// Load fatt maps
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'maps');
	$rc = ciniki_fatt_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	$rsp = array('stat'=>'ok', 'certs'=>array());

	//
	// Get the business details
	//
	if( $args['customer_id'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
		$rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'],
		array('customer_id'=>$args['customer_id'], 'addresses'=>'yes', 'phones'=>'yes', 'emails'=>'yes'));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$rsp['customer'] = $rc['customer'];
		$rsp['customer_details'] = $rc['details'];
	} else {
		$rsp['customer'] = array();
		$rsp['customer_details'] = array();
	}

	//
	// Get the list of employees
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerList');
	$rc = ciniki_customers_hooks_customerList($ciniki, $args['business_id'], array('parent_id'=>$args['customer_id']));
	if( $rc['stat'] != 'ok' ) {	
		return $rc;
	}
	$customers = array();
	foreach($rc['customers'] as $customer) {
		$customers[$customer['customer']['id']] = $customer['customer'];
		$customers[$customer['customer']['id']]['certs'] = array();
	}


	//
	// Get the certifications for the employees
	//
	if( count($customers) > 0 ) {
		$strsql = "SELECT ciniki_fatt_cert_customers.id, "
			. "ciniki_fatt_cert_customers.cert_id, "
			. "ciniki_fatt_cert_customers.customer_id, "
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
			. "WHERE ciniki_fatt_cert_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_fatt_cert_customers.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, array_keys($customers)) . ") "
			. "ORDER BY ciniki_fatt_cert_customers.customer_id, ciniki_fatt_cert_customers.cert_id, days_till_expiry ASC "
			. "";
		error_log($strsql);
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
			array('container'=>'certs', 'fname'=>'id', 'name'=>'cert',
				'fields'=>array('id', 'customer_id', 'name', 'date_received', 'date_expiry', 'days_till_expiry', 'years_valid')),
			));
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
				//
				// Attach the customer name to the cert and the customer to the cert
				//
				if( isset($customers[$cert['cert']['customer_id']]) ) {
					$customers[$cert['cert']['customer_id']]['certs'][] = $rsp['certs'][$cid];
					$rsp['certs'][$cid]['cert']['display_name'] = $customers[$cert['cert']['customer_id']]['display_name'];
				} else {
					$rsp['certs'][$cid]['cert']['display_name'] = '';
				}
			}
		}
	}

	//
	// Check for customers with no certifications
	//
	foreach($customers as $customer) {
		if( count($customer['certs']) == 0 ) {
			$rsp['certs'][] = array('cert'=>array('id'=>'0', 'cert_id'=>'0', 'customer_id'=>$customer['id'], 'display_name'=>$customer['display_name'], 'name'=>'', 'years_valid'=>'', 'days_to_expiry'=>'', 'expiry_text'=>'', 'date_received'=>'', 'date_expiry'=>''));
		}
	}

	return $rsp;
}
?>
