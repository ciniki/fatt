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
// business_id:     The ID of the business the offering is attached to.
// offering_id:     The ID of the offering to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_offeringGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.offeringGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    $rsp = array('stat'=>'ok');

    if( $args['offering_id'] == 0 ) {
        //
        // The defaults for starting a new offering
        //
        $rsp['offering'] = array(
            'course_id'=>0,
            'permalink'=>'',
            'price'=>'',
            'flags'=>0,
            'instructors'=>array(),
            'registrations'=>array(),
            'date_display'=>'',
            'dates'=>array(),
            );
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringLoad');
        $rc = ciniki_fatt_offeringLoad($ciniki, $args['business_id'], $args['offering_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['offering']) ) {
            $rsp['offering'] = $rc['offering'];
        }
    }

    //
    // Get the number of remaining seats
    //

    return $rsp;
}
?>
