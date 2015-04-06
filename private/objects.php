<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_fatt_objects($ciniki) {
	
	$objects = array();
	$objects['course'] = array(
		'name'=>'Course',
		'o_name'=>'course',
		'o_container'=>'courses',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_courses',
		'fields'=>array(
			'name'=>array(),
			'code'=>array(),
			'permalink'=>array(),
			'status'=>array('default'=>'10'),
			'primary_image_id'=>array('default'=>'0'),
			'synopsis'=>array('default'=>''),
			'description'=>array('default'=>''),
			'price'=>array('default'=>'0'),
			'num_days'=>array('default'=>'1'),
			'num_seats_per_instructor'=>array('default'=>'0'),
			'flags'=>array('default'=>'0'),
			'cert_form'=>array('default'=>''),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['category'] = array(
		'name'=>'Category',
		'o_name'=>'category',
		'o_container'=>'categories',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_categories',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'sequence'=>array('default'=>'1'),
			'primary_image_id'=>array('default'=>'0'),
			'synopsis'=>array('default'=>''),
			'description'=>array('default'=>''),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['course_category'] = array(
		'name'=>'Course Category',
		'o_name'=>'course_category',
		'o_container'=>'course_categories',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_course_categories',
		'fields'=>array(
			'course_id'=>array('ref'=>'ciniki.fatt.course'),
			'category_id'=>array('ref'=>'ciniki.fatt.category'),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['course_cert'] = array(
		'name'=>'Course Certification',
		'o_name'=>'course_cert',
		'o_container'=>'course_certs',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_course_certs',
		'fields'=>array(
			'course_id'=>array('ref'=>'ciniki.fatt.course'),
			'cert_id'=>array('ref'=>'ciniki.fatt.cert'),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['instructor'] = array(
		'name'=>'Instructor',
		'o_name'=>'instructor',
		'o_container'=>'instructors',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_instructors',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'status'=>array('default'=>'10'),
			'id_number'=>array('default'=>''),
			'email'=>array('default'=>''),
			'phone'=>array('default'=>''),
			'primary_image_id'=>array('default'=>'0'),
			'flags'=>array('default'=>'1'),
			'synopsis'=>array('default'=>''),
			'bio'=>array('default'=>''),
			'url'=>array('default'=>''),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['location'] = array(
		'name'=>'Location',
		'o_name'=>'location',
		'o_container'=>'locations',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_locations',
		'fields'=>array(
			'name'=>array(),
			'permalink'=>array(),
			'status'=>array('default'=>'10'),
			'address1'=>array('default'=>''),
			'address2'=>array('default'=>''),
			'city'=>array('default'=>''),
			'province'=>array('default'=>''),
			'postal'=>array('default'=>''),
			'latitude'=>array('default'=>''),
			'longitude'=>array('default'=>''),
			'url'=>array('default'=>''),
			'description'=>array('default'=>''),
			'num_seats'=>array('default'=>'0'),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['cert'] = array(
		'name'=>'Certification',
		'o_name'=>'cert',
		'o_container'=>'certs',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_certs',
		'fields'=>array(
			'name'=>array(),
			'grouping'=>array('default'=>''),
			'status'=>array('default'=>'10'),
			'years_valid'=>array('default'=>'1'),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['certcustomer'] = array(
		'name'=>'Certification Customer',
		'o_name'=>'certcustomer',
		'o_container'=>'certcustomers',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_cert_customers',
		'fields'=>array(
			'cert_id'=>array('ref'=>'ciniki.fatt.cert'),
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'date_received'=>array(),
			'date_expiry'=>array(),
			'flags'=>array('default'=>'1'),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['message'] = array(
		'name'=>'Message',
		'o_name'=>'message',
		'o_container'=>'messages',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_messages',
		'fields'=>array(
			'object'=>array(),
			'object_id'=>array(),
			'days'=>array(),
			'subject'=>array(),
			'message'=>array(),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
