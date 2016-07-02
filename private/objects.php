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
            'name'=>array('name'=>'Name'),
            'code'=>array('name'=>'Code'),
            'permalink'=>array('name'=>'Permalink'),
            'sequence'=>array('name'=>'Order', 'default'=>'0'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'primary_image_id'=>array('name'=>'Primary Image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            'price'=>array('name'=>'Price', 'default'=>'0'),
            'taxtype_id'=>array('name'=>'Tax Type', 'default'=>'0'),
            'num_days'=>array('name'=>'Number of Days', 'default'=>'1'),
            'num_hours'=>array('name'=>'Number of Hours', 'default'=>'0'),
            'num_seats_per_instructor'=>array('name'=>'Number of Seats per Instructor', 'default'=>'0'),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'cover_letter'=>array('name'=>'Cover Letter', 'default'=>''),
            'cert_form1'=>array('name'=>'1st Certification Form', 'default'=>''),
            'cert_form2'=>array('name'=>'2nd Certification Form', 'default'=>''),
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
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'sequence'=>array('name'=>'Order', 'default'=>'1'),
            'primary_image_id'=>array('name'=>'Image', 'default'=>'0'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
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
            'last_message_day'=>array('default'=>''),
            'next_message_date'=>array('default'=>''),
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
            'city'=>array('default'=>''),
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
        'name'=>'Course Offering Registration',
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
    $objects['aed'] = array(
        'name'=>'AED',
        'o_name'=>'aed',
        'o_container'=>'aeds',
        'sync'=>'yes',
        'table'=>'ciniki_fatt_aeds',
        'fields'=>array(
            'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'location'=>array('name'=>'Location'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'flags'=>array('name'=>'Options', 'default'=>0),
            'make'=>array('name'=>'Make', 'default'=>''),
            'model'=>array('name'=>'Model Number', 'default'=>''),
            'serial'=>array('name'=>'Serial Number', 'default'=>''),
            'device_expiration'=>array('name'=>'Device Expiration Date'),
            'primary_battery_expiration'=>array('name'=>'Primary Battery Expiration Date'),
            'secondary_battery_expiration'=>array('name'=>'Secondary Battery Expiration Date'),
            'primary_adult_pads_expiration'=>array('name'=>'Primary Adult Pads Expiration Date'),
            'secondary_adult_pads_expiration'=>array('name'=>'Secondary Adult Pads Expiration Date'),
            'primary_child_pads_expiration'=>array('name'=>'Primary Child Pads Expiration Date'),
            'secondary_child_pads_expiration'=>array('name'=>'Secondary Child Pads Expiration Date'),
            'primary_image_id'=>array('name'=>'Image', 'default'=>'0'),
            'notes'=>array('name'=>'Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_fatt_history',
        );
    $objects['aedimage'] = array(
        'name'=>'AED Image',
        'o_name'=>'aedimage',
        'o_container'=>'aedimages',
        'sync'=>'yes',
        'table'=>'ciniki_fatt_aed_images',
        'fields'=>array(
            'aed_id'=>array('name'=>'AED', 'ref'=>'ciniki.fatt.aed'),
            'image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image'),
            'image_date'=>array('name'=>'Date', 'default'=>''),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_fatt_history',
        );
    $objects['aednote'] = array(
        'name'=>'AED Note',
        'o_name'=>'aednote',
        'o_container'=>'aednotes',
        'sync'=>'yes',
        'table'=>'ciniki_fatt_aed_notes',
        'fields'=>array(
            'aed_id'=>array('name'=>'AED', 'ref'=>'ciniki.fatt.aed'),
            'note_date'=>array('name'=>'Date'),
            'content'=>array('name'=>'Note'),
            ),
        'history_table'=>'ciniki_fatt_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
