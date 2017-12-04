<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_fatt_templates_aeds(&$ciniki, $tnid, $aeds) {

    //
    // Load tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Load the invoice settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_fatt_settings', 'tnid', $tnid, 'ciniki.fatt', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['settings']) ) {
        $fatt_settings = $rc['settings'];
    } else {
        $fatt_settings = array();
    }
    
    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 10;
        //Page header
        public $header_image = null;
        public $header_name = '';
        public $header_addr = array();
        public $header_details = array();
        public $header_height = 15;      // The height of the image and address
        public $tenant_details = array();
        public $fatt_settings = array();

        public function Header() {
            //
            // Add header image if specified
            //
            $this->SetFont('helvetica', '', 14);
            $this->Cell(244, 12, 'AED Expirations', 0, false, 'C', 0, '', 0, false, 'T', 'M');
        }

        // Page footer
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
                0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('L', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Load the header image
    //
    if( isset($fatt_settings['default-header-image']) && $fatt_settings['default-header-image'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $tnid, $fatt_settings['default-header-image'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    $pdf->tenant_details = $tenant_details;
    $pdf->fatt_settings = $fatt_settings;

//  print "<pre>" . print_r($class, true) . "</pre>";

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->SetTitle('AEDs');
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->top_margin + $pdf->header_height, $pdf->right_margin);
    $pdf->SetHeaderMargin($pdf->top_margin);


    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(2);

    // add a page
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(128);
    $pdf->SetLineWidth(0.15);
    $pdf->AddPage();

    //
    // Add the information to the first page
    //
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(246);
    $w = array(56, 44, 24, 22, 22, 22, 26, 26);
    $pdf->Cell($w[0], 6, 'Company', 1, 0, 'L', 1);
    $pdf->Cell($w[1], 6, 'Make/Model', 1, 0, 'L', 1);
    $pdf->Cell($w[2], 6, 'Serial', 1, 0, 'L', 1);
    $pdf->Cell($w[3], 6, 'Device', 1, 0, 'L', 1);
    $pdf->Cell($w[4], 6, 'Battery (A)', 1, 0, 'L', 1);
    $pdf->Cell($w[5], 6, 'Battery (B)', 1, 0, 'L', 1);
    $pdf->Cell($w[6], 6, 'Adult Pads', 1, 0, 'L', 1);
    $pdf->Cell($w[7], 6, 'Child Pads', 1, 0, 'L', 1);
    $pdf->Ln();

    $pdf->SetFillColor(255);
    $pdf->SetFont('', '', 8);
    $fill = 0;
    foreach($aeds as $aed) {
        $lh = 6;
        if( $pdf->getStringHeight($w[0], $aed['display_name'], false, true, '', 1) > $lh ) {
            $lh = $pdf->getStringHeight($w[0], $aed['display_name'], false, true, '', 1);
        }
        if( $pdf->getStringHeight($w[1], $aed['make'] . ($aed['model'] != '' ? "\n" . $aed['model'] : ''), false, true, '', 1) > $lh ) {
            $lh = $pdf->getStringHeight($w[1], $aed['make'] . ($aed['model'] != '' ? "\n" . $aed['model'] : ''), false, true, '', 1);
        }
        if( $pdf->getStringHeight($w[2], $aed['serial'], false, true, '', 1) > $lh ) {
            $lh = $pdf->getStringHeight($w[2], $aed['serial'], false, true, '', 1);
        }

        if( $pdf->GetY() > (190 - $lh) ) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetFillColor(246);
            $pdf->Cell($w[0], 6, 'Company', 1, 0, 'L', 1);
            $pdf->Cell($w[1], 6, 'Make/Model', 1, 0, 'L', 1);
            $pdf->Cell($w[2], 6, 'Serial', 1, 0, 'L', 1);
            $pdf->Cell($w[3], 6, 'Device', 1, 0, 'L', 1);
            $pdf->Cell($w[4], 6, 'Battery (A)', 1, 0, 'L', 1);
            $pdf->Cell($w[5], 6, 'Battery (B)', 1, 0, 'L', 1);
            $pdf->Cell($w[6], 6, 'Adult Pads', 1, 0, 'L', 1);
            $pdf->Cell($w[7], 6, 'Child Pads', 1, 0, 'L', 1);
            $pdf->Ln();
            $pdf->SetFillColor(255);
            $pdf->SetFont('', '', 8);
        }

        $pdf->writeHTMLCell($w[0], $lh, '', '', $aed['display_name'], 1, 0, $fill, true);
        $pdf->writeHTMLCell($w[1], $lh, '', '', $aed['make'] . ($aed['model'] != '' ? "<br/>" . $aed['model'] : ''), 1, 0, $fill, true);
        $pdf->writeHTMLCell($w[2], $lh, '', '', $aed['serial'], 1, 0, $fill, true);
        $pdf->writeHTMLCell($w[3], $lh, '', '', $aed['device_expiration_text'], 1, 0, $fill, true, 'L');
        $pdf->writeHTMLCell($w[4], $lh, '', '', $aed['primary_battery_expiration_text'], 1, 0, $fill, true, 'L');
        $pdf->writeHTMLCell($w[5], $lh, '', '', $aed['secondary_battery_expiration_text'], 1, 0, $fill, true, 'L');
        $adult = '';
        if( $aed['primary_adult_pads_expiration_text'] != '' ) {
            $adult .= '<b>A</b>: ' . $aed['primary_adult_pads_expiration_text'];
        }
        if( $aed['secondary_adult_pads_expiration_text'] != '' ) {
            $adult .= ($adult != '' ? '<br/>' : '') . '<b>B</b>: ' . $aed['secondary_adult_pads_expiration_text'];
        }
        $pdf->writeHTMLCell($w[6], $lh, '', '', $adult, 1, 0, $fill, true, 'L');
        $child = '';
        if( $aed['primary_child_pads_expiration_text'] != '' ) {
            $child .= '<b>A</b>: ' . $aed['primary_child_pads_expiration_text'];
        }
        if( $aed['secondary_child_pads_expiration_text'] != '' ) {
            $child .= ($child != '' ? '<br/>' : '') . '<b>B</b>: ' . $aed['secondary_child_pads_expiration_text'];
        }
        $pdf->writeHTMLCell($w[7], $lh, '', '', $child, 1, 1, $fill, true, 'L');
//        $pdf->writeHTMLCell($w[6], $lh, '', '', $aed['primary_adult_pads_expiration_text'], 1, 0, $fill, true, 'C');
//        $pdf->writeHTMLCell($w[7], $lh, '', '', $aed['secondary_adult_pads_expiration_text'], 1, 0, $fill, true, 'C');
//        $pdf->writeHTMLCell($w[8], $lh, '', '', $aed['primary_child_pads_expiration_text'], 1, 0, $fill, true, 'C');
//        $pdf->writeHTMLCell($w[9], $lh, '', '', $aed['secondary_child_pads_expiration_text'], 1, 1, $fill, true, 'C');
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>'aedexpirations.pdf');
}
?>
