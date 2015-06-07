//
// This file contains the UI panels to manage course information, instructors, certs, locations and messages
//
function ciniki_fatt_settings() {
	this.courseForms = {
		'':'None',
		};
	this.toggleOptions = {'no':'Hide', 'yes':'Display'};
	this.positionOptions = {'left':'Left', 'center':'Center', 'right':'Right', 'off':'Off'};

	this.init = function() {
		//
		// The menu panel
		//
		this.menu = new M.panel('Settings',
			'ciniki_fatt_settings', 'menu',
			'mc', 'narrow', 'sectioned', 'ciniki.fatt.settings.menu');
		this.menu.sections = {	
			'_':{'label':'', 'list':{
				'courses':{'label':'Courses', 'visible':'no', 'fn':'M.ciniki_fatt_settings.courseList(\'M.ciniki_fatt_settings.showMenu();\');'},
				'instructors':{'label':'Instructors', 'visible':'no', 'fn':'M.ciniki_fatt_settings.instructorList(\'M.ciniki_fatt_settings.showMenu();\');'},
				'locations':{'label':'Locations', 'visible':'no', 'fn':'M.ciniki_fatt_settings.locationList(\'M.ciniki_fatt_settings.showMenu();\');'},
				'certs':{'label':'Certifications', 'visible':'no', 'fn':'M.ciniki_fatt_settings.certList(\'M.ciniki_fatt_settings.showMenu();\');'},
				}},
			'_classes':{'label':'', 'list':{
				'documents':{'label':'Document Headers', 'visible':'yes', 'fn':'M.ciniki_fatt_settings.documentsShow(\'M.ciniki_fatt_settings.showMenu();\');'},
				}},
		};
		this.menu.addClose('Back');

		//
		// Courses
		//
		this.courses = new M.panel('Courses',
			'ciniki_fatt_settings', 'courses',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.courses');
		this.courses.sections = {
			'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Category',
				'addFn':'M.ciniki_fatt_settings.categoryEdit(\'M.ciniki_fatt_settings.courseList();\',0);',
				},
			'bundles':{'label':'Bundles', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Bundle',
				'addFn':'M.ciniki_fatt_settings.bundleEdit(\'M.ciniki_fatt_settings.courseList();\',0);',
				},
			'courses':{'label':'Courses', 'type':'simplegrid', 'num_cols':4,
				'headerValues':['Code', 'Name', 'Price', 'Status'],
				'cellClasses':['multiline', 'multiline', 'multiline', 'multiline'],
				'addTxt':'Add Course',
				'addFn':'M.ciniki_fatt_settings.courseEdit(\'M.ciniki_fatt_settings.courseList();\',0);',
				},
		};
		this.courses.cellValue = function(s, i, j, d) {
			if( s == 'categories' ) {
				return d.category.name;
			} else if( s == 'bundles' ) {
				return d.bundle.name;
			} else {
				switch(j) {
					case 0: return '<span class="maintext">' + d.course.code + '</span><span class="subtext">' + (d.course.num_seats_per_instructor>0?d.course.num_seats_per_instructor + ' seats':'unlimited') + '</span>';
					case 1: return '<span class="maintext">' + d.course.name + '</span><span class="subtext">' + d.course.num_hours + ' hour' + (d.course.num_hours!=1?'s':'') + ' over ' + d.course.num_days + ' day' + (d.course.num_days!=1?'s':'') + '</span>';
					case 2: return '<span class="maintext">' + d.course.price + '</span><span class="subtext">' + d.course.taxtype_name + '</span>';
					case 3: return '<span class="maintext">' + d.course.status_text + '</span><span class="subtext">' + d.course.visible + '</span>';
				}
			}
		};
		this.courses.sectionData = function(s) { return this.data[s]; }
		this.courses.rowFn = function(s, i, d) {
			if( s == 'categories' ) {
				return 'M.ciniki_fatt_settings.categoryEdit(\'M.ciniki_fatt_settings.courseList();\',\'' + d.category.id + '\');';
			} else if( s == 'bundles' ) {
				return 'M.ciniki_fatt_settings.bundleEdit(\'M.ciniki_fatt_settings.courseList();\',\'' + d.bundle.id + '\');';
			} else {
				return 'M.ciniki_fatt_settings.courseEdit(\'M.ciniki_fatt_settings.courseList();\',\'' + d.course.id + '\');';
			}
		};
		this.courses.addButton('add', 'Course', 'M.ciniki_fatt_settings.courseEdit(\'M.ciniki_fatt_settings.courseList();\',0);');
		this.courses.addClose('Back');

		//
		// The course edit panel
		//
		this.course = new M.panel('Course',
			'ciniki_fatt_settings', 'course',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.settings.course');
		this.course.course_id = 0;
		this.course.data = {};
		this.course.sections = {
			'image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
			'details':{'label':'', 'aside':'yes', 'fields':{
				'name':{'label':'Name', 'type':'text'},
				'code':{'label':'Code', 'type':'text', 'size':'small'},
				'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':{'10':'Active', '50':'Archived'}},
				'price':{'label':'Price', 'type':'text', 'size':'small'},
				'taxtype_id':{'label':'Tax', 'active':'no', 'type':'select', 'options':{}},
				'num_days':{'label':'Days', 'type':'toggle', 'default':'1', 'toggles':{'1':'1', '2':'2'}},
				'num_hours':{'label':'Hours', 'type':'text', 'size':'small'},
				'num_seats_per_instructor':{'label':'Seats/Instructor', 'type':'text', 'size':'tiny'},
				'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
				'cert_form':{'label':'Form', 'type':'select', 'options':this.courseForms},
				}},
			'_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
				'categories':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':{}},
				}},
			'_bundles':{'label':'Bundles', 'aside':'yes', 'active':'no', 'fields':{
				'bundles':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':{}},
				}},
			'_certs':{'label':'Certifications', 'aside':'yes', 'active':'no', 'fields':{
				'certs':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':{}},
				}},
			'_synopsis':{'label':'Synopsis', 'fields':{
				'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_description':{'label':'Description', 'fields':{
				'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
				}},
			'messages':{'label':'Messages', 'active':'no', 'type':'simplegrid', 'num_cols':2,
				'addTxt':'New Reminder',
				'addFn':'M.ciniki_fatt_settings.course.messageEdit(0)',
				},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.courseSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.courseDelete(M.ciniki_fatt_settings.course.course_id);'},
				}},
		};
		this.course.sectionData = function(s) { return this.data[s]; }
		this.course.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.course.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.courseHistory', 'args':{'business_id':M.curBusinessID, 'course_id':this.course_id, 'field':i}};
		}
		this.course.messageEdit = function(mid) {
			if( this.course_id == 0 ) {
				// Save course first 
				var c = this.serializeForm('yes');
				M.api.postJSONCb('ciniki.fatt.courseAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_fatt_settings.course.course_id = rsp.id;
					M.ciniki_fatt_settings.messageEdit('M.ciniki_fatt_settings.course.messagesUpdate();','ciniki.fatt.course',rsp.id,mid);
				});
			} else {
				M.ciniki_fatt_settings.messageEdit('M.ciniki_fatt_settings.course.messagesUpdate();','ciniki.fatt.course',this.course_id,mid);
			}
		}
		this.course.messagesUpdate = function() {
			M.api.getJSONCb('ciniki.fatt.courseGet', {'business_id':M.curBusinessID, 
				'course_id':this.course_id, 'messages':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_fatt_settings.course;
					p.data.messages = rsp.course.messages;
					p.refreshSection('messages');
					p.show();
			});
		}
		this.course.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				if( d.message.days < 0 ) {
					return Math.abs(d.message.days) + ' days before expiry';
				} else if( d.message.days == 0 ) {
					return 'on expiry day';
				} else if( d.message.days > 0 ) {
					return Math.abs(d.message.days) + ' days after expiry';
				}
			} else if( j == 1 ) {
				return d.message.subject;
			}
		}
		this.course.rowFn = function(s, i, d) {
			return 'M.ciniki_fatt_settings.messageEdit(\'M.ciniki_fatt_settings.course.messagesUpdate();\',\'ciniki.fatt.course\',M.ciniki_fatt_settings.course.course_id,\'' + d.message.id + '\');';
		}
		this.course.addDropImage = function(iid) {
			M.ciniki_fatt_settings.course.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.course.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.course.addButton('save', 'Save', 'M.ciniki_fatt_settings.courseSave();');
		this.course.addClose('Cancel');

		//
		// The category edit panel 
		//
		this.category = new M.panel('Course Category',
			'ciniki_fatt_settings', 'category',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.settings.category');
		this.category.category_id = 0;
		this.category.data = {};
		this.category.sections = {
			'image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
			'details':{'label':'', 'aside':'yes', 'fields':{
				'name':{'label':'Name', 'type':'text'},
				'sequence':{'label':'Sequence', 'type':'text', 'size':'tiny'},
				}},
			// Future synopsis if require 
//			'_synopsis':{'label':'Synopsis', 'fields':{
//				'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
//				}},
			'_courses':{'label':'Courses', 'active':'yes', 'fields':{
				'courses':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':{}},
				}},
			'_description':{'label':'Description', 'fields':{
				'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.categorySave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.categoryDelete(M.ciniki_fatt_settings.category.category_id);'},
				}},
		};
		this.category.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.category.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.categoryHistory', 'args':{'business_id':M.curBusinessID, 'category_id':this.category_id, 'field':i}};
		}
		this.category.addDropImage = function(iid) {
			M.ciniki_fatt_settings.category.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.category.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.category.addButton('save', 'Save', 'M.ciniki_fatt_settings.categorySave();');
		this.category.addClose('Cancel');

		//
		// The bundle edit panel 
		//
		this.bundle = new M.panel('Course Bundle',
			'ciniki_fatt_settings', 'bundle',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.bundle');
		this.bundle.bundle_id = 0;
		this.bundle.data = {};
		this.bundle.sections = {
//			'image':{'label':'', 'aside':'yes', 'fields':{
//				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
//				}},
			'details':{'label':'', 'aside':'no', 'fields':{
				'name':{'label':'Name', 'type':'text'},
//				'sequence':{'label':'Sequence', 'type':'text', 'size':'tiny'},
				}},
			'_courses':{'label':'Courses', 'active':'yes', 'fields':{
				'courses':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':{}},
				}},
			// Future synopsis if require 
//			'_synopsis':{'label':'Synopsis', 'fields':{
//				'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
//				}},
//			'_description':{'label':'Description', 'fields':{
//				'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
//				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.bundleSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.bundleDelete(M.ciniki_fatt_settings.bundle.bundle_id);'},
				}},
		};
		this.bundle.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.bundle.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.bundleHistory', 'args':{'business_id':M.curBusinessID, 'bundle_id':this.bundle_id, 'field':i}};
		}
		this.bundle.addDropImage = function(iid) {
			M.ciniki_fatt_settings.bundle.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.bundle.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.bundle.addButton('save', 'Save', 'M.ciniki_fatt_settings.bundleSave();');
		this.bundle.addClose('Cancel');

		//
		// Instructors
		//
		this.instructors = new M.panel('Instructors',
			'ciniki_fatt_settings', 'instructors',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.instructors');
		this.instructors.sections = {
			'instructors':{'label':'', 'type':'simplegrid', 'num_cols':3,
				'headerValues':['Name', 'Status'],
				'cellClasses':['multiline', ''],
				'addTxt':'Add Instructor',
				'addFn':'M.ciniki_fatt_settings.instructorEdit(\'M.ciniki_fatt_settings.instructorList();\',0);',
				},
		};
		this.instructors.sectionData = function(s) { return this.data[s]; }
		this.instructors.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return '<span class="maintext">' + d.instructor.name + (d.instructor.initials!=''?' ['+d.instructor.initials+']':'') + (d.instructor.id_number!=''?' <span class="subdue">'+d.instructor.id_number+'</span>':'') + '</span><span class="subtext">' + d.instructor.email + (d.instructor.email!=''&&d.instructor.phone!=''?' - ':'') + d.instructor.phone + '</span>';
				case 1: return d.instructor.status_text;
			}
		};
		this.instructors.rowFn = function(s, i, d) {
			return 'M.ciniki_fatt_settings.instructorEdit(\'M.ciniki_fatt_settings.instructorList();\',\'' + d.instructor.id + '\');';
		};
		this.instructors.addButton('add', 'Add', 'M.ciniki_fatt_settings.instructorEdit(\'M.ciniki_fatt_settings.instructorList();\',0);');
		this.instructors.addClose('Back');

		//
		// The instructor edit panel
		//
		this.instructor = new M.panel('Instructor',
			'ciniki_fatt_settings', 'instructor',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.settings.instructor');
		this.instructor.instructor_id = 0;
		this.instructor.data = {};
		this.instructor.sections = {
			'image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
			'details':{'label':'', 'aside':'yes', 'fields':{
				'name':{'label':'Name', 'type':'text'},
				'initials':{'label':'Initials', 'type':'text', 'size':'small'},
				'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':{'10':'Active', '50':'Archived'}},
				'id_number':{'label':'ID #', 'type':'text'},
				'email':{'label':'Email', 'type':'text'},
				'phone':{'label':'Phone', 'type':'text'},
				'url':{'label':'Website', 'type':'text'},
				'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
				}},
			'_synopsis':{'label':'Synopsis', 'fields':{
				'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_bio':{'label':'Biography', 'fields':{
				'bio':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.instructorSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.instructorDelete(M.ciniki_fatt_settings.instructor.instructor_id);'},
				}},
		};
		this.instructor.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.instructor.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.instructorHistory', 'args':{'business_id':M.curBusinessID, 'instructor_id':this.instructor_id, 'field':i}};
		}
		this.instructor.addDropImage = function(iid) {
			M.ciniki_fatt_settings.instructor.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.instructor.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.instructor.addButton('save', 'Save', 'M.ciniki_fatt_settings.instructorSave();');
		this.instructor.addClose('Cancel');

		//
		// Locations
		//
		this.locations = new M.panel('Locations',
			'ciniki_fatt_settings', 'locations',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.locations');
		this.locations.sections = {
			'locations':{'label':'', 'type':'simplegrid', 'num_cols':3,
				'headerValues':['Name', 'Status'],
				'cellClasses':['', ''],
				'addTxt':'Add Location',
				'addFn':'M.ciniki_fatt_settings.locationEdit(\'M.ciniki_fatt_settings.locationList();\',0);',
				},
		};
		this.locations.sectionData = function(s) { return this.data[s]; }
		this.locations.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return (d.location.code!=''?d.location.code+' - ':'') + d.location.name;
				case 1: return d.location.status_text;
			}
		};
		this.locations.rowFn = function(s, i, d) {
			return 'M.ciniki_fatt_settings.locationEdit(\'M.ciniki_fatt_settings.locationList();\',\'' + d.location.id + '\');';
		};
		this.locations.addButton('add', 'Add', 'M.ciniki_fatt_settings.locationEdit(\'M.ciniki_fatt_settings.locationList();\',0);');
		this.locations.addClose('Back');

		//
		// The location edit panel 
		//
		this.location = new M.panel('Location',
			'ciniki_fatt_settings', 'location',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.settings.location');
		this.location.location_id = 0;
		this.location.data = {};
		this.location.sections = {
			'details':{'label':'', 'aside':'yes', 'fields':{
				'code':{'label':'Code', 'type':'text', 'size':'small'},
				'name':{'label':'Name', 'type':'text'},
				'url':{'label':'Website', 'type':'text'},
				'num_seats':{'label':'Seats', 'type':'text', 'size':'small'},
				'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':{'10':'Active', '50':'Archived'}},
				}},
			'address':{'label':'', 'aside':'yes', 'fields':{
				'address1':{'label':'Address', 'type':'text'},
				'address2':{'label':'', 'type':'text'},
				'city':{'label':'City', 'type':'text', 'size':'medium'},
				'province':{'label':'Province', 'type':'text', 'size':'small'},
				'postal':{'label':'Postal', 'type':'text', 'size':'small'},
				}},
			'_map':{'label':'Location Map', 'aside':'yes', 'visible':'yes', 'fields':{
				'latitude':{'label':'Latitude', 'type':'text', 'size':'small'},
				'longitude':{'label':'Longitude', 'type':'text', 'size':'small'},
				}},
			'_map_buttons':{'label':'', 'aside':'yes', 'buttons':{
				'_latlong':{'label':'Lookup Lat/Long', 'fn':'M.ciniki_fatt_settings.location.lookupLatLong();'},
				}},
			'_description':{'label':'Description', 'fields':{
				'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.locationSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.locationDelete(M.ciniki_fatt_settings.location.location_id);'},
				}},
		};
		this.location.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.location.lookupLatLong = function() {
			M.startLoad();
			if( document.getElementById('googlemaps_js') == null) {
				var script = document.createElement("script");
				script.id = 'googlemaps_js';
				script.type = "text/javascript";
				script.src = "https://maps.googleapis.com/maps/api/js?key=" + M.curBusiness.settings['googlemapsapikey'] + "&sensor=false&callback=M.ciniki_fatt_settings.location.lookupGoogleLatLong";
				document.body.appendChild(script);
			} else {
				this.lookupGoogleLatLong();
			}
		};
		this.location.lookupGoogleLatLong = function() {
			var address = this.formValue('address1') + ', ' + this.formValue('address2') + ', ' + this.formValue('city') + ', ' + this.formValue('province');
			var geocoder = new google.maps.Geocoder();
			geocoder.geocode( { 'address': address}, function(results, status) {
				if (status == google.maps.GeocoderStatus.OK) {
					M.ciniki_fatt_settings.location.setFieldValue('latitude', results[0].geometry.location.lat());
					M.ciniki_fatt_settings.location.setFieldValue('longitude', results[0].geometry.location.lng());
					M.stopLoad();
				} else {
					alert('We were unable to lookup your latitude/longitude, please check your address in Settings: ' + status);
					M.stopLoad();
				}
			});	
		};
		this.location.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.locationHistory', 'args':{'business_id':M.curBusinessID, 'location_id':this.location_id, 'field':i}};
		}
		this.location.addButton('save', 'Save', 'M.ciniki_fatt_settings.locationSave();');
		this.location.addClose('Cancel');

		//
		// Certs
		//
		this.certs = new M.panel('Certifications',
			'ciniki_fatt_settings', 'certs',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.certs');
		this.certs.sections = {
			'certs':{'label':'', 'type':'simplegrid', 'num_cols':3,
				'headerValues':['Grouping', 'Name', 'Status'],
				'cellClasses':['', ''],
				'addTxt':'Add Certification',
				'addFn':'M.ciniki_fatt_settings.certEdit(\'M.ciniki_fatt_settings.certList();\',0);',
				},
		};
		this.certs.sectionData = function(s) { return this.data[s]; }
		this.certs.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.cert.grouping;
				case 1: return d.cert.name;
				case 2: return d.cert.status_text;
			}
		};
		this.certs.rowFn = function(s, i, d) {
			return 'M.ciniki_fatt_settings.certEdit(\'M.ciniki_fatt_settings.certList();\',\'' + d.cert.id + '\');';
		};
		this.certs.addButton('add', 'Add', 'M.ciniki_fatt_settings.certEdit(\'M.ciniki_fatt_settings.certList();\',0);');
		this.certs.addClose('Back');

		//
		// The cert edit panel
		//
		this.cert = new M.panel('Certification',
			'ciniki_fatt_settings', 'cert',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.cert');
		this.cert.cert_id = 0;
		this.cert.data = {};
		this.cert.sections = {
			'details':{'label':'', 'fields':{
				'name':{'label':'Name', 'type':'text'},
				'grouping':{'label':'Grouping', 'type':'text', 'size':'small'},
				'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':{'10':'Active', '50':'Archived'}},
				'years_valid':{'label':'Valid For', 'type':'text', 'size':'small'},
				}},
			'_courses':{'label':'Courses', 'aside':'yes', 'active':'yes', 'fields':{
				'courses':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'list':{}},
				}},
			'messages':{'label':'Messages', 'active':'no', 'type':'simplegrid', 'num_cols':2,
				'addTxt':'New Reminder',
				'addFn':'M.ciniki_fatt_settings.cert.messageEdit(0)',
				},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.certSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.certDelete(M.ciniki_fatt_settings.cert.cert_id);'},
				}},
		};
		this.cert.sectionData = function(s) { return this.data[s]; }
		this.cert.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.cert.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.certHistory', 'args':{'business_id':M.curBusinessID, 'cert_id':this.cert_id, 'field':i}};
		}
		this.cert.messageEdit = function(mid) {
			if( this.cert_id == 0 ) {
				// Save cert first 
				var c = this.serializeForm('yes');
				M.api.postJSONCb('ciniki.fatt.certAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_fatt_settings.cert.cert_id = rsp.id;
					M.ciniki_fatt_settings.messageEdit('M.ciniki_fatt_settings.cert.messagesUpdate();','ciniki.fatt.cert',rsp.id,mid);
				});
			} else {
				M.ciniki_fatt_settings.messageEdit('M.ciniki_fatt_settings.cert.messagesUpdate();','ciniki.fatt.cert',this.cert_id,mid);
			}
		}
		this.cert.messagesUpdate = function() {
			M.api.getJSONCb('ciniki.fatt.certGet', {'business_id':M.curBusinessID, 
				'cert_id':this.cert_id, 'messages':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_fatt_settings.cert;
					p.data.messages = rsp.cert.messages;
					p.refreshSection('messages');
					p.show();
			});
		}
		this.cert.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				if( d.message.days < 0 ) {
					return Math.abs(d.message.days) + ' days before expiry';
				} else if( d.message.days == 0 ) {
					return 'on expiry day';
				} else if( d.message.days > 0 ) {
					return Math.abs(d.message.days) + ' days after expiry';
				}
			} else if( j == 1 ) {
				return d.message.subject;
			}
		}
		this.cert.rowFn = function(s, i, d) {
			return 'M.ciniki_fatt_settings.messageEdit(\'M.ciniki_fatt_settings.cert.messagesUpdate();\',\'ciniki.fatt.cert\',M.ciniki_fatt_settings.cert.cert_id,\'' + d.message.id + '\');';
		}
		this.cert.addButton('save', 'Save', 'M.ciniki_fatt_settings.certSave();');
		this.cert.addClose('Cancel');

		//
		// Message panel
		//
		this.message = new M.panel('Message',
			'ciniki_fatt_settings', 'message',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.message');
		this.message.message_id = 0;
		this.message.object = '';
		this.message.object_id = 0;
		this.message.data = {};
		this.message.sections = {
			'details':{'label':'', 'fields':{
				'status':{'label':'Status', 'type':'toggle', 'toggles':{'0':'Inactive', '10':'Active'}},
				'days':{'label':'Days', 'type':'text', 'size':'small'},
				'subject':{'label':'Subject', 'type':'text'},
				}},
			'_message':{'label':'Message', 'fields':{
				'message':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.messageSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.messageDelete(M.ciniki_fatt_settings.message.message_id);'},
				}},
		};
		this.message.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.message.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.messageHistory', 'args':{'business_id':M.curBusinessID, 'message_id':this.message_id, 'field':i}};
		}
		this.message.addButton('save', 'Save', 'M.ciniki_fatt_settings.messageSave();');
		this.message.addClose('Cancel');

		//
		// The documents settings panel
		//
		this.documents = new M.panel('Documents',
			'ciniki_fatt_settings', 'documents',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.documents');
		this.documents.sections = {
			'image':{'label':'Header Image', 'fields':{
				'default-header-image':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
			'header':{'label':'Header Address Options', 'fields':{
				'default-header-contact-position':{'label':'Position', 'type':'toggle', 'default':'center', 'toggles':this.positionOptions},
				'default-header-name':{'label':'Business Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'default-header-address':{'label':'Address', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'default-header-phone':{'label':'Phone', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'default-header-cell':{'label':'Cell', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'default-header-fax':{'label':'Fax', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'default-header-email':{'label':'Email', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				'default-header-website':{'label':'Website', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.documentsSave();'},
				}},
		};
		this.documents.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.settingsHistory', 
				'args':{'business_id':M.curBusinessID, 'setting':i}};
		}
		this.documents.fieldValue = function(s, i, d) {
			if( this.data[i] == null && d.default != null ) { return d.default; }
			return this.data[i];
		};
		this.documents.addDropImage = function(iid) {
			M.ciniki_fatt_settings.documents.setFieldValue('default-header-image', iid);
			return true;
		};
		this.documents.deleteImage = function(fid) {
			this.setFieldValue(fid, 0);
			return true;
		};
		this.documents.addButton('save', 'Save', 'M.ciniki_fatt_settings.documentsSave();');
		this.documents.addClose('Cancel');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_fatt_settings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		// 
		// Setup main menu
		//
		this.menu.sections._.list.courses.visible = (M.curBusiness.modules['ciniki.fatt'].flags&0x01)>0?'yes':'no';
		this.menu.sections._.list.instructors.visible = (M.curBusiness.modules['ciniki.fatt'].flags&0x01)>0?'yes':'no';
		this.menu.sections._.list.certs.visible = (M.curBusiness.modules['ciniki.fatt'].flags&0x10)>0?'yes':'no';
		this.menu.sections._.list.locations.visible = (M.curBusiness.modules['ciniki.fatt'].flags&0x04)>0?'yes':'no';

		//
		// Determine what is visible
		//
		if( (M.curBusiness.modules['ciniki.fatt'].flags&0x02) > 0 ) {
			this.courses.sections.courses.label = 'Courses';
			this.courses.sections.categories.active = 'yes';
//			this.courses.sections.courses.num_cols = 4;
//			this.courses.sections.courses.headerValues = ['Category', 'Name', 'Price', 'Status'];
			this.courses.addButton('add_c', 'Category', 'M.ciniki_fatt_settings.categoryEdit(\'M.ciniki_fatt_settings.courseList();\',0);', 'add');
		} else {
			this.courses.sections.courses.label = '';
			this.courses.sections.categories.active = 'no';
//			this.courses.sections.courses.num_cols = 3;
//			this.courses.sections.courses.headerValues = ['Name', 'Price', 'Status'];
			this.courses.delButton('add_c');
		}
		if( (M.curBusiness.modules['ciniki.fatt'].flags&0x40) > 0 ) {
			this.courses.sections.bundles.active = 'yes';
		} else {
			this.courses.sections.bundles.active = 'no';
		}
		this.cert.sections.messages.active = ((M.curBusiness.modules['ciniki.fatt'].flags&0x20) > 0?'yes':'no');
		this.course.sections._categories.active = ((M.curBusiness.modules['ciniki.fatt'].flags&0x02) > 0?'yes':'no');
		this.course.sections._bundles.active = ((M.curBusiness.modules['ciniki.fatt'].flags&0x40) > 0?'yes':'no');
		this.course.sections._certs.active = ((M.curBusiness.modules['ciniki.fatt'].flags&0x10) > 0?'yes':'no');
		this.course.sections.messages.active = ((M.curBusiness.modules['ciniki.fatt'].flags&0x08) > 0?'yes':'no');

		//
		// Setup the tax types
		//
		if( M.curBusiness.modules['ciniki.taxes'] != null ) {
			this.course.sections.details.fields.taxtype_id.active = 'yes';
			this.course.sections.details.fields.taxtype_id.options = {'0':'No Taxes'};
			if( M.curBusiness.taxes != null && M.curBusiness.taxes.settings.types != null ) {
				for(i in M.curBusiness.taxes.settings.types) {
					this.course.sections.details.fields.taxtype_id.options[M.curBusiness.taxes.settings.types[i].type.id] = M.curBusiness.taxes.settings.types[i].type.name;
				}
			}
		} else {
			this.course.sections.details.fields.taxtype_id.active = 'no';
			this.course.sections.details.fields.taxtype_id.options = {'0':'No Taxes'};
		}
		


		if( args.manage != null ) {
			switch(args.manage) {
				case 'courses': this.courseList(cb); break;
				case 'instructors': this.instructorList(cb); break;
				case 'locations': this.locationList(cb); break;
				case 'certs': this.certList(cb); break;
				case 'message': this.messageList(cb); break;
				default: this.showMenu(cb); break;
			}
		} else if( args.course_id != null ) {
			this.courseEdit(cb, args.course_id);
		} else if( args.instructor_id != null ) {
			this.instructorEdit(cb, args.instructor_id);
		} else if( args.location_id != null ) {
			this.locationEdit(cb, args.location_id);
		} else if( args.cert_id != null ) {
			this.certEdit(cb, args.cert_id);
		} else {
			this.showMenu(cb);
		}
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMenu = function(cb) {
		this.menu.refresh();
		this.menu.show(cb);
	}

	//
	// Courses
	//
	this.courseList = function(cb) {
		M.api.getJSONCb('ciniki.fatt.courseList', {'business_id':M.curBusinessID, 
			'course_id':this.course.course_id, 'categories':'yes', 'bundles':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_settings.courses;
				p.data = rsp;
				p.refresh();
				p.show(cb);
		});
	};

	this.courseEdit = function(cb, cid) {
		if( cid != null ) { this.course.course_id = cid; }
		this.course.sections._buttons.buttons.delete.visible = (this.course.course_id>0?'yes':'no');
		M.api.getJSONCb('ciniki.fatt.courseGet', {'business_id':M.curBusinessID, 
			'course_id':this.course.course_id, 'messages':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_settings.course;
				p.data = rsp.course;
				p.sections._categories.fields.categories.list = (rsp.categories!=null?rsp.categories:{});
				p.sections._bundles.fields.bundles.list = (rsp.bundles!=null?rsp.bundles:{});
				p.sections._certs.fields.certs.list = (rsp.certs!=null?rsp.certs:{});
				p.refresh();
				p.show(cb);
		});
	};

	this.courseSave = function() {
		if( this.course.course_id > 0 ) {
			var c = this.course.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.fatt.courseUpdate', {'business_id':M.curBusinessID,
					'course_id':this.course.course_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_fatt_settings.course.close();
					});
			} else {
				this.course.close();
			}
		} else {
			var c = this.course.serializeForm('yes');
			M.api.postJSONCb('ciniki.fatt.courseAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.course.close();
			});
		}
	};

	this.courseDelete = function(lid) {
		if( confirm('Are you sure you want to remove this course?') ) {
			M.api.getJSONCb('ciniki.fatt.courseDelete', {'business_id':M.curBusinessID, 'course_id':lid}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.course.close();
			});
		}
	};

	//
	// Categories
	//
	this.categoryEdit = function(cb, cid) {
		if( cid != null ) { this.category.category_id = cid; }
		this.category.sections._buttons.buttons.delete.visible = (this.category.category_id>0?'yes':'no');
		M.api.getJSONCb('ciniki.fatt.categoryGet', {'business_id':M.curBusinessID, 
			'category_id':this.category.category_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_settings.category;
				p.data = rsp.category;
				p.sections._courses.fields.courses.list = (rsp.courses!=null?rsp.courses:{});
				p.refresh();
				p.show(cb);
		});
	};

	this.categorySave = function() {
		if( this.category.category_id > 0 ) {
			var c = this.category.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.fatt.categoryUpdate', {'business_id':M.curBusinessID,
					'category_id':this.category.category_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_fatt_settings.category.close();
					});
			} else {
				this.category.close();
			}
		} else {
			var c = this.category.serializeForm('yes');
			M.api.postJSONCb('ciniki.fatt.categoryAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.category.close();
			});
		}
	};

	this.categoryDelete = function(lid) {
		if( confirm('Are you sure you want to remove this category?') ) {
			M.api.getJSONCb('ciniki.fatt.categoryDelete', {'business_id':M.curBusinessID, 'category_id':lid}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.category.close();
			});
		}
	};

	//
	// Bundles
	//
	this.bundleEdit = function(cb, cid) {
		if( cid != null ) { this.bundle.bundle_id = cid; }
		this.bundle.sections._buttons.buttons.delete.visible = (this.bundle.bundle_id>0?'yes':'no');
		M.api.getJSONCb('ciniki.fatt.bundleGet', {'business_id':M.curBusinessID, 
			'bundle_id':this.bundle.bundle_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_settings.bundle;
				p.data = rsp.bundle;
				p.sections._courses.fields.courses.list = (rsp.courses!=null?rsp.courses:{});
				p.refresh();
				p.show(cb);
		});
	};

	this.bundleSave = function() {
		if( this.bundle.bundle_id > 0 ) {
			var c = this.bundle.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.fatt.bundleUpdate', {'business_id':M.curBusinessID,
					'bundle_id':this.bundle.bundle_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_fatt_settings.bundle.close();
					});
			} else {
				this.bundle.close();
			}
		} else {
			var c = this.bundle.serializeForm('yes');
			M.api.postJSONCb('ciniki.fatt.bundleAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.bundle.close();
			});
		}
	};

	this.bundleDelete = function(lid) {
		if( confirm('Are you sure you want to remove this bundle?') ) {
			M.api.getJSONCb('ciniki.fatt.bundleDelete', {'business_id':M.curBusinessID, 'bundle_id':lid}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.bundle.close();
			});
		}
	};

	//
	// Instructors
	//
	this.instructorList = function(cb) {
		M.api.getJSONCb('ciniki.fatt.instructorList', {'business_id':M.curBusinessID, 
			'instructor_id':this.instructor.instructor_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_settings.instructors;
				p.data = rsp;
				p.refresh();
				p.show(cb);
		});
	};

	this.instructorEdit = function(cb, iid) {
		if( iid != null ) { this.instructor.instructor_id = iid; }
		this.instructor.sections._buttons.buttons.delete.visible = (this.instructor.instructor_id>0?'yes':'no');
		M.api.getJSONCb('ciniki.fatt.instructorGet', {'business_id':M.curBusinessID, 
			'instructor_id':this.instructor.instructor_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_settings.instructor;
				p.data = rsp.instructor;
				p.refresh();
				p.show(cb);
		});
	};

	this.instructorSave = function() {
		if( this.instructor.instructor_id > 0 ) {
			var c = this.instructor.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.fatt.instructorUpdate', {'business_id':M.curBusinessID,
					'instructor_id':this.instructor.instructor_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_fatt_settings.instructor.close();
					});
			} else {
				this.instructor.close();
			}
		} else {
			var c = this.instructor.serializeForm('yes');
			M.api.postJSONCb('ciniki.fatt.instructorAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.instructor.close();
			});
		}
	};

	this.instructorDelete = function(lid) {
		if( confirm('Are you sure you want to remove this instructor?') ) {
			M.api.getJSONCb('ciniki.fatt.instructorDelete', {'business_id':M.curBusinessID, 'instructor_id':lid}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.instructor.close();
			});
		}
	};

	//
	// Locations
	//
	this.locationList = function(cb) {
		M.api.getJSONCb('ciniki.fatt.locationList', {'business_id':M.curBusinessID, 
			'location_id':this.location.location_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_settings.locations;
				p.data = rsp;
				p.refresh();
				p.show(cb);
		});
	};

	this.locationEdit = function(cb, lid) {
		if( lid != null ) { this.location.location_id = lid; }
		this.location.sections._buttons.buttons.delete.visible = (this.location.location_id>0?'yes':'no');
		M.api.getJSONCb('ciniki.fatt.locationGet', {'business_id':M.curBusinessID, 
			'location_id':this.location.location_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_settings.location;
				p.data = rsp.location;
				p.refresh();
				p.show(cb);
		});
	};

	this.locationSave = function() {
		if( this.location.location_id > 0 ) {
			var c = this.location.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.fatt.locationUpdate', {'business_id':M.curBusinessID,
					'location_id':this.location.location_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_fatt_settings.location.close();
					});
			} else {
				this.location.close();
			}
		} else {
			var c = this.location.serializeForm('yes');
			M.api.postJSONCb('ciniki.fatt.locationAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.location.close();
			});
		}
	};

	this.locationDelete = function(lid) {
		if( confirm('Are you sure you want to remove this location?') ) {
			M.api.getJSONCb('ciniki.fatt.locationDelete', {'business_id':M.curBusinessID, 'location_id':lid}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.location.close();
			});
		}
	};

	//
	// Certs
	//
	this.certList = function(cb) {
		M.api.getJSONCb('ciniki.fatt.certList', {'business_id':M.curBusinessID, 
			'cert_id':this.cert.cert_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_settings.certs;
				p.data = rsp;
				p.refresh();
				p.show(cb);
		});
	};

	this.certEdit = function(cb, cid) {
		if( cid != null ) { this.cert.cert_id = cid; }
		this.cert.sections._buttons.buttons.delete.visible = (this.cert.cert_id>0?'yes':'no');
		M.api.getJSONCb('ciniki.fatt.certGet', {'business_id':M.curBusinessID, 
			'cert_id':this.cert.cert_id, 'messages':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_settings.cert;
				p.data = rsp.cert;
				p.sections._courses.fields.courses.list = (rsp.courses!=null?rsp.courses:{});
				p.refresh();
				p.show(cb);
		});
	};

	this.certSave = function() {
		if( this.cert.cert_id > 0 ) {
			var c = this.cert.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.fatt.certUpdate', {'business_id':M.curBusinessID,
					'cert_id':this.cert.cert_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_fatt_settings.cert.close();
					});
			} else {
				this.cert.close();
			}
		} else {
			var c = this.cert.serializeForm('yes');
			M.api.postJSONCb('ciniki.fatt.certAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.cert.close();
			});
		}
	};

	this.certDelete = function(cid) {
		if( confirm('Are you sure you want to remove this certification?') ) {
			M.api.getJSONCb('ciniki.fatt.certDelete', {'business_id':M.curBusinessID, 'cert_id':cid}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.cert.close();
			});
		}
	};

	//
	// Messages
	//
	this.messageEdit = function(cb, o, oid, mid) {
		if( o != null ) { this.message.object = o; }
		if( oid != null ) { this.message.object_id = oid; }
		if( mid != null ) { this.message.message_id = mid; }
		this.message.sections._buttons.buttons.delete.visible = (this.message.message_id>0?'yes':'no');
		M.api.getJSONCb('ciniki.fatt.messageGet', {'business_id':M.curBusinessID, 
			'message_id':this.message.message_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_settings.message;
				p.data = rsp.message;
				p.refresh();
				p.show(cb);
		});
	};

	this.messageSave = function() {
		if( this.message.message_id > 0 ) {
			var c = this.message.serializeForm('no');
			if( this.message.data.object != this.message.object ) {
				c += '&object=' + this.message.object;
			}
			if( this.message.data.object_id != this.message.object_id ) {
				c += '&object_id=' + this.message.object_id;
			}
			if( c != '' ) {
				M.api.postJSONCb('ciniki.fatt.messageUpdate', {'business_id':M.curBusinessID,
					'message_id':this.message.message_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_fatt_settings.message.close();
					});
			} else {
				this.message.close();
			}
		} else {
			var c = this.message.serializeForm('yes');
			c += '&object=' + this.message.object + '&object_id=' + this.message.object_id;
			M.api.postJSONCb('ciniki.fatt.messageAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.message.close();
			});
		}
	};

	this.messageDelete = function(cid) {
		if( confirm('Are you sure you want to remove this messageification?') ) {
			M.api.getJSONCb('ciniki.fatt.messageDelete', {'business_id':M.curBusinessID, 'message_id':cid}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.message.close();
			});
		}
	};

	//
	// Classes
	//
	this.documentsShow = function(cb) {
		M.api.getJSONCb('ciniki.fatt.settingsGet', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_fatt_settings.documents;
			p.data = rsp.settings;
			p.refresh();
			p.show(cb);
		});
	};

	//
	// Save the Invoice settings
	//
	this.documentsSave = function() {
		var c = this.documents.serializeForm('no');
		if( c != '' ) {
			M.api.postJSONCb('ciniki.fatt.settingsUpdate', {'business_id':M.curBusinessID}, 
				c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_fatt_settings.documents.close();
				});
		} else {
			this.documents.close();
		}
	};
}
