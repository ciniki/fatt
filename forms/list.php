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
        );
    
    return array('stat'=>'ok', 'forms'=>$forms);
}
?>
