<?php
//
// Description
// -----------
// This function will process a request for the FATT module
//
// Arguments
// ---------
// ciniki:
// settings:		The web settings structure.
// business_id:		The ID of the business to get post for.
//
// args:			The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_fatt_web_processRequest(&$ciniki, $settings, $business_id, $args) {

	if( !isset($ciniki['business']['modules']['ciniki.fatt']) ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2637', 'msg'=>"I'm sorry, the page you requested does not exist."));
	}
	$page = array(
		'title'=>$args['page_title'],
		'breadcrumbs'=>$args['breadcrumbs'],
		'blocks'=>array(),
		);

	//
	// Check if a file was specified to be downloaded
	//
/*	$download_err = '';
	if( isset($args['uri_split'][0]) && $args['uri_split'][0] != ''
		&& isset($args['uri_split'][1]) && $args['uri_split'][1] == 'download'
		&& isset($args['uri_split'][2]) && $args['uri_split'][2] != '' 
		&& preg_match("/^(.*)\.pdf$/", $args['uri_split'][2], $matches)
		) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'blog', 'web', 'downloadPDF');
		$rc = ciniki_blog_web_downloadPDF($ciniki, $settings, $business_id, $ciniki['request']['uri_split'][0], $args['uri_split'][2], $args['blogtype']);
		if( $rc['stat'] == 'ok' ) {
			return array('stat'=>'ok', 'download'=>$rc['file']);
		}
		
		//
		// If there was an error locating the files, display generic error
		//
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2606', 'msg'=>'The file you requested does not exist.'));
	}
*/

	//
	// Setup titles
	//
	if( $page['title'] == '' ) {
		$page['title'] = 'Courses';
	}
	if( count($page['breadcrumbs']) == 0 ) {
		$page['breadcrumbs'][] = array('name'=>'Courses', 'url'=>$args['base_url']);
	}

	$display = '';
	$ciniki['response']['head']['og']['url'] = $args['domain_base_url'];

	//
	// Parse the url to determine what was requested
	//
	
	//
	// Setup the base url as the base url for this page. This may be altered below
	// as the uri_split is processed, but we do not want to alter the original passed in.
	//
	$base_url = $args['base_url']; 

	//
	// Parse the URL and decide what should be displayed
	//
	$display = 'courses';
	if( ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x02) == 0x02 
		&& isset($args['uri_split'][0]) && $args['uri_split'][0] != ''
		) {
		$category_permalink = array_shift($args['uri_split']);
		ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'web', 'categoryDetails');
		$rc = ciniki_fatt_web_categoryDetails($ciniki, $settings, $business_id, $category_permalink);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$display = 'category';
		$category = $rc['category'];
		$base_url .= '/' . $category_permalink;
		$ciniki['response']['head']['og']['url'] .= '/' . $category_permalink;
		$page['title'] = $category['name'];
		$page['breadcrumbs'][] = array('name'=>$category['name'], 'url'=>$base_url);
	}

	if( isset($args['uri_split'][0]) && $args['uri_split'][0] != '' ) {
		$display = 'course';
		$course_permalink = array_shift($args['uri_split']);
		$base_url .= '/' . $course_permalink;
		$ciniki['response']['head']['og']['url'] .= '/' . $course_permalink;
	}

	if( isset($args['uri_split'][0]) && $args['uri_split'][0] != '' ) {
		$display = 'offering';
		$offering_permalink = array_shift($args['uri_split']);
		$base_url .= '/' . $offering_permalink;
		$ciniki['response']['head']['og']['url'] .= '/' . $offering_permalink;
	}

	//
	// Setup the page blocks
	//
	if( $display == 'courses' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'web', 'courses');
		$rc = ciniki_fatt_web_courses($ciniki, $settings, $business_id, array());
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2640', 'msg'=>"I'm sorry, but we can't seem to find the courses you requested.", 'err'=>$rc['err']));
		}
		//
		// If categories are enabled, then show the default list by category
		//
		if( ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x02) == 0x02 ) {
			if( !isset($rc['categories']) ) {
				return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2641', 'msg'=>"I'm sorry, but we can't seem to find the courses you requested."));
			}
			$page['blocks'][] = array('type'=>'cilist', 'base_url'=>$base_url, 'noimage'=>'yes', 'categories'=>$rc['categories'],
				'image_version'=>'thumbnail',
				'more_button_text'=>(isset($settings['page-fatt-more-button-text'])?$settings['page-fatt-more-button-text']:''),
				);
		} 
		//
		// Otherwise show the list of images
		//
		else {
			if( !isset($rc['courses']) ) {
				return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2642', 'msg'=>"I'm sorry, but we can't seem to find the courses you requested."));
			}
			$page['blocks'][] = array('type'=>'imagelist', 'base_url'=>$base_url, 'noimage'=>'yes', 'list'=>$rc['courses'],
				'image_version'=>'thumbnail',
				'more_button_text'=>(isset($settings['page-fatt-more-button-text'])?$settings['page-fatt-more-button-text']:''),
				);
		}
	}

	elseif( $display == 'category' ) {
		if( isset($category['image_id']) && $category['image_id'] > 0 ) {
			$page['blocks'][] = array('type'=>'asideimage', 'primary'=>'yes', 'image_id'=>$post['image_id'], 'title'=>$post['title'], 'caption'=>'');
		}
		if( isset($category['description']) && $category['description'] != '' ) {
			$page['blocks'][] = array('type'=>'content', 'title'=>'', 'content'=>$category['description']);
		}

		ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'web', 'courses');
		$rc = ciniki_fatt_web_courses($ciniki, $settings, $business_id, array('category_id'=>(isset($category['id'])?$category['id']:0)));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2643', 'msg'=>"I'm sorry, but we can't seem to find the courses you requested.", 'err'=>$rc['err']));
		}
		if( !isset($rc['courses']) ) {
			return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2644', 'msg'=>"I'm sorry, but we can't seem to find the courses you requested."));
		}
		$page['blocks'][] = array('type'=>'imagelist', 'base_url'=>$base_url, 'noimage'=>'yes', 'list'=>$rc['courses'],
			'image_version'=>'thumbnail',
			'more_button_text'=>(isset($settings['page-fatt-more-button-text'])?$settings['page-fatt-more-button-text']:''),
			);
	}

	elseif( $display == 'course' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'fatt', 'web', 'courseDetails');
		$rc = ciniki_fatt_web_courseDetails($ciniki, $settings, $business_id, $course_permalink);
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2645', 'msg'=>"I'm sorry, but we can't seem to find the course you requested.", 'err'=>$rc['err']));
		}
		if( !isset($rc['course']) ) {
			return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2646', 'msg'=>"I'm sorry, but we can't seem to find the course you requested."));
		}
		$course = $rc['course'];
		$page['breadcrumbs'][] = array('name'=>$course['name'], 'url'=>$course['permalink']);

		if( isset($post['synopsis']) && $post['synopsis'] != '' ) {
			$ciniki['response']['head']['og']['description'] = strip_tags($post['synopsis']);
		} elseif( isset($post['content']) && $post['content'] != '' ) {
			$ciniki['response']['head']['og']['description'] = strip_tags($post['content']);
		}

		if( isset($course['image_id']) && $course['image_id'] > 0 ) {
			$page['blocks'][] = array('type'=>'asideimage', 'primary'=>'yes', 'image_id'=>$course['image_id'], 'title'=>$course['name'], 'caption'=>'');
		}
		if( isset($course['description']) && $course['description'] != '' ) {
			$page['blocks'][] = array('type'=>'content', 'title'=>'', 'content'=>$course['description']);
		}

		if( isset($course['offerings']) && count($course['offerings']) > 0 ) {
			$page['blocks'][] = array('type'=>'pricetable', 'title'=>'Upcoming Courses', 
				'headers'=>array('When', 'Where', 'Price'),
				'fields'=>array('date_string', 'city', 'price'),
				'prices'=>$course['offerings']);
		} else {
			$page['blocks'][] = array('type'=>'message', 'title'=>'Upcoming Courses', 'content'=>'Currently no courses are scheduled.');
		}

	}

	elseif( $display == 'offering' ) {
		
	}

	//
	// Return error if nothing found to display
	//
	else {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'2592', 'msg'=>"We're sorry, the page you requested."));
	}

	//
	// If categories enabled and not category menu, display submenu as categories
	//
	if( ($ciniki['business']['modules']['ciniki.fatt']['flags']&0x02) == 0x02 
		&& isset($settings['page-fatt-submenu-categories']) && $settings['page-fatt-submenu-categories'] == 'yes'
		) {
		$strsql = "SELECT id, name, permalink, primary_image_id, synopsis, description "
			. "FROM ciniki_fatt_categories "
			. "WHERE ciniki_fatt_categories.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "ORDER BY sequence ";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.fatt', 'category');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['rows']) ) {
			$page['submenu'] = array();
			foreach($rc['rows'] as $cat) {
				$page['submenu'][$cat['permalink']] = array('name'=>$cat['name'], 'url'=>$args['base_url'] . '/' . $cat['permalink']);
			}
		}
	}

	return array('stat'=>'ok', 'page'=>$page);
}
?>
