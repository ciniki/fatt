<?php
//
// Description
// -----------
// This function will process a request for the FATT module
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// tnid:     The ID of the tenant to get post for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_fatt_web_offeringRegister(&$ciniki, $settings, $tnid, $offering_uuid, $base_url) {

    if( !isset($ciniki['tenant']['modules']['ciniki.fatt']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.fatt.169', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    $blocks = array();

    //
    // Load the details about the offering
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'web', 'offeringDetails');
    $rc = ciniki_fatt_web_offeringDetails($ciniki, $settings, $tnid, $offering_uuid);
    if( $rc['stat'] != 'ok' ) {
        $blocks[] = array('type' => 'formmessage', 'level'=>'error', 'message'=>'Unable to add the registration, please try again or contact us for help.');
        return array('stat'=>'errors', 'blocks'=>$blocks);
    }
    $offering = $rc['offering'];

    //
    // Add the registration, when either an individual or form posted with student_id
    //
    if( isset($_POST['action']) && $_POST['action'] == 'add' && isset($_POST['student_id']) 
        && is_numeric($_POST['student_id']) && $_POST['student_id'] > 0 
        ) {
        $student_id = $_POST['student_id'];
//        $blocks[] = array('type'=>'content', 'html'=>'<pre>' . print_r($_POST, true) . '</pre>');
        //
        // Check for parent_id
        //
        $strsql = "SELECT parent_id "
            . "FROM ciniki_customers "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $student_id) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['customer']) ) {
            $blocks[] = array('type' => 'formmessage', 'level'=>'error', 'message'=>'You are unable to register at this time, please try again or contact us for help.');
            return array('stat'=>'errors', 'blocks'=>$blocks);
        }
        if( $rc['customer']['parent_id'] > 0 ) {
            $customer_id = $rc['customer']['parent_id'];
        } else {
            $customer_id = $student_id;
        }

        //
        // Check for an existing invoice
        //
        $strsql = "SELECT r1.invoice_id, r1.customer_id "
            . "FROM ciniki_fatt_offering_dates AS d1, ciniki_fatt_offering_dates AS d2, ciniki_fatt_offering_registrations AS r1 "
            . "WHERE d1.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['id']) . "' "
            . "AND d1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND d1.start_date = d2.start_date "
            . "AND d1.location_id = d2.location_id "
            . "AND d2.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND d2.offering_id = r1.offering_id "
            . "AND r1.customer_id = '" . ciniki_core_dbQuote($ciniki, $customer_id) . "' "
            . "AND r1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'registration');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $invoice_id = 0;
        if( isset($rc['rows']) ) {
            foreach($rc['rows'] as $row) {
                if( $row['customer_id'] == $customer_id && $row['invoice_id'] > 0 ) {
                    $invoice_id = $row['invoice_id'];
                }
            }
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
        // Add an invoice if one does not already exist
        //
        if( $invoice_id == 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAdd');
            $rc = ciniki_sapos_invoiceAdd($ciniki, $tnid, array(
                'customer_id'=>$customer_id,
                'status'=>10,
                'payment_status'=>10,
                'objects'=>array(
                    'object' => array('object' => 'ciniki.fatt.offering', 'id' => $offering['id'], 'registration_status' => 5, 'student_id'=>$student_id),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
                $blocks[] = array('type' => 'formmessage', 'level'=>'error', 'message'=>'We had a problem setting up your cart, please try again or contact us for help.');
                return array('stat'=>'errors', 'blocks'=>$blocks);
            }
            $invoice_id = $rc['id'];
        } else {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceAddItem');
            $rc = ciniki_sapos_invoiceAddItem($ciniki, $tnid, array(
                'invoice_id' => $invoice_id,
                'object'=>'ciniki.fatt.offering', 
                'object_id' => $offering['id'],
                'quantity' => 1,
                'registration_status' => 5,
                'student_id' => $student_id,
                ));
            if( $rc['stat'] != 'ok' ) {
                $blocks[] = array('type' => 'formmessage', 'level'=>'error', 'message'=>'We had a problem setting up your cart, please try again or contact us for help.');
                return array('stat'=>'errors', 'blocks'=>$blocks);
            }
        }
        
        //
        // Setup the offering registration
        //
/*        $reg_args = array(
            'offering_id' => $offering['id'],
            'customer_id' => $customer_id,
            'student_id' => $student_id,
            'invoice_id' => $invoice_id,
            'status' => 5,      // Registration, needs to be approved
            );
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.fatt.offeringregistration', $reg_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.fatt');
            $blocks[] = array('type' => 'formmessage', 'level'=>'error', 'message'=>'Unable to add the registration, please try again or contact us for help.');
            return array('stat'=>'errors', 'blocks'=>$blocks);
        }  */
        $blocks[] = array('type' => 'formmessage', 'level'=>'success', 'message'=>'Registration saved');

/*        //
        // Update the seat count
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'offeringUpdateDatesSeats');
        $rc = ciniki_fatt_offeringUpdateDatesSeats($ciniki, $tnid, $offering['id']);
        if( $rc['stat'] != 'ok' ) {
            error_log('ERR: Unable to update offeringUpdateDatesSeats');
        } */

        //
        // Commit the transaction
        //
        $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.fatt');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        return array('stat'=>'added', 'blocks'=>$blocks);
    }

    $form = "<form class='wide' action='' method='POST'>";
    $form .= "<input type='hidden' name='action' value='add'>";
    //
    // Get the list of customers for the account
    //
    if( $ciniki['session']['account']['type'] == 20 || $ciniki['session']['account']['type'] == 30 ) {
        if( isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] > 0 ) {
            $student_id = $_GET['id'];
        } elseif( !isset($student_id) && isset($registration['student_id']) ) {
            $student_id = $registration['student_id'];
        }
        $form .= "<div class='ciniki-fatt-form-section'>";
        $form .= "<label for='student_id'>Student:</label>";
        $form .= "<select id='student_id' type='select' class='select' name='student_id' onchange='addChildCheck();'>";
        if( $ciniki['session']['account']['type'] == 30 ) {
            $form .= "<option value='" . $ciniki['session']['account']['id'] . "'>Save Seat</option>";
        }
        foreach($ciniki['session']['account']['parents'] as $parent) {
            $form .= "<option value='" . $parent['id'] . "'"
                . (isset($student_id) && $parent['id'] == $student_id ? ' selected' : '')
                . ">" . $parent['display_name'] . "</option>";
        }
        foreach($ciniki['session']['account']['children'] as $child) {
            $form .= "<option value='" . $child['id'] . "'"
                . (isset($student_id) && $child['id'] == $student_id ? ' selected' : '')
                . ">" . $child['display_name'] . "</option>";
        }
        if( $ciniki['session']['account']['type'] == 20 ) {
            $form .= "<option value='addchild'>Add Child</option>";
        } elseif( $ciniki['session']['account']['type'] == 30 ) {
            $form .= "<option value='addchild'>Add Employee</option>";
        }
        $form .= "</select>";
        $form .= "</div>";
//        $blocks[] = array('type'=>'content', 'html'=>'<pre>' . print_r($ciniki, true) . "</pre>");

        $form .= "<script type='text/javascript'>"
            . "function addChildCheck() {"
                . "var e=document.getElementById('student_id');"
                . "if(e.value=='addchild'){"
                    . "window.location.replace('" . $ciniki['request']['ssl_domain_base_url'] . "/account/contactinfo/add?r=" . $base_url . "');"
                . "}"
            . "}"
            . "</script>";
    } else {
        $form .= "<input type='hidden' name='student_id' value='" . ciniki_core_dbQuote($ciniki, $ciniki['session']['customer']['id']) . "'/>";
    }
    //
    // Show the information for the course
    //
//$blocks[] = array('type'=>'content', 'html'=>'<pre>' . print_r($offering, true) . '</pre>');
    $form .= "<div class='ciniki-fatt-form-section'>";
    $form .= "<label for='course'>Course:</label>{$offering['name']}";
    $form .= "</div>";

    $form .= "<div class='ciniki-fatt-form-section'>";
    $form .= "<label for='date'>Date:</label>{$offering['date_string']}";
    $form .= "</div>";

    $form .= "<div class='ciniki-fatt-form-section'>";
    $form .= "<label for='time'>Time:</label>{$offering['start_time']} - {$offering['end_time']}";
    $form .= "</div>";

    $form .= "<div class='ciniki-fatt-form-section'>";
    $form .= "<label for='city'>Location:</label>{$offering['city']}";
    $form .= "</div>";

    $form .= "<div class='submit'><input type='submit' class='submit' value='Save Registration'></div>";

    $form .= "</form>";
    $blocks[] = array('type'=>'content', 'html'=>$form);

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
