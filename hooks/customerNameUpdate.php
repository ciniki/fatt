<?php
//
// Description
// -----------
// This function will update open orders when a customer status changes
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
function ciniki_fatt_hooks_customerNameUpdate($ciniki, $business_id, $args) {
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
	// Update open orders
	//
	if( isset($args['customer_id']) && $args['customer_id'] > 0 
		&& isset($args['display_name']) && $args['display_name'] != '' 
		) {
		//
		// Update the invoice's items with the student name
		//
		$strsql = "SELECT id, "
			. "invoice_id "
			. "FROM ciniki_fatt_offering_registrations "
			. "WHERE student_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
			. "AND customer_id <> student_id "
			. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		//
		// Update any invoices where this customer is the student and not customer
		//
		if( isset($rc['rows']) ) {
			$invoices = $rc['rows'];
			ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemUpdate');
			ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
			foreach($invoices as $invoice) {
				//
				// Get the item
				//
				$rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $business_id, $invoice['invoice_id'], 'ciniki.fatt.offeringregistration', $invoice['id']);
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
				$item = $rc['item'];

				//
				// Update invoice item
				//
				$rc = ciniki_sapos_hooks_invoiceItemUpdate($ciniki, $business_id, array('item_id'=>$item['id'], 'notes'=>$args['display_name']));
				if( $rc['stat'] != 'ok' ) {
					return $rc;
				}
			}
		}
	}

	return array('stat'=>'ok');
}
?>
