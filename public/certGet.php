<?php
//
// Description
// ===========
// This method will return all the information about a cert.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the cert is attached to.
// cert_id:     The ID of the cert to get the details for.
// 
// Returns
// -------
//
function ciniki_fatt_certGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'cert_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Certification'), 
        'messages'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Messages'), 
        'certs'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Certs'), 
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.certGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Load the status maps for the text description of each status
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'maps');
    $rc = ciniki_fatt_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');

    if( $args['cert_id'] == 0 ) {   
        //
        // Return the default settings for a new cert
        //
        $rsp = array('stat'=>'ok', 'cert'=>array(
            'name'=>'',
            'grouping'=>'',
            'status'=>'10',
            'years_valid'=>'',
            'alt_cert_id'=>'0',
            ));
    } else {
        //
        // Get the cert details
        //
        $strsql = "SELECT ciniki_fatt_certs.id, "
            . "ciniki_fatt_certs.name, "
            . "ciniki_fatt_certs.grouping, "
            . "ciniki_fatt_certs.status, "
            . "ciniki_fatt_certs.years_valid, "
            . "ciniki_fatt_certs.alt_cert_id "
            . "FROM ciniki_fatt_certs "
            . "WHERE ciniki_fatt_certs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_fatt_certs.id = '" . ciniki_core_dbQuote($ciniki, $args['cert_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.certs', array(
            array('container'=>'certs', 'fname'=>'id', 'name'=>'cert',
                'fields'=>array('id', 'name', 'grouping', 'status', 'years_valid', 'alt_cert_id')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['certs']) || !isset($rc['certs'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.76', 'msg'=>'Unable to find cert'));
        }
        $rsp = array('stat'=>'ok', 'cert'=>$rc['certs'][0]['cert']);
    }

    //
    // Get the courses for the certs and the tenant
    //
    $rsp['cert']['courses'] = '';
    $strsql = "SELECT ciniki_fatt_courses.id, "
        . "ciniki_fatt_courses.name, "
        . "IFNULL(ciniki_fatt_course_certs.id, 0) AS link_id "
        . "FROM ciniki_fatt_courses "
        . "LEFT JOIN ciniki_fatt_course_certs ON ("
            . "ciniki_fatt_courses.id = ciniki_fatt_course_certs.course_id "
            . "AND ciniki_fatt_course_certs.cert_id = '" . ciniki_core_dbQuote($ciniki, $args['cert_id']) . "' "
            . "AND ciniki_fatt_course_certs.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_fatt_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_fatt_courses.name "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
        array('container'=>'courses', 'fname'=>'id', 'name'=>'item',
            'fields'=>array('id', 'name', 'link_id')),
    ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp['courses'] = array();
    if( isset($rc['courses']) ) {
        $rsp['courses'] = $rc['courses'];
        foreach($rsp['courses'] as $cid => $item) {
            if( $item['item']['link_id'] > 0 ) {
                $rsp['cert']['courses'] .= ($rsp['cert']['courses']!=''?',':'') . $item['item']['id'];
            }
            unset($rsp['courses'][$cid]['item']['link_id']);
        }
    }

    //
    // Get any messages about the cert
    //
    if( isset($args['messages']) && $args['messages'] == 'yes' 
        && ($ciniki['tenant']['modules']['ciniki.fatt']['flags']&0x20) > 0 
        ) {
        $strsql = "SELECT id, status, status AS status_text, "
            . "days, subject, message, parent_subject, parent_message "
            . "FROM ciniki_fatt_messages "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND object = 'ciniki.fatt.cert' "
            . "AND object_id = '" . ciniki_core_dbQuote($ciniki, $args['cert_id']) . "' "
            . "ORDER BY days "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.fatt', array(
            array('container'=>'messages', 'fname'=>'id', 'name'=>'message',
                'fields'=>array('id', 'status', 'status_text', 'days', 'subject', 'message', 'parent_subject', 'parent_message'),
                'maps'=>array('status_text'=>$maps['message']['status'])),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['messages']) ) {
            $rsp['cert']['messages'] = $rc['messages'];

        } else {
            $rsp['cert']['messages'] = array();
        }
    }

    //
    // Get the list of certs
    //
    if( isset($args['certs']) && $args['certs'] == 'yes' ) {
        $strsql = "SELECT id, name "
            . "FROM ciniki_fatt_certs "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['cert_id']) . "' "
            . "AND status = 10 "
            . "ORDER BY name "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'cert');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['rows']) ) {
            $rsp['certs'] = $rc['rows'];
            array_unshift($rsp['certs'], array('id'=>0, 'name'=>'None'));
        } else {
            $rsp['certs'] = array();
        }
    }

    return $rsp;
}
?>
