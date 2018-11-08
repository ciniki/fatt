<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_fatt_web_accountSubMenuItems($ciniki, $settings, $tnid) {

    $submenu = array();

    //
    // Add 2 new menu items for Registrations and Certifications
    //
    $submenu[] = array('name'=>'Registrations', 'priority'=>600, 
        'package'=>'ciniki', 'module'=>'fatt', 
        'selected'=>($ciniki['request']['page'] == 'account' && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'registrations')?'yes':'no',
        'url'=>$ciniki['request']['base_url'] . '/account/registrations');

    $submenu[] = array('name'=>'Certifications', 'priority'=>600, 
        'package'=>'ciniki', 'module'=>'fatt', 
        'selected'=>($ciniki['request']['page'] == 'account' && isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'certifications')?'yes':'no',
        'url'=>$ciniki['request']['base_url'] . '/account/certifications');


    return array('stat'=>'ok', 'submenu'=>$submenu);
}
?>
