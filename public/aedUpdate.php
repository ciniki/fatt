<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_fatt_aedUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'aed_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'AED'),
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'),
        'location'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Location'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'make'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Make'),
        'model'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Model Number'),
        'serial'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Serial Number'),
        'device_expiration'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Device Expiration Date'),
        'primary_battery_expiration'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Primary Battery Expiration Date'),
        'secondary_battery_expiration'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Secondary Battery Expiration Date'),
        'primary_adult_pads_expiration'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Primary Adult Pads Expiration Date'),
        'secondary_adult_pads_expiration'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Secondary Adult Pads Expiration Date'),
        'primary_child_pads_expiration'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Primary Child Pads Expiration Date'),
        'secondary_child_pads_expiration'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Secondary Child Pads Expiration Date'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Image'),
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'),
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.aedUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $strsql = "SELECT id, uuid, flags, "
        . "device_expiration, primary_battery_expiration, secondary_battery_expiration, "
        . "primary_adult_pads_expiration, secondary_adult_pads_expiration, primary_child_pads_expiration, secondary_child_pads_expiration "
        . "FROM ciniki_fatt_aeds "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['aed_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'item');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['item']) ) {  
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.55', 'msg'=>'Unable to find AED'));
    }
    $item = $rc['item'];

    //
    // Check expiration fields if specified in flags
    //
    $flags = isset($args['flags']) ? $args['flags'] : $item['flags'];
    if( ($flags&0x01) == 0x01 && ((isset($args['device_expiration']) && $args['device_expiration'] == '') 
        || (!isset($args['device_expiration']) && $item['device_expiration'] == '0000-00-00')) 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.148', 'msg'=>'Device Expiration must be specified.'));
    }
    if( ($flags&0x04) == 0x04 && ((isset($args['secondary_battery_expiration']) && $args['secondary_battery_expiration'] == '') 
        || (!isset($args['secondary_battery_expiration']) && $item['secondary_battery_expiration'] == '0000-00-00')) 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.56', 'msg'=>'Secondary Battery Expiration must be specified.'));
    }
    if( ($flags&0x10) == 0x10 && ((isset($args['primary_adult_pads_expiration']) && $args['primary_adult_pads_expiration'] == '') 
        || (!isset($args['primary_adult_pads_expiration']) && $item['primary_adult_pads_expiration'] == '0000-00-00')) 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.57', 'msg'=>'Primary Adult Pads Expiration must be specified.'));
    }
    if( ($flags&0x20) == 0x20 && ((isset($args['secondary_adult_pads_expiration']) && $args['secondary_adult_pads_expiration'] == '') 
        || (!isset($args['secondary_adult_pads_expiration']) && $item['secondary_adult_pads_expiration'] == '0000-00-00')) 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.58', 'msg'=>'Secondary Adult Pads Expiration must be specified.'));
    }
    if( ($flags&0x0100) == 0x0100 && ((isset($args['primary_child_pads_expiration']) && $args['primary_child_pads_expiration'] == '') 
        || (!isset($args['primary_child_pads_expiration']) && $item['primary_child_pads_expiration'] == '0000-00-00')) 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.59', 'msg'=>'Primary Child Pads Expiration must be specified.'));
    }
    if( ($flags&0x0200) == 0x0200 && ((isset($args['secondary_child_pads_expiration']) && $args['secondary_child_pads_expiration'] == '') 
        || (!isset($args['secondary_child_pads_expiration']) && $item['secondary_child_pads_expiration'] == '0000-00-00')) 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.60', 'msg'=>'Secondary Child Pads Expiration must be specified.'));
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
    // Update the AED in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.aed', $args['aed_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'fatt');

    return array('stat'=>'ok');
}
?>
