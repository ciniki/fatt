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
// business_id:			The ID of the business the offering is attached to.
// registration_id:		The ID of the offering registration to get the details for.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.offeringRegistrationGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringRegistrationLoad');
	$rc = ciniki_fatt_offeringRegistrationLoad($ciniki, $args['business_id'], $args['registration_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2677', 'msg'=>'Registration does not exist'));
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
            . "AND d2.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_offerings ON ("
            . "d2.offering_id = ciniki_fatt_offerings.id "
            . "AND ciniki_fatt_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_fatt_offerings.id <> '" . ciniki_core_dbQuote($ciniki, $registration['offering_id']) . "' "
            . ") "
        . "WHERE d1.offering_id = '" . ciniki_core_dbQuote($ciniki, $registration['offering_id']) . "' "
        . "AND d1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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

    return array('stat'=>'ok', 'registration'=>$registration);
}
?>
