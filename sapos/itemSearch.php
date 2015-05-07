<?php
//
// Description
// ===========
// This function searches the fatt course offerings
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_fatt_sapos_itemSearch($ciniki, $business_id, $args) {

	if( $args['start_needle'] == '' ) {
		return array('stat'=>'ok', 'items'=>array());
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Search by code, name or date
	// Show how many spots left for each offering
	//
	$strsql = "SELECT ciniki_fatt_offerings.id, "
		. "ciniki_fatt_offerings.start_date, "
		. "ciniki_fatt_offerings.date_string, "
		. "ciniki_fatt_offerings.location, "
		. "ciniki_fatt_offerings.seats_remaining, "
		. "ciniki_fatt_offerings.price, "
		. "ciniki_fatt_courses.code, "
		. "ciniki_fatt_courses.name, "
		. "ciniki_fatt_courses.taxtype_id "
		. "FROM ciniki_fatt_courses "
		. "INNER JOIN ciniki_fatt_offerings ON ("
			. "ciniki_fatt_courses.id = ciniki_fatt_offerings.course_id "
			. "AND ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND UNIX_TIMESTAMP(ciniki_fatt_offerings.start_date) > (UNIX_TIMESTAMP(UTC_TIMESTAMP())-86400) "
			. ") "
		. "WHERE ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_fatt_courses.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_fatt_courses.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. ") "
		. "ORDER BY ciniki_fatt_offerings.start_date ";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.fatt', array(
		array('container'=>'offerings', 'fname'=>'id',
			'fields'=>array('id', 'code', 'name', 'start_date', 'date_string', 'location', 'seats_remaining', 'price', 'taxtype_id')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['offerings']) ) {
		$offerings = $rc['offerings'];
	} else {
		return array('stat'=>'ok', 'items'=>array());
	}

	$items = array();
	foreach($offerings as $oid => $offering) {
		$item = array(
			'status'=>0,
			'object'=>'ciniki.fatt.offering',
			'object_id'=>$offering['id'],
			'code'=>'',
			'description'=>$offering['name'] . ' - ' . $offering['date_string'],
			'quantity'=>1,
			'unit_amount'=>$offering['price'],
			'unit_discount_amount'=>0,
			'unit_discount_percentage'=>0,
			'taxtype_id'=>$offering['taxtype_id'], 
			'notes'=>'',
			'registrations_available'=>$offering['seats_remaining'],
			);
		if( $offering['seats_remaining'] < 0 ) {
			$item['available_display'] = abs($offering['seats_remaining']) . ' oversold';
		} elseif( $offering['seats_remaining'] == 0 ) {
			$item['available_display'] = 'SOLD OUT';
		} elseif( $offering['seats_remaining'] > 0 ) {
			$item['available_display'] = $offering['seats_remaining'];
		}
		// Flags: No Quantity, Registration Item
		$item['flags'] = 0x28;
		$items[] = array('item'=>$item);
	}

	return array('stat'=>'ok', 'items'=>$items);		
}
?>
