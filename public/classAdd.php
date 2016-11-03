<?php
//
// Description
// -----------
// This method will add a new class for the business. The class can be a bundle or course.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to add the offering to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_fatt_classAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        'day1'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'datetimetoutc', 'name'=>'Day 1'), 
        'day2'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Day 2'), 
        'flags'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Options'), 
        'location_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Location'), 
        'instructors'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Instructors'), 
        'address1'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Address'), 
        'address2'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Address'), 
        'city'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'City'), 
        'province'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Province'), 
        'postal'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Postal'), 
        'latitude'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Latitude'), 
        'longitude'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Longitude'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Customer'), 
        'num_seats'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Seats'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.classAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check if it's a bundle
    //
    $courses = array();
    if( strncmp($args['course_id'], 'b-', 2) == 0 ) {
        $args['bundle_id'] = substr($args['course_id'], 2);
        unset($args['course_id']);

        //
        // Lookup the bundle
        //
        $strsql = "SELECT ciniki_fatt_courses.id, "
            . "ciniki_fatt_courses.price, "
            . "ciniki_fatt_courses.num_hours, "
            . "ciniki_fatt_courses.num_days "
            . "FROM ciniki_fatt_course_bundles, ciniki_fatt_courses "
            . "WHERE ciniki_fatt_course_bundles.bundle_id = '" . ciniki_core_dbQuote($ciniki, $args['bundle_id']) . "' "
            . "AND ciniki_fatt_course_bundles.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_fatt_course_bundles.course_id = ciniki_fatt_courses.id "
            . "AND ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'course');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            $courses = $rc['rows'];
        }
    } else {
        //
        // Lookup the course
        //
        $strsql = "SELECT ciniki_fatt_courses.id, "
            . "ciniki_fatt_courses.price, "
            . "ciniki_fatt_courses.num_hours, "
            . "ciniki_fatt_courses.num_days "
            . "FROM ciniki_fatt_courses "
            . "WHERE ciniki_fatt_courses.id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
            . "AND ciniki_fatt_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'course');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['course']) ) {
            $courses = array($rc['course']);
        }
    }

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
    // Add each course
    //
    foreach($courses as $course) {
        //
        // Get a new UUID, do this first so it can be used as permalink if necessary
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
        $rc = ciniki_core_dbUUID($ciniki, 'ciniki.fatt');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $uuid = $rc['uuid'];

        //
        // Create the permalink
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $permalink = ciniki_core_makePermalink($ciniki, $uuid);

        //
        // Check the permalink doesn't already exist
        //
        $strsql = "SELECT id "
            . "FROM ciniki_fatt_offerings "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' " 
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'offering');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.77', 'msg'=>'You already have a offering with this name, please choose another name.'));
        }

        //
        // Add the offering
        //
        $offering_args = array('course_id'=>$course['id'],
            'permalink'=>$permalink,
            'price'=>$course['price'],
            'flags'=>$args['flags'],
            );
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.fatt.offering', $offering_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
        $offering_id = $rc['id'];

        //
        // Add the dates
        //
        $date_args = array('offering_id'=>$offering_id,
            'start_date'=>$args['day1'],
            'num_hours'=>min($course['num_hours'], 8),
            'day_number'=>1,
            'location_id'=>$args['location_id'],
            'address1'=>$args['address1'],
            'address2'=>$args['address2'],
            'city'=>$args['city'],
            'province'=>$args['province'],
            'postal'=>$args['postal'],
            'latitude'=>$args['latitude'],
            'longitude'=>$args['longitude'],
            );
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.fatt.offeringdate', $date_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
        $offeringdate_id = $rc['id'];

        //
        // Check for a second day
        //
        if( $course['num_days'] > 1 && isset($args['day2']) ) {
            $date_args['start_date'] = $args['day2'];
            $date_args['day_number'] = 2;
            $date_args['num_hours'] = $course['num_hours'] - 8;

            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
            $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.fatt.offeringdate', $date_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                return $rc;
            }
            $offeringdate_id = $rc['id'];
        }

        //
        // Update the instructors
        //
        if( isset($args['instructors']) && count($args['instructors']) > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateInstructors');
            $rc = ciniki_fatt_offeringUpdateInstructors($ciniki, $args['business_id'], $offering_id, $args['instructors']);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                return $rc;
            }
        }

        //
        // Check if this is not a bundle and the customer is set
        //
        if( isset($args['course_id']) && strncmp($args['course_id'], 'b-', 2) != 0 
            && isset($args['customer_id']) && $args['customer_id'] > 0 
            && isset($args['num_seats']) && $args['num_seats'] > 0 
            ) {

        }

        //
        // Update the seats
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateDatesSeats');
        $rc = ciniki_fatt_offeringUpdateDatesSeats($ciniki, $args['business_id'], $offering_id, 'yes');
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
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

    return array('stat'=>'ok');
}
?>
