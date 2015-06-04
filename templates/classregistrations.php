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
function ciniki_fatt_templates_classregistrations(&$ciniki, $business_id, $class_id, $business_details, $fatt_settings) {

	//
	// Load the class
	//
	$rsp = array('stat'=>'ok');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'private', 'classLoad');
	$rc = ciniki_fatt_classLoad($ciniki, $business_id, array('class_id'=>$class_id));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['class']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'2423', 'msg'=>'Unable to find requested class'));
	}
	$class = $rc['class'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
	//
	// Load TCPDF library
	//
	require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

	class MYPDF extends TCPDF {
		public $left_margin = 18;
		public $right_margin = 18;
		public $top_margin = 18;
		//Page header
		public $header_image = null;
		public $header_name = '';
		public $header_addr = array();
		public $header_details = array();
		public $header_height = 0;		// The height of the image and address
		public $business_details = array();
		public $fatt_settings = array();

		public function Header() {
			//
			// Check if there is an image to be output in the header.   The image
			// will be displayed in a narrow box if the contact information is to
			// be displayed as well.  Otherwise, image is scaled to be 100% page width
			// but only to a maximum height of the header_height (set far below).
			//
/*			$img_width = 0;
			if( $this->header_image != null ) {
				$height = $this->header_image->getImageHeight();
				$width = $this->header_image->getImageWidth();
				$image_ratio = $width/$height;
				if( count($this->header_addr) == 0 && $this->header_name == '' ) {
					$img_width = 180;
				} else {
					$img_width = 120;
				}
				$available_ratio = $img_width/$this->header_height;
				// Check if the ratio of the image will make it too large for the height,
				// and scaled based on either height or width.
				if( $available_ratio < $image_ratio ) {
					$this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, $this->top_margin, 
						$img_width, 0, 'JPEG', '', 'TL', 2, '150');
				} else {
					$this->Image('@'.$this->header_image->getImageBlob(), $this->left_margin, $this->top_margin, 
						0, $this->header_height-6, 'JPEG', '', 'TL', 2, '150');
				}
			}

			//
			// Add the contact information
			//
			if( !isset($this->fatt_settings['default-header-contact-position']) 
				|| $this->fatt_settings['default-header-contact-position'] != 'off' ) {
				if( isset($this->fatt_settings['default-header-contact-position'])
					&& $this->fatt_settings['default-header-contact-position'] == 'left' ) {
					$align = 'L';
				} elseif( isset($this->fatt_settings['default-header-contact-position'])
					&& $this->fatt_settings['default-header-contact-position'] == 'right' ) {
					$align = 'R';
				} else {
					$align = 'C';
				}
				$this->Ln(1);
				if( $this->header_name != '' ) {
					$this->SetFont('times', 'B', 20);
					if( $img_width > 0 ) {
						$this->Cell($img_width, 10, '', 0);
					}
					$this->Cell(180-$img_width, 10, $this->header_name, 
						0, false, $align, 0, '', 0, false, 'M', 'M');
					$this->Ln(5);
				}
				$this->SetFont('times', '', 10);
				if( count($this->header_addr) > 0 ) {
					$address_lines = count($this->header_addr);
					if( $img_width > 0 ) {
						$this->Cell($img_width, ($address_lines*5), '', 0);
					}
					$this->MultiCell(180-$img_width, $address_lines, implode("\n", $this->header_addr), 
						0, $align, 0, 0, '', '', true, 0, false, true, 0, 'M', false);
					$this->Ln();
				}
			}
*/
/*			//
			// Output the default details which should be at the top of each page.
			//
			$this->SetCellPadding(2);
			if( count($this->header_details) <= 6 ) {
				if( $this->header_name == '' && count($this->header_addr) == 0 ) {
					$this->Ln($this->header_height+6);
				} elseif( $this->header_name == '' && count($this->header_addr) > 0 ) {
					$used_space = 4 + count($this->header_addr)*5;
					if( $used_space < 30 ) {
						$this->Ln(30-$used_space+5);
					} else {
						$this->Ln(7);
					}
				} elseif( $this->header_name != '' && count($this->header_addr) > 0 ) {
					$used_space = 10 + count($this->header_addr)*5;
					if( $used_space < 30 ) {
						$this->Ln(30-$used_space+6);
					} else {
						$this->Ln(5);
					}
				} elseif( $this->header_name != '' && count($this->header_addr) == 0 ) {
					$this->Ln(25);
				}
				$this->SetFont('times', '', 10);
				$num_elements = count($this->header_details);
				if( $num_elements == 3 ) {
					$w = array(60,60,60);
				} elseif( $num_elements == 4 ) {
					$w = array(45,45,45,45);
				} elseif( $num_elements == 5 ) {
					$w = array(36,36,36,36,36);
				} else {
					$w = array(30,30,30,30,30,30);
				}
				$lh = 6;
				$this->SetFont('', 'B');
				for($i=0;$i<$num_elements;$i++) {
					if( $this->header_details[$i]['label'] != '' ) {
						$this->SetFillColor(224);
						$this->Cell($w[$i], $lh, $this->header_details[$i]['label'], 1, 0, 'C', 1);
					} else {
						$this->SetFillColor(255);
						$this->Cell($w[$i], $lh, '', 'T', 0, 'C', 1);
					}
				}
				$this->Ln();
				$this->SetFillColor(255);
				$this->SetFont('');
				for($i=0;$i<$num_elements;$i++) {
					if( $this->header_details[$i]['label'] != '' ) {
						$this->Cell($w[$i], $lh, $this->header_details[$i]['value'], 1, 0, 'C', 1);
					} else {
						$this->Cell($w[$i], $lh, '', 0, 0, 'C', 1);
					}
				}
				$this->Ln();
			} */
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
	$pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

	//
	// Figure out the header business name and address information
	//
	$pdf->header_height = 0;
	$pdf->header_name = '';
	if( !isset($fatt_settings['default-header-contact-position'])
		|| $fatt_settings['default-header-contact-position'] != 'off' ) {
		if( !isset($fatt_settings['default-header-name'])
			|| $fatt_settings['default-header-name'] == 'yes' ) {
			$pdf->header_name = $business_details['name'];
			$pdf->header_height = 8;
		}
		if( !isset($fatt_settings['default-header-address'])
			|| $fatt_settings['default-header-address'] == 'yes' ) {
			if( isset($business_details['contact.address.street1']) 
				&& $business_details['contact.address.street1'] != '' ) {
				$pdf->header_addr[] = $business_details['contact.address.street1'];
			}
			if( isset($business_details['contact.address.street2']) 
				&& $business_details['contact.address.street2'] != '' ) {
				$pdf->header_addr[] = $business_details['contact.address.street2'];
			}
			$city = '';
			if( isset($business_details['contact.address.city']) 
				&& $business_details['contact.address.city'] != '' ) {
				$city .= $business_details['contact.address.city'];
			}
			if( isset($business_details['contact.address.province']) 
				&& $business_details['contact.address.province'] != '' ) {
				$city .= ($city!='')?', ':'';
				$city .= $business_details['contact.address.province'];
			}
			if( isset($business_details['contact.address.postal']) 
				&& $business_details['contact.address.postal'] != '' ) {
				$city .= ($city!='')?'  ':'';
				$city .= $business_details['contact.address.postal'];
			}
			if( $city != '' ) {
				$pdf->header_addr[] = $city;
			}
		}
		if( !isset($fatt_settings['default-header-phone'])
			|| $fatt_settings['default-header-phone'] == 'yes' ) {
			if( isset($business_details['contact.phone.number']) 
				&& $business_details['contact.phone.number'] != '' ) {
				$pdf->header_addr[] = 'phone: ' . $business_details['contact.phone.number'];
			}
			if( isset($business_details['contact.tollfree.number']) 
				&& $business_details['contact.tollfree.number'] != '' ) {
				$pdf->header_addr[] = 'phone: ' . $business_details['contact.tollfree.number'];
			}
		}
		if( !isset($fatt_settings['default-header-cell'])
			|| $fatt_settings['default-header-cell'] == 'yes' ) {
			if( isset($business_details['contact.cell.number']) 
				&& $business_details['contact.cell.number'] != '' ) {
				$pdf->header_addr[] = 'cell: ' . $business_details['contact.cell.number'];
			}
		}
		if( (!isset($fatt_settings['default-header-fax'])
			|| $fatt_settings['default-header-fax'] == 'yes')
			&& isset($business_details['contact.fax.number']) 
			&& $business_details['contact.fax.number'] != '' ) {
			$pdf->header_addr[] = 'fax: ' . $business_details['contact.fax.number'];
		}
		if( (!isset($fatt_settings['default-header-email'])
			|| $fatt_settings['default-header-email'] == 'yes')
			&& isset($business_details['contact.email.address']) 
			&& $business_details['contact.email.address'] != '' ) {
			$pdf->header_addr[] = $business_details['contact.email.address'];
		}
		if( (!isset($fatt_settings['default-header-website'])
			|| $fatt_settings['default-header-website'] == 'yes')
			&& isset($business_details['contact-website-url']) 
			&& $business_details['contact-website-url'] != '' ) {
			$pdf->header_addr[] = $business_details['contact-website-url'];
		}
	}
	$pdf->header_height += (count($pdf->header_addr)*5);

	//
	// Set the minimum header height
	//
	if( $pdf->header_height < 30 ) {
//		$pdf->header_height = 30;
	}

	//
	// Load the header image
	//
	if( isset($fatt_settings['default-header-image']) && $fatt_settings['default-header-image'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
		$rc = ciniki_images_loadImage($ciniki, $business_id, 
			$fatt_settings['default-header-image'], 'original');
		if( $rc['stat'] == 'ok' ) {
			$pdf->header_image = $rc['image'];
//			if( $pdf->header_height < 30 ) {
//				$pdf->header_height = 30;
//			}
		}
	}

	$pdf->business_details = $business_details;
	$pdf->fatt_settings = $fatt_settings;

//	print "<pre>" . print_r($class, true) . "</pre>";

	$instructors = '';
	if( isset($class['instructors']) ) {
		foreach($class['instructors'] as $iid => $instructor) {
			$instructors .= ($instructors!=''?', ':'') . $instructor['instructor']['name'];
		}
	}

	//
	// Determine the header details
	//
	$pdf->header_details = array(
		array('label'=>'Date', 'value'=>$class['start_date']),
		array('label'=>'Courses', 'value'=>$class['course_codes']),
		array('label'=>'Location', 'value'=>$class['location_name']),
		array('label'=>'Instructors', 'value'=>$instructors),
		array('label'=>'Registrations', 'value'=>count($class['registrations'])),
		);

	//
	// Setup the PDF basics
	//
	$pdf->SetCreator('Ciniki');
	$pdf->SetAuthor($business_details['name']);
	$pdf->SetTitle($class['location_code'] . ' - ' . $class['date']);
	$pdf->SetSubject('');
	$pdf->SetKeywords('');

	// set margins
	$pdf->SetMargins($pdf->left_margin, $pdf->top_margin + $pdf->header_height, $pdf->right_margin);
	$pdf->SetHeaderMargin($pdf->top_margin);


	// set font
	$pdf->SetFont('times', 'BI', 10);
	$pdf->SetCellPadding(2);

	// add a page
	$pdf->AddPage();
	$pdf->SetFillColor(255);
	$pdf->SetTextColor(0);
	$pdf->SetDrawColor(51);
	$pdf->SetLineWidth(0.15);

	//
	// Determine the billing address information
	//
/*	$baddr = array();
	if( isset($invoice['billing_name']) && $invoice['billing_name'] != '' ) {
		$baddr[] = $invoice['billing_name'];
	}
	if( isset($invoice['billing_address1']) && $invoice['billing_address1'] != '' ) {
		$baddr[] = $invoice['billing_address1'];
	}
	if( isset($invoice['billing_address2']) && $invoice['billing_address2'] != '' ) {
		$baddr[] = $invoice['billing_address2'];
	}
	$city = '';
	if( isset($invoice['billing_city']) && $invoice['billing_city'] != '' ) {
		$city = $invoice['billing_city'];
	}
	if( isset($invoice['billing_province']) && $invoice['billing_province'] != '' ) {
		$city .= (($city!='')?', ':'') . $invoice['billing_province'];
	}
	if( isset($invoice['billing_postal']) && $invoice['billing_postal'] != '' ) {
		$city .= (($city!='')?',  ':'') . $invoice['billing_postal'];
	}
	if( $city != '' ) {
		$baddr[] = $city;
	}
	if( isset($invoice['billing_country']) && $invoice['billing_country'] != '' ) {
		$baddr[] = $invoice['billing_country'];
	}

	//
	// Determine the shipping information
	//
	$saddr = array();
	if( $invoice['shipping_status'] > 0 ) {
		if( isset($invoice['shipping_name']) && $invoice['shipping_name'] != '' ) {
			$saddr[] = $invoice['shipping_name'];
		}
		if( isset($invoice['shipping_address1']) && $invoice['shipping_address1'] != '' ) {
			$saddr[] = $invoice['shipping_address1'];
		}
		if( isset($invoice['shipping_address2']) && $invoice['shipping_address2'] != '' ) {
			$saddr[] = $invoice['shipping_address2'];
		}
		$city = '';
		if( isset($invoice['shipping_city']) && $invoice['shipping_city'] != '' ) {
			$city = $invoice['shipping_city'];
		}
		if( isset($invoice['shipping_province']) && $invoice['shipping_province'] != '' ) {
			$city .= (($city!='')?', ':'') . $invoice['shipping_province'];
		}
		if( isset($invoice['shipping_postal']) && $invoice['shipping_postal'] != '' ) {
			$city .= (($city!='')?',  ':'') . $invoice['shipping_postal'];
		}
		if( $city != '' ) {
			$saddr[] = $city;
		}
		if( isset($invoice['shipping_country']) && $invoice['shipping_country'] != '' ) {
			$saddr[] = $invoice['shipping_country'];
		}
		if( isset($invoice['shipping_phone']) && $invoice['shipping_phone'] != '' ) {
			$saddr[] = 'Phone: ' . $invoice['shipping_phone'];
		}
	}

	//
	// Output the bill to and ship to information
	//
	if( $invoice['shipping_status'] > 0 ) {
		$w = array(90, 90);
	} else {
		$w = array(100, 80);
	}
	$lh = 6;
	$pdf->SetFillColor(224);
	$pdf->setCellPadding(2);
	if( count($baddr) > 0 || count($saddr) > 0 ) {
		$pdf->SetFont('', 'B');
		$pdf->Cell($w[0], $lh, 'Bill To:', 1, 0, 'L', 1);
		$border = 1;
		if( $invoice['shipping_status'] > 0 ) {
			$pdf->Cell($w[1], $lh, 'Ship To:', 1, 0, 'L', 1);
			$border = 1;
			$diff_lines = (count($baddr) - count($saddr));
			// Add padding so the boxes line up
			if( $diff_lines > 0 ) {
				for($i=0;$i<$diff_lines;$i++) {
					$saddr[] = " ";
				}
			} elseif( $diff_lines < 0 ) {
				for($i=0;$i<abs($diff_lines);$i++) {
					$baddr[] = " ";
				}
			}
		}
		$pdf->Ln($lh);	
		$pdf->SetFont('');
		$pdf->setCellPaddings(2, 4, 2, 2);
		$pdf->MultiCell($w[0], $lh, implode("\n", $baddr), $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
		if( $invoice['shipping_status'] > 0 ) {
			$pdf->MultiCell($w[1], $lh, implode("\n", $saddr), $border, 'L', 0, 0, '', '', true, 0, false, true, 0, 'T', false);
		}
		$pdf->Ln($lh);
	}
	$pdf->Ln();

	//
	// Add an extra space for invoices with few items
	//
	if( count($baddr) == 0 && count($saddr) == 0 && count($invoice['items']) < 5 ) {
		$pdf->Ln(10);
	}
*/

	//
	// Add header image if specified
	//
	if( $pdf->header_image != null ) {
		$height = $pdf->header_image->getImageHeight();
		$width = $pdf->header_image->getImageWidth();
		$image_ratio = $width/$height;
		$img_width = 60;
		$available_ratio = $img_width/40;
		// Check if the ratio of the image will make it too large for the height,
		// and scaled based on either height or width.
		if( $available_ratio < $image_ratio ) {
			$pdf->Image('@'.$pdf->header_image->getImageBlob(), $pdf->left_margin, $pdf->top_margin, 
				$img_width, 0, 'JPEG', '', 'TL', 2, '150');
		} else {
			$pdf->Image('@'.$pdf->header_image->getImageBlob(), $pdf->left_margin, $pdf->top_margin, 
				0, 35, 'JPEG', '', 'TL', 2, '150');
		}
	}

	//
	// Add the information to the first page
	//
	$w = array(25, 85);
	foreach($pdf->header_details as $detail) {
		$pdf->SetFillColor(224);
		$pdf->SetX($pdf->left_margin + 70);
		$pdf->SetFont('', 'B');
		$pdf->Cell($w[0], 6, $detail['label'], 1, 0, 'L', 1);
		$pdf->SetFillColor(255);
		$pdf->SetFont('', '');
		$pdf->Cell($w[1], 6, $detail['value'], 1, 0, 'L', 1);
		$pdf->Ln();
	}
	$pdf->Ln();

	//
	// Add the registrations
	//
	$w = array(22, 110, 15, 18, 15);
	$pdf->SetFillColor(224);
	$pdf->SetFont('', 'B');
	$pdf->SetCellPadding(2);
	$pdf->Cell($w[0], 6, 'Course', 1, 0, 'L', 1);
	$pdf->Cell($w[1], 6, 'Customer', 1, 0, 'L', 1);
	$pdf->Cell($w[2], 6, 'Initials', 1, 0, 'L', 1);
	$pdf->Cell($w[3], 6, 'Status', 1, 0, 'L', 1);
	$pdf->Cell($w[4], 6, 'P/F', 1, 0, 'L', 1);
	$pdf->Ln();
	$pdf->SetFillColor(236);
	$pdf->SetTextColor(0);
	$pdf->SetFont('');

	$fill=0;
	foreach($class['registrations'] as $reg) {
		$reg = $reg['registration'];

		//
		// Get the student information, so it can be added to the form and verified
		//
		if( $reg['customer_id'] != $reg['student_id'] ) {
			$customer_information = '[' . $reg['customer_display_name'] . '] ' . $reg['student_display_name'] . "\n";
		} else {
			$customer_information = $reg['customer_display_name'] . "\n";
		}
		if( $reg['student_id'] > 0 ) {
			$rc = ciniki_customers_hooks_customerDetails($ciniki, $business_id, 
				array('customer_id'=>$reg['student_id'], 'addresses'=>'yes', 'phones'=>'yes', 'emails'=>'yes'));
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
//			print "<pre>" . print_r($rc, true) . "</pre>";
			if( isset($rc['customer']) ) {
				$customer = $rc['customer'];
				if( isset($customer['addresses']) ) {
					foreach($customer['addresses'] as $a => $address) {
						if( count($customer['addresses']) > 1 ) {
							$flags = $address['address']['flags'];
							$comma = '';
							if( ($flags&0x01) == 0x01 ) { $label .= $comma . 'Shipping'; $comma = ', ';}
							if( ($flags&0x02) == 0x02 ) { $label .= $comma . 'Billing'; $comma = ', ';}
							if( ($flags&0x04) == 0x04 ) { $label .= $comma . 'Mailing'; $comma = ', ';}
						}
						$joined_address = $address['address']['address1'] . ", ";
						if( isset($address['address']['address2']) && $address['address']['address2'] != '' ) {
							$joined_address .= $address['address']['address2'] . ", ";
						}
						$city = '';
						$comma = '';
						if( isset($address['address']['city']) && $address['address']['city'] != '' ) {
							$city = $address['address']['city'];
							$comma = ', ';
						}
						if( isset($address['address']['province']) && $address['address']['province'] != '' ) {
							$city .= $comma . $address['address']['province'];
							$comma = ', ';
						}
						if( isset($address['address']['postal']) && $address['address']['postal'] != '' ) {
							$city .= '  ' . $address['address']['postal'];
							$comma = ', ';
						}
						if( $city != '' ) {
							$joined_address .= $city . "\n";
						}
						$customer_information .= $joined_address;
					}
				} else {
					$customer_information .= "Address: \n";
				}
				if( isset($customer['phones']) ) {
					$phones = "";
					foreach($customer['phones'] as $phone) {
						if( count($customer['phones']) > 1 ) {
							$phones .= ($phones!=''?', ':'') . $phone['phone_label'] . ': ' . $phone['phone_number'];
						} else {
							$phones .= $phone['phone_number'];
						}
					}
					if( count($customer['phones']) > 0 ) {
						$customer_information .= $phones . "\n";
					} else {
						$customer_information .= "Phone: \n";
					}
				}
				if( isset($customer['emails']) ) {
					$emails = '';
					$comma = '';
					foreach($customer['emails'] as $e => $email) {
						$emails .= ($emails!=''?', ':'') . $email['email']['address'];
					}
					$customer_information .= $emails . "\n";
				}
				if( isset($customer['birthdate']) ) {
					$customer_information .= "Birthday: " . $customer['birthdate'] . "\n";
				}
			}
		}

		// Calculate the line height required
		$lh = $pdf->getStringHeight($w[1], $customer_information);
		$lh1 = $pdf->getStringHeight($w[3], $reg['invoice_status']);
		if( $lh1 > $lh ) { $lh = $lh1; }

		// Check if we need a page break
		if( $pdf->getY() > ($pdf->getPageHeight() - $lh - $pdf->top_margin - $pdf->header_height) ) {
			$pdf->AddPage();
			$pdf->SetFillColor(224);
			$pdf->SetFont('', 'B');
			$pdf->Cell($w[0], 6, 'Course', 1, 0, 'L', 1);
			$pdf->Cell($w[1], 6, 'Customer', 1, 0, 'L', 1);
			$pdf->Cell($w[2], 6, 'Initials', 1, 0, 'L', 1);
			$pdf->Cell($w[3], 6, 'Status', 1, 0, 'L', 1);
			$pdf->Cell($w[4], 6, 'P/F', 1, 0, 'L', 1);
			$pdf->Ln();
			$pdf->SetFillColor(236);
			$pdf->SetTextColor(0);
			$pdf->SetFont('');
		}

		$pdf->MultiCell($w[0], $lh, $reg['course_code'], 1, 'L', $fill, 0);
		$pdf->MultiCell($w[1], $lh, $customer_information, 1, 'L', $fill, 0);
		$pdf->MultiCell($w[2], $lh, ' ', 1, 'L', $fill, 0);
		$pdf->MultiCell($w[3], $lh, $reg['invoice_status'], 1, 'L', $fill, 0);
		$pdf->MultiCell($w[4], $lh, ' ', 1, 'L', $fill, 0);
		$pdf->Ln();

		$fill=!$fill;
	}

	// Check if we need a page break
	if( $pdf->getY() > ($pdf->getPageHeight() - 40) ) {
		$pdf->AddPage();
	}

	return array('stat'=>'ok', 'class'=>$class, 'pdf'=>$pdf);
}
?>
