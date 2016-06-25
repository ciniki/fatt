<?php
//
// Description
// -----------
// This function will return the form information, including questions and how it maps to the form.
//
// Arguments
// ---------
// ciniki:
//
// Returns
// -------
//
function ciniki_fatt_forms_caon201495sfaDetails($ciniki, $business_id) {

    $rsp = array('stat'=>'ok');
    $rsp['questions'] = array(
        array('question'=>array(
            'title'=>'Emergency First Aid Award Items',
            'bit'=>0x01,
            'formcheck'=>'1'
            )),
        array('question'=>array(
            'title'=>'Two-rescuer CPR with AED skills: adult, child and infant',
            'bit'=>0x02,
            'formcheck'=>'2'
            )),
        array('question'=>array(
            'title'=>'Suspected spinal injury',
            'bit'=>0x04,
            'formcheck'=>'3'
            )),
        array('question'=>array(
            'title'=>'Environmental emergencies: heat, cold',
            'bit'=>0x08,
            'formcheck'=>'4'
            )),
        array('question'=>array(
            'title'=>'Bone of joint injury',
            'bit'=>0x10,
            'formcheck'=>'5'
            )),
        array('question'=>array(
            'title'=>'Check injures',
            'bit'=>0x20,
            'formcheck'=>'6'
            )),
        array('question'=>array(
            'title'=>'Suspected head injury',
            'bit'=>0x40,
            'formcheck'=>'7'
            )),
        array('question'=>array(
            'title'=>'Seizure',
            'bit'=>0x80,
            'formcheck'=>'8'
            )),
        array('question'=>array(
            'title'=>'Diabetes',
            'bit'=>0x0100,
            'formcheck'=>'9'
            )),
        array('question'=>array(
            'title'=>'Poisoning',
            'bit'=>0x0200,
            'formcheck'=>'10'
            )),
        array('question'=>array(
            'title'=>'Critical Incident Stress',
            'bit'=>0x0400,
            'formcheck'=>'11'
            )),
        array('question'=>array(
            'title'=>'Written Test',
            'bit'=>0x0800,
            'formcheck'=>'12'
            )),
        );

    return $rsp;
}
?>
