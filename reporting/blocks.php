<?php
//
// Description
// -----------
// This function will return the list of available blocks to the ciniki.reporting module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_fatt_reporting_blocks(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.fatt']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.159', 'msg'=>"I'm sorry, the block you requested does not exist."));
    }

    $blocks = array();

    //
    // Return the list of blocks for the tenant
    //
    $blocks['ciniki.fatt.certexpirations'] = array(
        'name'=>'Certificate Expirations',
        'module' => 'First Aid Training',
        'options'=>array(
            'days'=>array('label'=>'Number of days', 'type'=>'text', 'size'=>'small', 'default'=>'60'),
            ),
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
