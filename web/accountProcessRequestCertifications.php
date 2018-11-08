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
function ciniki_fatt_web_accountProcessRequestCertifications(&$ciniki, $settings, $tnid, $args) {

    $page = array(
        'title'=>'Certifications',
        'breadcrumbs'=>(isset($args['breadcrumbs'])?$args['breadcrumbs']:array()),
        'blocks'=>array(),
        'container-class' => 'page-account-ciniki-certifications',
    );
    $page['breadcrumbs'][] = array('name'=>'Certifications', 'url'=>$ciniki['request']['domain_base_url'] . '/account/certifications');

    //
    // Double check the account is logged in, should never reach this spot
    //
    if( !isset($ciniki['session']['account']['id']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.164', 'msg'=>'Not logged in'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $cur_date = new DateTime('now', new DateTimezone($intl_timezone));

    //
    // Get the certs for the customer
    //
    $strsql = "SELECT cert_customers.id, "
        . "cert_customers.customer_id, "
        . "IFNULL(customers.first, '') AS first, "
        . "IFNULL(customers.last, '') AS last, "
        . "IFNULL(customers.display_name, '') AS display_name, "
        . "certs.id AS cert_id, "
        . "DATE_FORMAT(cert_customers.date_received, '" . ciniki_core_dbQuote($ciniki, '%M %d, %Y') . "') AS date_received, "
        . "DATE_FORMAT(cert_customers.date_expiry, '" . ciniki_core_dbQuote($ciniki, '%M %d, %Y') . "') AS date_expiry, "
        . "DATEDIFF('" . ciniki_core_dbQuote($ciniki, $cur_date->format('Y-m-d')) . "', cert_customers.date_expiry) AS age, "
        . "certs.name, "
        . "certs.years_valid "
        . "FROM ciniki_fatt_cert_customers AS cert_customers "
        . "LEFT JOIN ciniki_fatt_certs AS certs ON ("
            . "cert_customers.cert_id = certs.id "
            . "AND certs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "cert_customers.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE cert_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND cert_customers.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ciniki['session']['account']['parent_child_ids']) . ") "
        . "ORDER BY cert_customers.date_expiry DESC, certs.name, customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'cert');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $curcerts = array();
    $pastcerts = array();
    foreach($rc['rows'] as $row) {
        if( $row['years_valid'] > 0 ) {
            $cert['display_name'] = $row['display_name'];
            $cert['name'] = $row['name'];
            $cert['date_received'] = $row['date_received'];
            $cert['date_expiry'] = $row['date_expiry'];
            $cert['days_till_expiry'] = $row['age'];
            $cert['unit_amount'] = 0;
            if( $row['age'] < 0 ) {
                $cert['expiry_text'] = "Expiring in " . abs($row['age']) . " day" . ($row['age']<1?'s':'') . ' on ' . $cert['date_expiry'];
                $curcerts[] = $cert;
            } elseif( $row['age'] == 0 ) {
                $cert['expiry_text'] = "Expired today " . $cert['date_expiry'];
                $pastcerts[] = $cert;
            } elseif( $row['age'] > 0 ) {
                $cert['expiry_text'] = "Expired " . $row['age'] . " day" . ($row['age']>1?'s':'') . ' ago on ' . $cert['date_expiry'];
                $pastcerts[] = $cert;
            }
        }
    }
    $curcerts = array_reverse($curcerts);

    if( $ciniki['session']['account']['type'] == 10 ) {
        $page['blocks'][] = array('type'=>'table', 
            'title' => 'Valid Certifications',
            'headers' => 'yes',
            'columns' => array(
                array('label' => 'Certification', 'field' => 'name'),
                array('label' => 'Received', 'field' => 'date_received'),
                array('label' => 'Expiration', 'field' => 'expiry_text'),
                ),
            'rows' => $curcerts,
            );
        $page['blocks'][] = array('type'=>'table', 
            'title' => 'Expired Certifications',
            'headers' => 'yes',
            'columns' => array(
                array('label' => 'Certification', 'field' => 'name'),
                array('label' => 'Received', 'field' => 'date_received'),
                array('label' => 'Expiration', 'field' => 'expiry_text'),
                ),
            'rows' => $pastcerts,
            );
    } else {
        $page['blocks'][] = array('type'=>'table', 
            'title' => 'Valid Certifications',
            'headers' => 'yes',
            'columns' => array(
                array('label' => 'Name', 'field' => 'display_name'),
                array('label' => 'Certification', 'field' => 'name'),
                array('label' => 'Received', 'field' => 'date_received'),
                array('label' => 'Expiration', 'field' => 'expiry_text'),
                ),
            'rows' => $curcerts,
            );
        $page['blocks'][] = array('type'=>'table', 
            'title' => 'Expired Certifications',
            'headers' => 'yes',
            'columns' => array(
                array('label' => 'Name', 'field' => 'display_name'),
                array('label' => 'Certification', 'field' => 'name'),
                array('label' => 'Received', 'field' => 'date_received'),
                array('label' => 'Expiration', 'field' => 'expiry_text'),
                ),
            'rows' => $pastcerts,
            );
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
