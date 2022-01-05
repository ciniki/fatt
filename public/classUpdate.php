<?php
//
// Description
// ===========
// This method will update an offering registration and the connected invoice item.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the offering is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_fatt_classUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.classUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the class details
    //
    $class = array();
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'classLoad');
    $rc = ciniki_fatt_classLoad($ciniki, $args['tnid'], $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['class']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.81', 'msg'=>'Unable to find class'));
    }
    $class = $rc['class'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.fatt');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Check for new status for registrations
    //
    foreach($class['registrations'] as $rid => $registration) {
        $arg_name = 'registration_' . $registration['registration']['id'] . '_status';
        if( isset($ciniki['request']['args'][$arg_name]) && $ciniki['request']['args'][$arg_name] != $registration['registration']['status'] ) {
            //
            // Update the status
            //
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.offeringregistration', $registration['registration']['id'], array(
                'status'=>$ciniki['request']['args'][$arg_name]), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.179', 'msg'=>'Error updating status', 'err'=>$rc['err']));
            }
            
            //
            // Check if invoice item and possibly invoice should be removed
            //
            if( ($ciniki['request']['args'][$arg_name] == 30 || $ciniki['request']['args'][$arg_name] == 40) && $registration['registration']['invoice_id'] > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemDelete');
                $rc = ciniki_sapos_hooks_invoiceItemDelete($ciniki, $args['tnid'], array(
                    'invoice_id'=>$registration['registration']['invoice_id'],
                    'object'=>'ciniki.fatt.offeringregistration',
                    'object_id'=>$registration['registration']['id'],
                    'deleteinvoice'=>'yes',
                    ));
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                    return $rc;
                }
                //
                // Update the status
                //
                // ** This now taken care of with a hook callback from sapos module to fatt/sapos/itemDelete
/*                $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.fatt.offeringregistration', $registration['registration']['id'], array('invoice_id'=>0), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                    return $rc;
                } */
            }

            //
            // Update the certification
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringRegistrationUpdateCerts');
            $rc = ciniki_fatt_offeringRegistrationUpdateCerts($ciniki, $args['tnid'], $registration['registration']['id'], $registration['registration']);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                return $rc;
            }
        }
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
