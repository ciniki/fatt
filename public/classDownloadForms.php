<?php
//
// Description
// ===========
// This method returns the pdf forms filled out for a class.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the cert is attached to.
// cert_id:     The ID of the cert to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_classDownloadForms($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.classDownloadForms'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the class details
    //
    $sp = explode('-', $args['class_id']);
    if( count($sp) < 2 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3541', 'msg'=>'Invalid appointment'));
    }
    $args['start_ts'] = $sp[0];
    $args['location_id'] = $sp[1];

    $strsql = "SELECT DISTINCT ciniki_fatt_offering_dates.offering_id "
        . "FROM ciniki_fatt_offering_dates "
        . "WHERE ciniki_fatt_offering_dates.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND UNIX_TIMESTAMP(ciniki_fatt_offering_dates.start_date) = '" . ciniki_core_dbQuote($ciniki, $args['start_ts']) . "' "
        . "AND ciniki_fatt_offering_dates.location_id = '" . ciniki_core_dbQuote($ciniki, $args['location_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'offering');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) || count($rc['rows']) == 0 ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3542', 'msg'=>'No offerings for this class'));
    }
    $offering_ids = array();
    foreach($rc['rows'] as $row) {
        $offering_ids[] = $row['offering_id'];
    }
    
    //
    // Generate the forms
    //
    $rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'forms', 'generate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $fn = $rc['function_call'];
    $rc = $fn($ciniki, $args['business_id'], array('offering_ids'=>$offering_ids));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( isset($rc['pdf']) ) {
        $rc['pdf']->Output($args['class_id'] . '.pdf', 'D');
    }

    return array('stat'=>'exit');
}
?>
