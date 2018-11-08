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
function ciniki_fatt_web_accountProcessRequest(&$ciniki, $settings, $tnid, $args) {

    //
    // FIXME: Check to make sure authenticated
    //

    if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'registrations' ) {
        array_shift($ciniki['request']['uri_split']);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'web', 'accountProcessRequestRegistrations');
        return ciniki_fatt_web_accountProcessRequestRegistrations($ciniki, $settings, $tnid, $args);
    } 
    elseif( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'certifications' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'web', 'accountProcessRequestCertifications');
        return ciniki_fatt_web_accountProcessRequestCertifications($ciniki, $settings, $tnid, $args);
    } 

    return array('stat'=>'404', 'err'=>array('code'=>'ciniki.fatt.163', 'msg'=>'Invalid request', 'err'=>$rc['err']));
}
?>
