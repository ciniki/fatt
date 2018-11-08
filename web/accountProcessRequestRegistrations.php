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
function ciniki_fatt_web_accountProcessRequestRegistrations(&$ciniki, $settings, $tnid, $args) {

    $page = array(
        'title' => 'Registrations',
        'breadcrumbs' => (isset($args['breadcrumbs'])?$args['breadcrumbs']:array()),
        'blocks' => array(),
        'container-class' => 'page-account-ciniki-registrations',
    );
    $page['breadcrumbs'][] = array('name'=>'Registrations', 'url'=>$ciniki['request']['domain_base_url'] . '/account/registrations');
    $base_url = $args['base_url'] . '/registrations';

    //
    // Double check the account is logged in, should never reach this spot
    //
    if( !isset($ciniki['session']['account']['id']) ) {
        if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] == 'add'   
            && isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] != ''   
            ) {
            //
            // You must be logged in to view this page
            //
            error_log('testing');
        } else {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.fatt.164', 'msg'=>'Not logged in'));
        }
    }

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'maps');
    $rc = ciniki_fatt_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];
    
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
    // Get the registrations for the account
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
    $strsql = "SELECT regs.id, "
        . "regs.uuid, "
        . "regs.customer_id, "
        . "regs.student_id, "
        . "regs.status, "
        . "regs.status AS status_text, "
        . "IFNULL(students.first, '') AS first, "
        . "IFNULL(students.last, '') AS last, "
        . "IFNULL(students.display_name, '') AS display_name, "
        . "offerings.date_string, "
        . "courses.code, "
        . "courses.name, "
        . "DATEDIFF('" . ciniki_core_dbQuote($ciniki, $cur_date->format('Y-m-d')) . "', offerings.start_date) AS days_till_start "
        . "FROM ciniki_fatt_offering_registrations AS regs "
        . "INNER JOIN ciniki_fatt_offerings AS offerings ON ("
            . "regs.offering_id = offerings.id "
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_fatt_courses AS courses ON ("
            . "offerings.course_id = courses.id "
            . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS students ON ("
            . "regs.student_id = students.id "
            . "AND students.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE regs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND offerings.start_date >= '" . ciniki_core_dbQuote($ciniki, $cur_date->format('Y-m-d')) . "' "
        . "AND ("
            . "regs.customer_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['account']['id']) . "' ";
    if( isset($ciniki['session']['account']['parent_child_ids']) && count($ciniki['session']['account']['parent_child_ids']) > 0 ) {
        $strsql .= "OR regs.student_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $ciniki['session']['account']['parent_child_ids']) . ") ";
    }
    $strsql .= ") ";
    $strsql .= "ORDER BY offerings.start_date DESC, courses.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'uuid', 'customer_id', 'student_id', 'first', 'last', 'display_name', 'date_string', 'status', 'status_text', 
                'code', 'name', 'days_till_start'),
            'maps'=>array('status_text'=>$maps['offeringregistration']['status']),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }


    if( isset($ciniki['request']['uri_split'][0]) && $ciniki['request']['uri_split'][0] != '' ) {
        $registrations = $rc['registrations'];   
        $registration = null;
        foreach($registrations as $rid => $reg) {
            if( $reg['uuid'] == $ciniki['request']['uri_split'][0] ) {
                $registration = $reg;
                break;
            }
        }
        if( $registration == null ) {
            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Invalid registration');
        } else {
            //
            // Check if registration is to be removed
            //
            if( isset($ciniki['request']['uri_split'][1]) && $ciniki['request']['uri_split'][1] == 'cancel' ) {
                if( isset($_POST['action']) && $_POST['action'] == 'confirm' ) {
                    //
                    // FIXME: Change status to cancelled
                    //
                    if( $registration['status'] == 5 ) {
                        // Delete the registration
                    } elseif( $registration['status'] != 30 ) {
                        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.fatt.offeringregistration', $registration['id'], array(
                            'status'=>30,
                            ), 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Error canceling registration, please try again or contact us for help.');
                        } else {
                            $page['blocks'][] = array('type'=>'formmessage', 'level'=>'green', 'message'=>'The registration has been cancelled.');
                            $page['blocks'][] = array('type'=>'content', 'html'=>"<form><div class='submit'><a class='submit button' href='{$base_url}'>Continue</a></div></form>");
                            return array('stat'=>'ok', 'page'=>$page);
                        }
                    }
                } else {
                    $form = "<form action='' method='POST'>";
                    $form .= "<input type='hidden' name='action' value='confirm'>";
                    $form .= "<div class='ciniki-fatt-form-section'>";
                    $form .= "<label for='course'>Student:</label>{$registration['display_name']}";
                    $form .= "</div>";
                    $form .= "<div class='ciniki-fatt-form-section'>";
                    $form .= "<label for='course'>Course:</label>{$registration['name']}";
                    $form .= "</div>";
                    $form .= "<div class='ciniki-fatt-form-section'>";
                    $form .= "<label for='course'>Course:</label>{$registration['name']}";
                    $form .= "</div>";
                    $form .= "<div class='submit'><input type='submit' class='submit' value=' Cancel Registration '></div>";
                    $form .= "</form>";
                    $page['blocks'][] = array('type'=>'content', 'html'=>$form);

                    return array('stat'=>'ok', 'page'=>$page);
                }
            }

            elseif( isset($_POST['action']) && $_POST['action'] == 'update' ) {
                //
                // Update student id
                //
                if( $_POST['student_id'] != $registration['student_id'] ) {
                    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
                    $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.fatt.offeringregistration', $registration['id'], array(
                        'student_id'=>$_POST['student_id'],
                        ), 0x04);
                    if( $rc['stat'] != 'ok' ) {
                        $page['blocks'][] = array('type'=>'formmessage', 'level'=>'error', 'message'=>'Error updating registration, please try again or contact us for help.');
                    } else {
                        $page['blocks'][] = array('type'=>'formmessage', 'level'=>'green', 'message'=>'The registration has been updated');
                        $page['blocks'][] = array('type'=>'content', 'html'=>"<form><div class='submit'><a class='submit button' href='{$base_url}'>Continue</a></div></form>");
                        return array('stat'=>'ok', 'page'=>$page);
                    }
                } 
            }
            
            //
            // Display the edit form
            //
            else {
                $form = "<form action='' method='POST'>";
                $form .= "<input type='hidden' name='action' value='update'>";
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

                $form .= "<div class='submit'><input type='submit' class='submit' value='Save'></div>";

                $form .= "</form>";
                $page['blocks'][] = array('type'=>'content', 'html'=>$form);

                return array('stat'=>'ok', 'page'=>$page);
            }
        }
    } 
    
    //
    // If all else fails, Display the list of registrations by pending approval and approved
    //
    $pending_regs = array();
    $upcoming_regs = array();
    if( isset($rc['registrations']) && count($rc['registrations']) > 0 ) {
        foreach($rc['registrations'] as $reg) {
            if( $reg['customer_id'] == $reg['student_id'] && ($ciniki['session']['account']['type'] == 20 || $ciniki['session']['account']['type'] == 30) ) {
                $reg['display_name'] = 'Saved Seat';
            }
            $reg['unit_amount'] = 0;
            if( $reg['status'] == 30 ) {
                $reg['edit_button'] = 'Cancelled';
            } elseif( $ciniki['session']['account']['type'] == 20 || $ciniki['session']['account']['type'] == 30 ) {
                $reg['edit_button'] = "<a href='{$base_url}/{$reg['uuid']}'>Edit</a><a href='{$base_url}/{$reg['uuid']}/cancel'>Cancel</a>";
            } else {
                $reg['edit_button'] = "<a href='{$base_url}/{$reg['uuid']}/cancel'>Cancel</a>";
            }
            if( $reg['status'] == 5 ) {
                $pending_regs[] = $reg;
            } else {
                $upcoming_regs[] = $reg;
            }
        }
        if( count($pending_regs) > 0 ) {
            if( $ciniki['session']['account']['type'] == 10 ) {
                $page['blocks'][] = array('type'=>'table', 
                    'title' => 'Pending Registrations',
                    'headers' => 'yes',
                    'columns' => array(
                        array('label' => 'Course', 'field' => 'name'),
                        array('label' => 'Date', 'field' => 'date_string'),
                        array('label' => '', 'field' => 'edit_button'),
                        ),
                    'rows' => $pending_regs,
                    );
            } else {
                $page['blocks'][] = array('type'=>'table', 
                    'title' => 'Pending Registrations',
                    'headers' => 'yes',
                    'columns' => array(
                        array('label' => 'Name', 'field' => 'display_name'),
                        array('label' => 'Course', 'field' => 'name'),
                        array('label' => 'Date', 'field' => 'date_string'),
                        array('label' => '', 'field' => 'edit_button'),
                        ),
                    'rows' => $pending_regs,
                    );
            }
        }
        if( count($upcoming_regs) > 0 ) {
            if( $ciniki['session']['account']['type'] == 10 ) {
                $page['blocks'][] = array('type'=>'table', 
                    'title' => 'Upcoming Registrations',
                    'headers' => 'yes',
                    'columns' => array(
                        array('label' => 'Course', 'field' => 'name'),
                        array('label' => 'Date', 'field' => 'date_string'),
                        array('label' => '', 'field' => 'edit_button'),
                        ),
                    'rows' => $upcoming_regs,
                    );
            } else {
                $page['blocks'][] = array('type'=>'table', 
                    'title' => 'Upcoming Registrations',
                    'headers' => 'yes',
                    'columns' => array(
                        array('label' => 'Name', 'field' => 'display_name'),
                        array('label' => 'Course', 'field' => 'name'),
                        array('label' => 'Date', 'field' => 'date_string'),
                        array('label' => '', 'field' => 'edit_button'),
                        ),
                    'rows' => $upcoming_regs,
                    );
            }
        }
    } else {
        $page['blocks'][] = array('type'=>'content', 'content'=>'No upcoming registrations');
    }

    return array('stat'=>'ok', 'page'=>$page);
}
?>
