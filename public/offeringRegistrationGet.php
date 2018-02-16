<?php
//
// Description
// ===========
// This method will return all the information about a offering.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the offering is attached to.
// registration_id:     The ID of the offering registration to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_offeringRegistrationGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    $dt = new DateTime('now', new DateTimeZone('UTC'));
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.offeringRegistrationGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringRegistrationLoad');
    $rc = ciniki_fatt_offeringRegistrationLoad($ciniki, $args['tnid'], $args['registration_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.110', 'msg'=>'Registration does not exist'));
    }
    $registration = $rc['registration'];

    //
    // Look up alternate course that can be switched
    //
    $strsql = "SELECT DISTINCT ciniki_fatt_offerings.id, "
        . "ciniki_fatt_offerings.course_id "
        . "FROM ciniki_fatt_offering_dates AS d1 "
        . "LEFT JOIN ciniki_fatt_offering_dates AS d2 ON ("
            . "d1.start_date = d2.start_date "
            . "AND d1.location_id = d2.location_id "
            . "AND d2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_offerings ON ("
            . "d2.offering_id = ciniki_fatt_offerings.id "
            . "AND ciniki_fatt_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_fatt_offerings.id <> '" . ciniki_core_dbQuote($ciniki, $registration['offering_id']) . "' "
            . ") "
        . "WHERE d1.offering_id = '" . ciniki_core_dbQuote($ciniki, $registration['offering_id']) . "' "
        . "AND d1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'alternate_courses', 'fname'=>'id', 'fields'=>array('id', 'course_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['alternate_courses']) ) {
        $registration['alternate_courses'] = $rc['alternate_courses'];
    }

    //
    // Lookup alternate dates they could be switch to for the same course
    //
    $strsql = "SELECT ciniki_fatt_offerings.id, "
        . "ciniki_fatt_offerings.date_string, "
        . "ciniki_fatt_offerings.location, "
        . "ciniki_fatt_offerings.seats_remaining, "
        . "ciniki_fatt_locations.colour "
        . "FROM ciniki_fatt_offerings "
        . "LEFT JOIN ciniki_fatt_offering_dates ON ("
            . "ciniki_fatt_offerings.id = ciniki_fatt_offering_dates.offering_id "
            . "AND ciniki_fatt_offering_dates.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_locations ON ("
            . "ciniki_fatt_offering_dates.location_id = ciniki_fatt_locations.id "
            . "AND ciniki_fatt_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_fatt_offerings.course_id = '" . ciniki_core_dbQuote($ciniki, $registration['course_id']) . "' "
        . "AND ciniki_fatt_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_fatt_offerings.id <> '" . ciniki_core_dbQuote($ciniki, $registration['offering_id']) . "' "
        . "AND ("
            . "ciniki_fatt_offerings.start_date > '" . ciniki_core_dbQuote($ciniki, $registration['start_date']) . "' "
            . "OR ciniki_fatt_offerings.start_date >= '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "' "
            . ") "
//        . "GROUP BY ciniki_fatt_offerings.id "
        . "ORDER BY ciniki_fatt_offerings.start_date "
        . "";
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'alternate_dates', 'fname'=>'id', 'fields'=>array('id', 'date_string', 'location', 'seats_remaining', 'colour')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['alternate_dates']) ) {
        $registration['alternate_dates'] = $rc['alternate_dates'];
    }

    //
    // Check if there are any messages for this registration
    //
    if( isset($ciniki['tenant']['modules']['ciniki.mail']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessages');
        $rc = ciniki_mail_hooks_objectMessages($ciniki, $args['tnid'], 
            array('object'=>'ciniki.fatt.registration', 'object_id'=>$registration['id'], 'xml'=>'no'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['messages']) ) {
            $registration['messages'] = $rc['messages'];
        }
    } 
    
    return array('stat'=>'ok', 'registration'=>$registration);
}
?>
