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
function ciniki_fatt_sapos_invoiceUpdate($ciniki, $business_id, $invoice_id, $item) {

	//
	// If an invoice was updated, check if we need to change any of the registrations
	//
	if( isset($item['object']) && $item['object'] == 'ciniki.fatt.offeringregistration' && isset($item['object_id']) ) {
		//
		// Check the offering registration exists
		//
		$strsql = "SELECT id, offering_id, customer_id, student_id "
			. "FROM ciniki_fatt_offering_registrations "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'registration');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['registration']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2352', 'msg'=>'Unable to find course registration'));
		}
		$registration = $rc['registration'];

		//
		// Pull the customer id from the invoice, see if it's different
		//
		$strsql = "SELECT id, customer_id "
			. "FROM ciniki_sapos_invoices "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['invoice']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2354', 'msg'=>'Unable to find invoice'));
		}
		$invoice = $rc['invoice'];
		
		//
		// If the customer is different, update the registration
		//
		if( $registration['customer_id'] != $invoice['customer_id'] ) {
			//
			// NOTE: Don't change the student_id if originally the same as customer_id because the use 
			//       might be changing from a self pay registration, to a company pay.
			//
			$reg_args = array('customer_id'=>$invoice['customer_id']);
			$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.fatt.offeringregistration', 
				$registration['id'], $reg_args, 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}

		return array('stat'=>'ok');
	}

	return array('stat'=>'ok');
}
?>
