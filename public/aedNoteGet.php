<?php
//
// Description
// ===========
// This method will return all the information about an aed note.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the aed note is attached to.
// note_id:          The ID of the aed note to get the details for.
//
// Returns
// -------
//
function ciniki_fatt_aedNoteGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'note_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'AED Note'),
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
    $rc = ciniki_fatt_checkAccess($ciniki, $args['tnid'], 'ciniki.fatt.aedNoteGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $php_date_format = ciniki_users_dateFormat($ciniki, 'php');
    $mysql_date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Return default for new AED Note
    //
    if( $args['note_id'] == 0 ) {
        $dt = new DateTime('now', new DateTimeZone($intl_timezone));
        $note = array('id'=>0,
            'aed_id'=>'',
            'note_date'=>$dt->format($php_date_format),
            'content'=>'',
        );
    }

    //
    // Get the details for an existing AED Note
    //
    else {
        $strsql = "SELECT ciniki_fatt_aed_notes.id, "
            . "ciniki_fatt_aed_notes.aed_id, "
            . "IFNULL(DATE_FORMAT(ciniki_fatt_aed_notes.note_date, '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "'), '') AS note_date, "
            . "ciniki_fatt_aed_notes.content "
            . "FROM ciniki_fatt_aed_notes "
            . "WHERE ciniki_fatt_aed_notes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_fatt_aed_notes.id = '" . ciniki_core_dbQuote($ciniki, $args['note_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'note');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.53', 'msg'=>'AED Note not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['note']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.54', 'msg'=>'Unable to find AED Note'));
        }
        $note = $rc['note'];
    }

    return array('stat'=>'ok', 'note'=>$note);
}
?>
