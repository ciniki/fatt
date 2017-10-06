<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_fatt_maps($ciniki) {
    $maps = array();
    $maps['location'] = array(
        'status'=>array(
            '10'=>'Active',
            '50'=>'Archive',
            ),
        );
    $maps['course'] = array(
        'status'=>array(
            '10'=>'Active',
            '50'=>'Archive',
            ),
        );
    $maps['instructor'] = array(
        'status'=>array(
            '10'=>'Active',
            '50'=>'Archive',
            ),
        );
    $maps['location'] = array(
        'status'=>array(
            '10'=>'Active',
            '50'=>'Archive',
            ),
        );
    $maps['message'] = array(
        'status'=>array(
            '0'=>'Inactive',
            '10'=>'Pending Approval',
            '20'=>'Auto Send',
            ),
        );
    $maps['cert'] = array(
        'status'=>array(
            '10'=>'Active',
            '50'=>'Archive',
            ),
        );
    $maps['offering'] = array(
        'flags'=>array(
            0=>'',
            0x01=>'Public',
            0x10=>'Online Registrations',
            ),
        );
    $maps['offeringregistration'] = array(
        'status'=>array(
            '0'=>'Unknown',
            '10'=>'Pass',
            '20'=>'Incomplete',
            '30'=>'Cancelled',
            '40'=>'No Show',
            '50'=>'Fail',
            ),
        );
    $maps['aed'] = array(
        'status'=>array(
            '10'=>'Active',
            '40'=>'Out for service',
            '60'=>'Deleted',
            ),
        );

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
