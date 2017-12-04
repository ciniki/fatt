<?php
//
// Description
// ===========
// This method will return all the information about a location.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the location is attached to.
// location_id:     The ID of the location to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_locationGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'location_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Location'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.locationGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    if( $args['location_id'] == 0 ) {
        return array('stat'=>'ok', 'location'=>array(
            'name'=>'',
            'status'=>'10',
            ));
    }

    //
    // Get the location details
    //
    $strsql = "SELECT ciniki_fatt_locations.id, "
        . "ciniki_fatt_locations.code, "
        . "ciniki_fatt_locations.name, "
        . "ciniki_fatt_locations.permalink, "
        . "ciniki_fatt_locations.status, "
        . "ciniki_fatt_locations.flags, "
        . "ciniki_fatt_locations.address1, "
        . "ciniki_fatt_locations.address2, "
        . "ciniki_fatt_locations.city, "
        . "ciniki_fatt_locations.province, "
        . "ciniki_fatt_locations.postal, "
        . "ciniki_fatt_locations.latitude, "
        . "ciniki_fatt_locations.longitude, "
        . "ciniki_fatt_locations.url, "
        . "ciniki_fatt_locations.description, "
        . "ciniki_fatt_locations.num_seats, "
        . "ciniki_fatt_locations.colour "
        . "FROM ciniki_fatt_locations "
        . "WHERE ciniki_fatt_locations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_fatt_locations.id = '" . ciniki_core_dbQuote($ciniki, $args['location_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'locations', 'fname'=>'id', 'name'=>'location',
            'fields'=>array('id', 'code', 'name', 'permalink', 'status', 'flags',
                'address1', 'address2', 'city', 'province', 'postal', 
                'latitude', 'longitude', 'url', 'description', 'num_seats', 'colour')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['locations']) || !isset($rc['locations'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.96', 'msg'=>'Unable to find location'));
    }
    $location = $rc['locations'][0]['location'];

    return array('stat'=>'ok', 'location'=>$location);
}
?>
