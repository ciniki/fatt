<?php
//
// Description
// -----------
// This function returns the list of first add certifications for a customer.
//
// Arguments
// ---------
// ciniki:
// business_id:			The business ID to check the session user against.
// method:				The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_fatt_hooks_customerCerts($ciniki, $business_id, $args) {
	//
	// Get the time information for business and user
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
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
	// Load the status maps for the text description of each status
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
	$rc = ciniki_sapos_maps($ciniki);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$maps = $rc['maps'];

	//
	// Get the certs for the customer
	//
	$strsql = "SELECT ciniki_fatt_cert_customers.id, "
		. "ciniki_fatt_certs.id AS cert_id, "
		. "DATE_FORMAT(ciniki_fatt_cert_customers.date_received, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_received, "
		. "DATE_FORMAT(ciniki_fatt_cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS date_expiry, "
		. "DATEDIFF('" . ciniki_core_dbQuote($ciniki, $cur_date->format('Y-m-d')) . "', ciniki_fatt_cert_customers.date_expiry) AS age, "
		. "ciniki_fatt_certs.name, "
		. "ciniki_fatt_certs.years_valid "
		. "FROM ciniki_fatt_cert_customers "
		. "LEFT JOIN ciniki_fatt_certs ON ("
			. "ciniki_fatt_cert_customers.cert_id = ciniki_fatt_certs.id "
			. "AND ciniki_fatt_certs.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. ") "
		. "WHERE ciniki_fatt_cert_customers.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
		. "AND ciniki_fatt_cert_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "ORDER BY ciniki_fatt_cert_customers.date_expiry DESC, ciniki_fatt_certs.name "
		. "";
	if( isset($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . $args['limit'] . " ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$certs = array();
	$curcerts = array();
	$pastcerts = array();
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'cert');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	foreach($rc['rows'] as $row) {
		$cert = array('cert'=>array(
			'id'=>$row['id'],
			'name'=>$row['name'],
			'date_received'=>$row['date_received'],
			'date_expiry'=>'No Expiration',
			'expiry_text'=>'No Expiration',
			));
		if( $row['years_valid'] > 0 ) {
			$cert['cert']['date_expiry'] = $row['date_expiry'];
			$cert['cert']['days_till_expiry'] = $row['age'];
			if( $row['age'] < 0 ) {
				$cert['cert']['expiry_text'] = "Expiring in " . abs($row['age']) . " day" . ($row['age']<1?'s':'');
			} elseif( $row['age'] == 0 ) {
				$cert['cert']['expiry_text'] = "Expired today";
			} elseif( $row['age'] > 0 ) {
				$cert['cert']['expiry_text'] = "Expired " . $row['age'] . " day" . ($row['age']>1?'s':'') . " ago";
			}
		}
		if( !isset($certs[$row['cert_id']]) ) {
			$certs[$row['cert_id']] = $cert;
			$curcerts[] = $cert;
		} else {
			$pastcerts[] = $cert;
		}
	}

	return array('stat'=>'ok', 'curcerts'=>$curcerts, 'pastcerts'=>$pastcerts);
}
?>
