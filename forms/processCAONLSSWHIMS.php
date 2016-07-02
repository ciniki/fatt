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
function ciniki_fatt_forms_processCAONLSSWHIMS($ciniki, $business_id, &$pdf, $form) {
    if( !isset($form['businesses']) || count($form['businesses']) == 0 ) {
        return array('stat'=>'ok');
    }

    $pdf->setFont('', '', 12);

    $count = 0;
    foreach($form['businesses'] as $business) {
        //
        // The business text is the name of the business to provide the WHIMS certificate for
        //
        $business_text = $business['display_name'] . "\n";
        if( $business['address1'] != '' ) { $business_text .= $business['address1'] . "\n"; }
        if( $business['address2'] != '' ) { $business_text .= $business['address2'] . "\n"; }
        $city = '';
        if( $business['city'] != '' ) { $city .= $business['city']; }
        if( $business['province'] != '' ) { $city .= ($city != '' ? ', ':'') . $business['province']; }
        if( $business['postal'] != '' ) { $city .= ($city != '' ? '  ':'') . $business['postal']; }
        if( $city != '' ) { $business_text .= $city . "\n"; }
        
        //
        // Build the list of emloyees
        //
        $employee_text = '';
        foreach($business['registrations'] as $reg) {
            $employee_text .= $reg['display_name'] . "\n";
        }

        //
        // Calculate height
        //
        $w = array(90, 90);
        $h = $pdf->getPageHeight() - 30 - 30;
        $business_height = $pdf->getStringHeight($w[0], $business_text);
        $employee_height = $pdf->getStringHeight($w[1], $employee_text);
        $required_height = ($business_height > $employee_height ? $business_height : $employee_height) + 4;

        if( $count == 0 || $required_height > ($h - $pdf->getY()) ) {
            $pdf->AddPage();
            $pdf->SetLeftMargin(20);
            $pdf->SetCellPadding(2);
            $pdf->SetX(20);
            $pdf->SetY(30);
            if( $count == 0 ) {
                $pdf->Multicell(180, 12, 'Can you please send WSIB certificates following businesses and their employees.', 0, 'L');
                $pdf->Ln();
            }
            $pdf->SetFillColor(224);
            $pdf->SetFont('', 'B');
            $pdf->Cell($w[0], 6, 'Company', 1, 0, 'L', 1);
            $pdf->Cell($w[1], 6, 'Employees', 1, 0, 'L', 1);
            $pdf->Ln();
            $pdf->SetFillColor(255);
            $pdf->SetFont('', '');
        }
        $pdf->MultiCell($w[0], $required_height, $business_text, 1, 'L', 0, 0);
        $pdf->MultiCell($w[1], $required_height, $employee_text, 1, 'L', 0, 0);
        $pdf->Ln();

        $count++;
    }

    return array('stat'=>'ok');
}
?>
