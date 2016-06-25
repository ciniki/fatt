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
// business_id:     The ID of the business the offering is attached to.
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['business_id'], 'ciniki.fatt.classUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the class details
    //
    $class = array();
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'classLoad');
    $rc = ciniki_fatt_classLoad($ciniki, $args['business_id'], $args);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['class']) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2470', 'msg'=>'Unable to find class'));
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
            $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.fatt.offeringregistration', $registration['registration']['id'], array(
                'status'=>$ciniki['request']['args'][$arg_name]), 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                return $rc;
            }
            
            //
            // Check if invoice item and possibly invoice should be removed
            //
            if( ($ciniki['request']['args'][$arg_name] == 30 || $ciniki['request']['args'][$arg_name] == 40) && $registration['registration']['invoice_id'] > 0 ) {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemDelete');
                $rc = ciniki_sapos_hooks_invoiceItemDelete($ciniki, $args['business_id'], array(
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
                $rc = ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.fatt.offeringregistration', $registration['registration']['id'], array('invoice_id'=>0), 0x04);
                if( $rc['stat'] != 'ok' ) {
                    ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                    return $rc;
                }
            }

            //
            // Update the certification
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringRegistrationUpdateCerts');
            $rc = ciniki_fatt_offeringRegistrationUpdateCerts($ciniki, $args['business_id'], $registration['registration']['id'], $registration['registration']);
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
    // Update the last_change date in the business modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
    ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'fatt');

    return array('stat'=>'ok');
}
?>
