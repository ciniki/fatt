<?php
//
// Description
// ===========
// This method will update an offering registration and the connected invoice item.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the offering is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_fatt_offeringRegistrationUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
		'item_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Invoice Item'),
		'student_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Student'),
		'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'), 
		'customer_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Notes'), 
		'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
		'test_results'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Test Results'), 
		'unit_amount'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Unit Amount'),
		'unit_discount_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 
			'name'=>'Discount Amount'),
		'unit_discount_percentage'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Discount Percentage'),
		'taxtype_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Tax Type'),
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.offeringRegistrationUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the current registration
	//
	$strsql = "SELECT id, offering_id, student_id, status "
		. "FROM ciniki_fatt_offering_registrations "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'registration');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['registration']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2359', 'msg'=>'Registration not found'));
	}
	$registration = $rc['registration'];

	//
	// Start transaction
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.fatt');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}

	//
	// Update the offering in the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	$rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.fatt.offeringregistration', $args['registration_id'], $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
		return $rc;
	}

	//
	// Check if the status changed
	//
	if( isset($args['status']) && $args['status'] != $registration['status'] ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringRegistrationUpdateCerts');
		$rc = ciniki_fatt_offeringRegistrationUpdateCerts($ciniki, $args['business_id'], $args['registration_id'], $registration);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
			return $rc;
		}
	}

	//
	// Update the seats
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateDatesSeats');
	$rc = ciniki_fatt_offeringUpdateDatesSeats($ciniki, $args['business_id'], $registration['offering_id'], 'yes');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
		return $rc;
	}

	//
	// Commit the transaction
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.fatt');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'fatt');

	//
	// Update the invoice item
	//
	if( isset($args['item_id']) && $args['item_id'] > 0 ) {
		$item_args = array('item_id'=>$args['item_id']);
		if( isset($args['student_id']) && $args['student_id'] != $registration['student_id'] ) {
			if( $args['student_id'] == 0 ) {
				$item_args['notes'] = '';
			} else {
				ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
				$rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'], array('customer_id'=>$args['student_id']));
				if( $rc['stat'] == 'ok' && isset($rc['customer']['display_name']) ) {
					$item_args['notes'] = $rc['customer']['display_name'];
				}
			}
		}

		foreach(array('unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id') as $aname) {
			if( isset($args[$aname]) ) {
				$item_args[$aname] = $args[$aname];
			}
		}
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemUpdate');
		$rc = ciniki_sapos_hooks_invoiceItemUpdate($ciniki, $args['business_id'], $item_args);
		return $rc;
	}

	return array('stat'=>'ok');
}
?>
