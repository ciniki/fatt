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
function ciniki_fatt_forms_list($ciniki, $business_id, $args) {
    
    $forms = array(
        'CA-ON-LSS-CPR-A'=>array('id'=>'CA-ON-LSS-CPR-A', 'name'=>'Ontario LSS - CPR-A', 
            'processor'=>'CAONLSSCPR', 
            'options'=>array('level'=>'A'),
            ),
        'CA-ON-LSS-CPR-B'=>array('id'=>'CA-ON-LSS-CPR-B', 'name'=>'Ontario LSS - CPR-B', 
            'processor'=>'CAONLSSCPR', 
            'options'=>array('level'=>'B'),
            ),
        'CA-ON-LSS-CPR-C'=>array('id'=>'CA-ON-LSS-CPR-C', 'name'=>'Ontario LSS - CPR-C', 
            'processor'=>'CAONLSSCPR', 
            'options'=>array('level'=>'C'),
            ),
        'CA-ON-LSS-EFA'=>array('id'=>'CA-ON-LSS-EFA', 'name'=>'Ontario LSS - EFA', 
            'processor'=>'CAONLSSEFA', 
            'options'=>array('recert'=>''),
            ),
        'CA-ON-LSS-EFA-R'=>array('id'=>'CA-ON-LSS-EFA-R', 'name'=>'Ontario LSS - EFA-R', 
            'processor'=>'CAONLSSEFA', 
            'options'=>array('recert'=>'yes'),
            ),
        'CA-ON-LSS-SFA'=>array('id'=>'CA-ON-LSS-SFA', 'name'=>'Ontario LSS - SFA', 
            'processor'=>'CAONLSSSFA', 
            'options'=>array('recert'=>''),
            ),
        'CA-ON-LSS-SFA-R'=>array('id'=>'CA-ON-LSS-SFA-R', 'name'=>'Ontario LSS - SFA-R', 
            'processor'=>'CAONLSSSFA', 
            'options'=>array('recert'=>'yes'),
            ), 
        );
    
    return array('stat'=>'ok', 'forms'=>$forms);
}
?>
