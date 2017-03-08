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
function ciniki_fatt_templates_aeds(&$ciniki, $business_id, $aeds) {

    //
    // Load business details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');
    $rc = ciniki_businesses_businessDetails($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $business_details = $rc['details'];
    } else {
        $business_details = array();
    }

    //
    // Load the invoice settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_fatt_settings', 'business_id', $business_id, 'ciniki.fatt', 'settings', '');
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
        public $business_details = array();
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
        $rc = ciniki_images_loadImage($ciniki, $business_id, $fatt_settings['default-header-image'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    $pdf->business_details = $business_details;
    $pdf->fatt_settings = $fatt_settings;

//  print "<pre>" . print_r($class, true) . "</pre>";

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($business_details['name']);
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
    $pdf->SetFillColor(246);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(128);
    $pdf->SetLineWidth(0.15);
    $pdf->AddPage();

    //
    // Add the information to the first page
    //
    $pdf->SetFont('helvetica', 'B', 8);
    $w = array(50, 20, 20, 22, 22, 22, 22, 22, 22, 22);
    $pdf->Cell($w[0], 6, 'Company', 1, 0, 'L', 1);
    $pdf->Cell($w[1], 6, 'Make', 1, 0, 'L', 1);
    $pdf->Cell($w[2], 6, 'Model', 1, 0, 'L', 1);
    $pdf->Cell($w[3], 6, 'Device', 1, 0, 'L', 1);
    $pdf->Cell($w[4], 6, 'Battery (A)', 1, 0, 'L', 1);
    $pdf->Cell($w[5], 6, 'Battery (B)', 1, 0, 'L', 1);
    $pdf->Cell($w[6], 6, 'Adult (A)', 1, 0, 'L', 1);
    $pdf->Cell($w[7], 6, 'Adult (B)', 1, 0, 'L', 1);
    $pdf->Cell($w[8], 6, 'Child (A)', 1, 0, 'L', 1);
    $pdf->Cell($w[9], 6, 'Child (B)', 1, 0, 'L', 1);
    $pdf->Ln();

    $pdf->SetFillColor(255);
    $pdf->SetFont('', '', 8);
    $pdf->SetCellPaddings(2, 2, 2, 2);
    $fill = 0;
    foreach($aeds as $aed) {
        $lh = 6;
        if( $pdf->getStringHeight($w[0], $aed['display_name'], false, true, '', 1) > $lh ) {
            $lh = $pdf->getStringHeight($w[0], $aed['display_name'], false, true, '', 1);
        }
        if( $pdf->getStringHeight($w[1], $aed['make'], false, true, '', 1) > $lh ) {
            $lh = $pdf->getStringHeight($w[1], $aed['make'], false, true, '', 1);
        }
        if( $pdf->getStringHeight($w[2], $aed['model'], false, true, '', 1) > $lh ) {
            $lh = $pdf->getStringHeight($w[2], $aed['model'], false, true, '', 1);
        }

        $pdf->writeHTMLCell($w[0], $lh, '', '', $aed['display_name'], 1, 0, $fill, true);
        $pdf->writeHTMLCell($w[1], $lh, '', '', $aed['make'], 1, 0, $fill, true);
        $pdf->writeHTMLCell($w[2], $lh, '', '', $aed['model'], 1, 0, $fill, true);
        $pdf->writeHTMLCell($w[3], $lh, '', '', $aed['device_expiration_text'], 1, 0, $fill, true, 'C');
        $pdf->writeHTMLCell($w[4], $lh, '', '', $aed['primary_battery_expiration_text'], 1, 0, $fill, true, 'C');
        $pdf->writeHTMLCell($w[5], $lh, '', '', $aed['secondary_battery_expiration_text'], 1, 0, $fill, true, 'C');
        $pdf->writeHTMLCell($w[6], $lh, '', '', $aed['primary_adult_pads_expiration_text'], 1, 0, $fill, true, 'C');
        $pdf->writeHTMLCell($w[7], $lh, '', '', $aed['secondary_adult_pads_expiration_text'], 1, 0, $fill, true, 'C');
        $pdf->writeHTMLCell($w[8], $lh, '', '', $aed['primary_child_pads_expiration_text'], 1, 0, $fill, true, 'C');
        $pdf->writeHTMLCell($w[9], $lh, '', '', $aed['secondary_child_pads_expiration_text'], 1, 1, $fill, true, 'C');
    }

    return array('stat'=>'ok', 'pdf'=>$pdf, 'filename'=>'aedexpirations.pdf');
}
?>
