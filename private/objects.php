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
			'taxtype_id'=>array('default'=>'0'),
			'num_days'=>array('default'=>'1'),
			'num_hours'=>array('default'=>'0'),
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
	$objects['bundle'] = array(
		'name'=>'Bundle',
		'o_name'=>'bundle',
		'o_container'=>'bundles',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_bundles',
		'fields'=>array(
			'name'=>array(),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['course_bundle'] = array(
		'name'=>'Course Bundle',
		'o_name'=>'course_bundle',
		'o_container'=>'course_bundles',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_course_bundles',
		'fields'=>array(
			'course_id'=>array('ref'=>'ciniki.fatt.course'),
			'bundle_id'=>array('ref'=>'ciniki.fatt.bundle'),
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
			'initials'=>array(),
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
			'code'=>array(),
			'name'=>array(),
			'permalink'=>array(),
			'status'=>array('default'=>'10'),
			'flags'=>array('default'=>'0'),
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
			'colour'=>array('default'=>''),
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
			'offering_id'=>array('ref'=>'ciniki.fatt.offering'),
			'date_received'=>array(),
			'date_expiry'=>array(),
			'flags'=>array('default'=>'1'),
			'last_message_days'=>array(),
			'next_message_date'=>array(),
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
			'status'=>array(),
			'days'=>array(),
			'subject'=>array(),
			'message'=>array(),
			'parent_subject'=>array('default'=>''),
			'parent_message'=>array('default'=>''),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['offering'] = array(
		'name'=>'Course Offering',
		'o_name'=>'offering',
		'o_container'=>'offerings',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_offerings',
		'fields'=>array(
			'course_id'=>array('ref'=>'ciniki.fatt.course'),
			'permalink'=>array('default'=>''),
			'price'=>array('default'=>'0'),
			'flags'=>array('default'=>'0'),
			'start_date'=>array('default'=>''),
			'date_string'=>array('default'=>''),
			'location'=>array('default'=>''),
			'max_seats'=>array('default'=>'0'),
			'seats_remaining'=>array('default'=>'0'),
			'num_registrations'=>array('default'=>'0'),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['offeringdate'] = array(
		'name'=>'Course Offering Date',
		'o_name'=>'offeringdate',
		'o_container'=>'offeringdates',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_offering_dates',
		'fields'=>array(
			'offering_id'=>array('ref'=>'ciniki.fatt.offering'),
			'day_number'=>array('default'=>'1'),
			'start_date'=>array(),
			'num_hours'=>array(),
			'location_id'=>array('ref'=>'ciniki.fatt.location'),
			'address1'=>array('default'=>''),
			'address2'=>array('default'=>''),
			'city'=>array('default'=>''),
			'province'=>array('default'=>''),
			'postal'=>array('default'=>''),
			'latitude'=>array('default'=>'0'),
			'longitude'=>array('default'=>'0'),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['offeringinstructor'] = array(
		'name'=>'Course Offering Instructor',
		'o_name'=>'offeringinstructor',
		'o_container'=>'offeringinstructors',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_offering_instructors',
		'fields'=>array(
			'offering_id'=>array('ref'=>'ciniki.fatt.offering'),
			'instructor_id'=>array('ref'=>'ciniki.fatt.instructor'),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	$objects['offeringregistration'] = array(
		'name'=>'Course Offering registration',
		'o_name'=>'offeringregistration',
		'o_container'=>'offeringregistrations',
		'sync'=>'yes',
		'table'=>'ciniki_fatt_offering_registrations',
		'fields'=>array(
			'offering_id'=>array('ref'=>'ciniki.fatt.offering'),
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'student_id'=>array('ref'=>'ciniki.customers.customer'),
			'invoice_id'=>array('ref'=>'ciniki.sapos.invoice'),
			'status'=>array('default'=>'0'),
			'customer_notes'=>array('default'=>''),
			'notes'=>array('default'=>''),
			'test_results'=>array('default'=>'0'),
			),
		'history_table'=>'ciniki_fatt_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
