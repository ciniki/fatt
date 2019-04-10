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
function ciniki_fatt_pendingRegistrations($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'approve_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Approve Registration'), 
        'welcome'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Send Welcome Email'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.pendingRegistrations'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the list of pending registrations
    //
    $strsql = "SELECT ciniki_fatt_offering_registrations.id, "
        . "ciniki_fatt_offering_registrations.offering_id, "
        . "ciniki_fatt_offering_registrations.customer_id, "
        . "ciniki_fatt_offering_registrations.student_id, "
        . "ciniki_fatt_offering_registrations.invoice_id, "
        . "ciniki_fatt_offering_registrations.offering_id, "
        . "ciniki_fatt_offering_registrations.status, "
        . "ciniki_fatt_offering_registrations.customer_notes, "
        . "ciniki_fatt_offering_registrations.notes, "
        . "ciniki_fatt_offerings.date_string, "
        . "ciniki_fatt_offerings.location, "
        . "ciniki_fatt_courses.code, "
        . "IF(ciniki_fatt_offering_registrations.customer_id<>ciniki_fatt_offering_registrations.student_id, IFNULL(c1.display_name, ''), '') AS parent_name, "
        . "IFNULL(c1.type, '') AS customer_type, "
        . "IFNULL(c2.display_name, '') AS student_name "
        . "FROM ciniki_fatt_offering_registrations "
        . "LEFT JOIN ciniki_customers AS c1 ON ("
            . "ciniki_fatt_offering_registrations.customer_id = c1.id "
            . "AND c1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS c2 ON ("
            . "ciniki_fatt_offering_registrations.student_id = c2.id "
            . "AND c2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_offerings ON ("
            . "ciniki_fatt_offering_registrations.offering_id = ciniki_fatt_offerings.id "
            . "AND ciniki_fatt_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_fatt_courses ON ("
            . "ciniki_fatt_offerings.course_id = ciniki_fatt_courses.id "
            . "AND ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_fatt_offering_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_fatt_offering_registrations.status = 5 "
        . "ORDER BY parent_name, student_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.offerings', array(
        array('container'=>'registrations', 'fname'=>'id',
            'fields'=>array('id', 'offering_id', 'invoice_id', 'status', 'customer_id', 'student_name', 'customer_type', 
                'date_string', 'code', 'parent_name', 'location', 'customer_notes', 'notes')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

    if( isset($args['approve_id']) && $args['approve_id'] > 0 ) {
        foreach($registrations as $rid => $reg) {
            if( $reg['id'] == $args['approve_id'] ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.offeringregistration', $reg['id'], array('status'=>0), 0x07);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.177', 'msg'=>'Unable to approve registration', 'err'=>$rc['err']));
                }
                unset($registrations[$rid]);
                if( isset($args['welcome']) && $args['welcome'] == 'yes' ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'registrationWelcomeEmailSend');
                    $rc = ciniki_fatt_registrationWelcomeEmailSend($ciniki, $args['tnid'], $reg['id']);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.178', 'msg'=>'Unable to send welcome email', 'err'=>$rc['err']));
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'registrations'=>$registrations);
}
?>
