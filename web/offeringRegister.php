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
function ciniki_fatt_web_offeringRegister(&$ciniki, $settings, $tnid, $offering_uuid) {

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
        $blocks[] = array('type' => 'formmessage', 'message'=>'Unable to add the registration, please try again or contact us for help.');
        return array('stat'=>'errors', 'blocks'=>$blocks);
    }
    $offering = $rc['offering'];

    //
    // Add the registration, when either an individual or form posted with student_id
    //
    if( $ciniki['session']['account']['type'] == 10 
        || (isset($_POST['action']) && $_POST['action'] == 'add' && isset($_POST['student_id']))
        ) {
        //
        // Start the transaction
        //
        
        //
        // Find or add the invoice
        //


        //
        // Setup the offering registration
        //
        $reg_args = array(
            'customer_id' => $ciniki['session']['account']['id'],
            'invoice_id' => $invoice_id,
            'status' => 5,      // Registration, needs to be approved
            );
        if( $ciniki['session']['account']['type'] == 10 ) {
            $reg_args['student_id'] = $ciniki['session']['account']['id'];
        } else {
            $reg_args['student_id'] = $_POST['student_id'];
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.fatt.offeringregistration', $reg_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            $blocks[] = array('type' => 'formmessage', 'message'=>'Unable to add the registration, please try again or contact us for help.');
            return array('stat'=>'ok', 'blocks'=>$blocks);
        } else {
            $blocks[] = array('type' => 'formmessage', 'message'=>'Registration added');
        }


        //
        // Commit the transaction, return the blocks
        //
    }

        
/*
            //
            // Load the offering details
            //
            $form = "<form action='' method='POST'>";
            $form .= "<input type='hidden' name='action' value='add'>";
            //
            // Get the list of customers for the account
            //
            if( $ciniki['session']['account']['type'] == 20 || $ciniki['session']['account']['type'] == 30 ) {
                $form .= "<div class='ciniki-fatt-form-section'>";
                $form .= "<label for='student_id'>Student:</label>";
                $form .= "<select id='student_id' type='select' class='select' name='student_id'>";
                if( $ciniki['session']['account']['type'] == 30 ) {
                    $form .= "<option value='" . $ciniki['session']['account']['id'] . "'>Save Seat</option>";
                }
                foreach($ciniki['session']['account']['parents'] as $parent) {
                    $form .= "<option value='" . $parent['id'] . "'"
                        . ($parent['id'] == $registration['student_id'] ? ' selected' : '')
                        . ">" . $parent['display_name'] . "</option>";
                }
                foreach($ciniki['session']['account']['children'] as $child) {
                    $form .= "<option value='" . $child['id'] . "'"
                        . ($child['id'] == $registration['student_id'] ? ' selected' : '')
                        . ">" . $child['display_name'] . "</option>";
                }
                $form .= "</select>";
                $form .= "</div>";
            }
            //
            // Show the information for the course
            //
            $form .= "<div class='ciniki-fatt-form-section'>";
            $form .= "<label for='course'>Course:</label>{$registration['name']}";
            $form .= "</div>";

            $form .= "<div class='ciniki-fatt-form-section'>";
            $form .= "<label for='date'>Date:</label>{$registration['date_string']}";
            $form .= "</div>";

            $form .= "<div class='submit'><input type='submit' class='submit' value='Save'><a href=''>Cancel</a></div>";

            $form .= "</form>";
            $page['blocks'][] = array('type'=>'content', 'html'=>$form);
        }
*/
    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
