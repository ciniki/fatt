<?php
//
// Description
// -----------
// This method will add a new aed for the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:        The ID of the business to add the AED to.
//
// Returns
// -------
// <rsp stat="ok" id="42">
//
function ciniki_fatt_aedAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
        'location'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Location'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Options'),
        'make'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Make'),
        'model'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Model Number'),
        'serial'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Serial Number'),
        'device_expiration'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Device Expiration Date'),
        'primary_battery_expiration'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Primary Battery Expiration Date'),
        'secondary_battery_expiration'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Secondary Battery Expiration Date'),
        'primary_adult_pads_expiration'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Primary Adult Pads Expiration Date'),
        'secondary_adult_pads_expiration'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Secondary Adult Pads Expiration Date'),
        'primary_child_pads_expiration'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Primary Child Pads Expiration Date'),
        'secondary_child_pads_expiration'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Secondary Child Pads Expiration Date'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
        'image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Additional Image'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to business_id as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'checkAccess');
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.aedAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Check expiration fields if specified in flags
    //
    if( ($args['flags']&0x01) == 0x01 && (!isset($args['secondary_battery_expiration']) || $args['secondary_battery_expiration'] == '') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.39', 'msg'=>'Secondary Battery Expiration must be specified.'));
    }
    if( ($args['flags']&0x10) == 0x10 && (!isset($args['primary_adult_pads_expiration']) || $args['primary_adult_pads_expiration'] == '') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.40', 'msg'=>'Primary Adult Pads Expiration must be specified.'));
    }
    if( ($args['flags']&0x20) == 0x20 && (!isset($args['primary_adult_pads_expiration']) || $args['primary_adult_pads_expiration'] == '') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.41', 'msg'=>'Secondary Adult Pads Expiration must be specified.'));
    }
    if( ($args['flags']&0x0100) == 0x0100 && (!isset($args['primary_child_pads_expiration']) || $args['primary_child_pads_expiration'] == '') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.42', 'msg'=>'Primary Child Pads Expiration must be specified.'));
    }
    if( ($args['flags']&0x0200) == 0x0200 && (!isset($args['secondary_child_pads_expiration']) || $args['secondary_child_pads_expiration'] == '') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.43', 'msg'=>'Secondary Child Pads Expiration must be specified.'));
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
    // Add the aed to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.fatt.aed', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }
    $aed_id = $rc['id'];

    //
    // Add additional image if supplied
    //
    if( isset($args['image_id']) && $args['image_id'] > 0 ) {
        //
        // Add the aed image to the database
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.fatt.aedimage', $args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            return $rc;
        }
        $aedimage_id = $rc['id'];
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

    return array('stat'=>'ok', 'id'=>$aed_id);
}
?>
