<?php
//
// Description
// ===========
// This method will be called whenever a item is updated in an invoice.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_fatt_sapos_itemDelete($ciniki, $business_id, $invoice_id, $item) {

	//
	// An invoice line item was removed that was an offering
	//
	if( isset($item['object']) && $item['object'] == 'ciniki.fatt.offeringregistration' && isset($item['object_id']) ) {
		//
		// Check the offering registration exists
		//
		$strsql = "SELECT id, uuid, offering_id, customer_id, student_id "
			. "FROM ciniki_fatt_offering_registrations "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'registration');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['registration']) ) {
			// Don't worry if can't find existing reg, probably database error
			return array('stat'=>'ok');
		}
		$registration = $rc['registration'];

		//
		// Remove the invoice from the registration, don't delete it
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
		$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.fatt.offeringregistration', 
			$registration['id'], array('invoice_id'=>'0'), 0x04);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}

		return array('stat'=>'ok');
	}

	return array('stat'=>'ok');
}
?>
