<?php
//
// Description
// ===========
// This method will return all the information about a message.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business the message is attached to.
// message_id:      The ID of the message to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_messageGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'message_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Message'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.messageGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    if( $args['message_id'] == 0 ) {
        return array('stat'=>'ok', 'message'=>array(
            'object'=>'',
            'object_id'=>0,
            'status'=>0,
            'days'=>'',
            'subject'=>'',
            'message'=>'',
            'parent_subject'=>'',
            'parent_message'=>'',
            ));
    }

    //
    // Get the message details
    //
    $strsql = "SELECT ciniki_fatt_messages.id, "
        . "ciniki_fatt_messages.object, "
        . "ciniki_fatt_messages.object_id, "
        . "ciniki_fatt_messages.status, "
        . "ciniki_fatt_messages.days, "
        . "ciniki_fatt_messages.subject, "
        . "ciniki_fatt_messages.message, "
        . "ciniki_fatt_messages.parent_subject, "
        . "ciniki_fatt_messages.parent_message "
        . "FROM ciniki_fatt_messages "
        . "WHERE ciniki_fatt_messages.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_fatt_messages.id = '" . ciniki_core_dbQuote($ciniki, $args['message_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.messages', array(
        array('container'=>'messages', 'fname'=>'id', 'name'=>'message',
            'fields'=>array('id', 'object', 'object_id', 'status', 'days', 'subject', 'message', 'parent_subject', 'parent_message')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['messages']) || !isset($rc['messages'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.99', 'msg'=>'Unable to find message'));
    }
    $message = $rc['messages'][0]['message'];

    return array('stat'=>'ok', 'message'=>$message);
}
?>
