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
function ciniki_fatt_forms_processCAONLSSIFA($ciniki, $tnid, &$pdf, $form) {

    $reg_number = 0;
    $page_num = 0;
    $total_pages = ceil(count($form['registrations']) / 4);
    while($reg_number < count($form['registrations']) ) {
        $pdf->AddPage();
        $pdf->SetCellPaddings(1, 0, 1, 0);
        if( ($page_num%2) == 0 ) {
            $pdf->Image($ciniki['config']['core']['modules_dir'] . '/fatt/forms/bgCAONLSSIFA1.png', 0, 0, 216, 279, '', '', '', false, 300, '', false, false, 0);
        } else {
            $pdf->Image($ciniki['config']['core']['modules_dir'] . '/fatt/forms/bgCAONLSSIFA2.png', 0, 0, 216, 279, '', '', '', false, 300, '', false, false, 0);
        }

        //
        // Setup the registration information
        //
        $y = 72.0;
        $num_page_reg = 0;
        $pdf->SetFillColor(255,255,255);
        $pdf->setCellPadding(0);
        while($reg_number < count($form['registrations']) && $num_page_reg < 4) {
            $reg = $form['registrations'][$reg_number];
            $pdf->setFont('', '', '9');
            $pdf->Text(12, $y, $reg['display_name']);
            $pdf->Text(29, $y+9, $reg['birthyear'] . '/' . $reg['birthmonth'] . '/' . $reg['birthday']);
//            $pdf->Text(32, $y+9, $reg['birthmonth']);
//            $pdf->Text(35, $y+9, $reg['birthday']);
            $pdf->Text(46, $y+9, $reg['phone']);
            $pdf->Text(17, $y+13.5, $reg['address'] . '  ' . $reg['province']);
//            $pdf->Cell(16, $y+13.5, $reg['address'] . '  ' . $reg['province']);
            $pdf->Text(57, $y+13.5, $reg['apt']);
            $pdf->Text(13, $y+18.5, $reg['city']);
            $pdf->Text(46, $y+18.5, $reg['postal']);
            $pdf->Text(15, $y+23, $reg['email']);


            //
            // Add checkmarks
            //
            $pdf->setFont('zapfdingbats', '', 16);
            $pdf->Text(75, $y+6, "4");
            $pdf->Text(81.75, $y+6, "4");
            $pdf->Text(89, $y+6, "4");
            $pdf->Text(96.25, $y+6, "4");
            $pdf->Text(103.5, $y+6, "4"); // 5
            $pdf->Text(110.75, $y+6, "4");
            $pdf->Text(118.0, $y+6, "4");
            $pdf->Text(124.25, $y+6, "4");
            $pdf->Text(131.5, $y+6, "4");
            $pdf->Text(138.75, $y+6, "4"); // 10
            $pdf->Text(145.75, $y+6, "4");
            $pdf->Text(153.0, $y+6, "4");
            $pdf->Text(160.5, $y+6, "4");
            $pdf->Text(166.75, $y+6, "4");
            $pdf->Text(174.0, $y+6, "4"); // 15
            $pdf->Text(180.5, $y+6, "4");
            $pdf->Text(187.5, $y+6, "4");
            $pdf->Text(194.75, $y+6, "4");
            $pdf->setFont('helvetica', '', 12);

            $pdf->Text(203, $y+10, 'P');

            $y += 29.75;
            $reg_number++;
            $num_page_reg++;
        }

        // Check box is more registrations on next page
        if( $reg_number < count($form['registrations']) ) {
            $pdf->setFont('zapfdingbats', '', 16);
            $pdf->Text(8, 193.0, "4");
            $pdf->setFont('helvetica', '', 12);
        }

        if( ($page_num%2) == 0 ) {
            $pdf->setFont('helvetica', '', 11);
            $pdf->Text(46, 195, $page_num+1);
            $pdf->Text(60, 195, $total_pages);

            $pdf->Text(177, 194, count($form['registrations']));

            $pdf->setFont('', '', '9');

            // Host Information
            $pdf->Text(9, 207.5, $form['host_name']);
            $pdf->Text(79, 207.5, $form['host_area_code']);
            $pdf->Text(87, 207.5, $form['host_phone']);
            $pdf->Text(9, 215, $form['host_street']);
            $pdf->Text(9, 223, $form['host_city']);
            $pdf->Text(55, 223, $form['host_province']);
            $pdf->Text(88, 223, $form['host_postal']);

            // Exam Date
            $pdf->Text(23, 240.5, $form['exam_date']->format('Y'));
            $pdf->Text(35.5, 240.5, $form['exam_date']->format('m'));
            $pdf->Text(46, 240.5, $form['exam_date']->format('d'));
            $pdf->Text(9, 251, $form['host_name']);
            $pdf->Text(79, 251.5, $form['host_area_code']);
            $pdf->Text(87, 251.5, $form['host_phone']);
//            $pdf->setFont('zapfdingbats', '', 12);
//            $pdf->Text(($form['options']['recert'] == 'yes' ? 86.5 : 66), 256.5, "4");
            $pdf->setFont('helvetica', '', 9);

            // Instructor Information
            $pdf->Text(110, 206, $form['instructor_name']);
            $pdf->Text(180, 206, $form['instructor_id']);
            $pdf->Text(110, 213, $form['instructor_email']);
            $pdf->Text(112, 221, $form['instructor_area_code']);
            $pdf->Text(122, 221, $form['instructor_phone']);

            $pdf->setFont('zapfdingbats', '', 8);
            $pdf->Text(197, 230, "4");
            $pdf->Text(197, 257, "4");
//            $pdf->Text(109, 232, $form['examiner_name']);
//            $pdf->Text(180, 230.5, $form['examiner_id']);
//            $pdf->Text(109, 239.5, $form['examiner_email']);
//            $pdf->Text(111, 247.5, $form['examiner_area_code']);
//            $pdf->Text(122, 247.5, $form['examiner_phone']);
            $pdf->setFont('helvetica', '', 9);
        } else {
            $pdf->setFont('helvetica', '', 11);
            $pdf->Text(45, 198, $page_num+1);
            $pdf->Text(59, 198, $total_pages);

            $pdf->Text(176, 197, count($form['registrations']));

            $pdf->setFont('', '', '9');

            // Host Information
            $pdf->setFont('helvetica', '', 9);
            $pdf->Text(8, 222, $form['host_name']);
//            $pdf->Text(9.5, 230, $form['host_area_code']);
//            $pdf->Text(21, 230, $form['host_phone']);

            // Exam Date
            $pdf->Text(21, 252, $form['exam_date']->format('Y'));
            $pdf->Text(32.5, 252, $form['exam_date']->format('m'));
            $pdf->Text(45, 252, $form['exam_date']->format('d'));
//            $pdf->Text(8, 255.5, $form['host_name']);
//            $pdf->Text(9.5, 264, $form['host_area_code']);
//            $pdf->Text(21, 264, $form['host_phone']);
//            $pdf->setFont('zapfdingbats', '', 12);
//            $pdf->Text(($form['options']['recert'] == 'yes' ? 86.5 : 66), 247.75, "4");

            $pdf->setFont('zapfdingbats', '', 8);
            $pdf->Text(186, 211, "4");
            // Examiner Information
//            $pdf->Text(109, 248.5, $form['examiner_name']);
//            $pdf->Text(180, 248.5, $form['examiner_id']);
//            $pdf->Text(109, 256.5, $form['examiner_email']);
//            $pdf->Text(111, 264, $form['examiner_area_code']);
//            $pdf->Text(122, 264, $form['examiner_phone']);
            $pdf->setFont('helvetica', '', 9);
        }
        $page_num++;
    }

    return array('stat'=>'ok');
}
?>
