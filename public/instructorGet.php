<?php
//
// Description
// ===========
// This method will return all the information about a instructor.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the instructor is attached to.
// instructor_id:       The ID of the instructor to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_instructorGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'instructor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Instructor'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.instructorGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Return blank entry if nothing specified
    //
    if( $args['instructor_id'] == 0 ) {
        return array('stat'=>'ok', 'instructor'=>array(
            'name'=>'',
            'status'=>'10',
            'primary_image_id'=>'0',
            'flags'=>'1',
            ));
    }

    //
    // Get the instructor details
    //
    $strsql = "SELECT ciniki_fatt_instructors.id, "
        . "ciniki_fatt_instructors.name, "
        . "ciniki_fatt_instructors.initials, "
        . "ciniki_fatt_instructors.permalink, "
        . "ciniki_fatt_instructors.status, "
        . "ciniki_fatt_instructors.id_number, "
        . "ciniki_fatt_instructors.email, "
        . "ciniki_fatt_instructors.phone, "
        . "ciniki_fatt_instructors.primary_image_id, "
        . "ciniki_fatt_instructors.flags, "
        . "ciniki_fatt_instructors.synopsis, "
        . "ciniki_fatt_instructors.bio, "
        . "ciniki_fatt_instructors.url "
        . "FROM ciniki_fatt_instructors "
        . "WHERE ciniki_fatt_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_fatt_instructors.id = '" . ciniki_core_dbQuote($ciniki, $args['instructor_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.instructors', array(
        array('container'=>'instructors', 'fname'=>'id', 'name'=>'instructor',
            'fields'=>array('id', 'name', 'initials', 'permalink', 'status',
                'id_number', 'email', 'phone', 'primary_image_id', 'flags', 
                'synopsis', 'bio', 'url')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['instructors']) || !isset($rc['instructors'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.90', 'msg'=>'Unable to find instructor'));
    }
    $instructor = $rc['instructors'][0]['instructor'];

    return array('stat'=>'ok', 'instructor'=>$instructor);
}
?>
