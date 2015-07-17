//
// The panels to show offerings by year/month
//
function ciniki_fatt_offerings() {
	this.courseForms = {
		'':'None',
		};

	this.courseReg = function(o,f) {
		var mt = '';
		var mt = o.seats_remaining + ' left';
		if( o.seats_remaining < 0 ) {
			mt = Math.abs(o.seats_remaining) + ' oversold';
		} else if( o.seats_remaining == 0 ) {
			mt = 'Sold Out';
		}
		if( o.max_seats > 0 ) {
			var st = o.num_registrations + ' of ' + o.max_seats;
		} else {
			var st = o.num_registrations + ' registered';
		}
		if( f != null && f == 'comma' ) {	
			return mt + ', ' + st;
		}
		return '<span class="maintext">' + mt + '</span><span class="subtext">' + st + '</span>';
	}

	this.init = function() {
		//
		// The offerings panel
		//
		this.offerings = new M.panel('Settings',
			'ciniki_fatt_offerings', 'offerings',
			'mc', 'medium mediumflex', 'sectioned', 'ciniki.fatt.offerings.offerings');
		this.offerings.data = {};
		this.offerings.year = 0;
		this.offerings.month = 0;
		this.offerings.sections = {	
			'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
			'months':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'0', 'tabs':{
				'0':{'label':'All', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,0);'},
				'1':{'label':'Jan', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,1);'},
				'2':{'label':'Feb', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,2);'},
				'3':{'label':'Mar', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,3);'},
				'4':{'label':'Apr', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,4);'},
				'5':{'label':'May', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,5);'},
				'6':{'label':'Jun', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,6);'},
				'7':{'label':'Jul', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,7);'},
				'8':{'label':'Aug', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,8);'},
				'9':{'label':'Sep', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,9);'},
				'10':{'label':'Oct', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,10);'},
				'11':{'label':'Nov', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,11);'},
				'12':{'label':'Dec', 'fn':'M.ciniki_fatt_offerings.showOfferings(null,null,12);'},
				}},
			'offerings':{'label':'Courses', 'type':'simplegrid', 'num_cols':4,
				'sortable':'yes',
				'headerValues':['Date', 'Course', 'Location', 'Registered'],
				'sortTypes':['altnumber', 'date', 'text', 'altnumber'],
				'cellClasses':['multiline', 'multiline', 'multiline', 'multiline', 'multiline'],
				'noData':'No offerings',
				},
		};
		this.offerings.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.offering.date_string;
				case 1: return d.offering.course_name;
				case 2: return d.offering.location;
				case 3: return M.ciniki_fatt_offerings.courseReg(d.offering);
				case 4: return d.offering.status_text;
			}
		};
		this.offerings.cellSortValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.offering.start_date_ts;
				case 1: return d.offering.course_name;
				case 2: return d.offering.location_name;
				case 3: return d.offering.seats_remaining;
			}
		};
		this.offerings.rowFn = function(s, i, d) {
			return 'M.ciniki_fatt_offerings.offeringShow(\'M.ciniki_fatt_offerings.showOfferings();\',\'' + d.offering.id + '\');';
		};
		this.offerings.sectionData = function(s) { return this.data[s]; };
		this.offerings.noData = function(s) { return this.sections[s].noData; };
		this.offerings.addButton('add', 'Add', 'M.ciniki_fatt_offerings.offeringEdit(\'M.ciniki_fatt_offerings.showOfferings();\',0);');
		this.offerings.addClose('Back');

		//
		// The offering panel showing all information about a course offering
		//
		this.offering = new M.panel('Offering',
			'ciniki_fatt_offerings', 'offering',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.offerings.offering');
		this.offering.offering_id = 0;
		this.offering.data = {};
		this.offering.sections = {	
			'details':{'label':'Course Offering', 'aside':'yes', 'list':{
				'course_name':{'label':'Course'},
				'price':{'label':'Price'},
				'date_string':{'label':'When'},
				'location':{'label':'Location'},
				'location_address':{'label':'Address', 'visible':'no'},
				'flags_display':{'label':'Options'},
				'max_seats':{'label':'Max Capacity'},
				'seats_remaining':{'label':'Available'},
				}},
			'instructors':{'label':'Instructors', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
				'cellClasses':['multiline', 'multiline'],
				},
			'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':2,
				'addTxt':'Add Registration',
				'addFn':'M.startApp(\'ciniki.fatt.sapos\',null,\'M.ciniki_fatt_offerings.offeringShow();\',\'mc\',{\'offering_id\':M.ciniki_fatt_offerings.offering.offering_id,\'source\':\'offering\'});',
//				'addFn':'M.ciniki_fatt_offerings.registrationAdd(\'M.ciniki_fatt_offerings.offeringShow();\',M.ciniki_fatt_offerings.offering_id);',
				},
		};
		this.offering.sectionData = function(s) { 
			if( s == 'details' ) { return this.sections[s].list; }
			return this.data[s]; 
		};
		this.offering.listLabel = function(s, i, d) { return d.label; }
		this.offering.listValue = function(s, i, d) { 
			if( i == 'max_seats' ) {
				if( this.data[i] == 0 ) { return 'No limit'; }
				else { return this.data[i]; }
			}
			if( i == 'seats_remaining' ) {
				if( this.data[i] < 0 ) { return Math.abs(this.data[i]) + ' oversold'; }
				if( this.data[i] == 0 ) { return 'Sold Out'; }	
				if( this.data[i] > 0 ) { return this.data[i]; }	
			}
			return this.data[i]; 
		};
		this.offering.cellValue = function(s, i, j, d) {
			if( s == 'instructors' ) {
				return '<span class="maintext">' + d.instructor.name + '</span><span class="subtext">' + d.instructor.email + (d.instructor.email!=''?' - ':'') + d.instructor.phone + '</span>';
			} else if( s == 'registrations' ) {
				if( j == 0 ) {
					if( d.registration.customer_id != d.registration.student_id ) {
						return (d.registration.student_display_name!=''?d.registration.student_display_name:'???') + ' <span class="subdue">[' + d.registration.customer_display_name + ']</span>';
					} 
					return d.registration.customer_display_name;
				} else if( j == 1 ) {
					return (d.registration.invoice_status!=null?d.registration.invoice_status:'');
				}
			}
		};
		this.offering.rowFn = function(s, i, d) {
			if( s == 'registrations' ) {
				return 'M.startApp(\'ciniki.fatt.sapos\',null,\'M.ciniki_fatt_offerings.offeringShow();\',\'mc\',{\'registration_id\':\'' + d.registration.id + '\',\'source\':\'offering\'});';
			}
			return '';
		};
		this.offering.addButton('edit', 'Edit', 'M.ciniki_fatt_offerings.offeringEdit(\'M.ciniki_fatt_offerings.offeringShow();\',M.ciniki_fatt_offerings.offering.offering_id);');
		this.offering.addClose('Back');

		//
		// The offering edit panel
		//
		this.edit = new M.panel('Course Offering',
			'ciniki_fatt_offerings', 'edit',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.offerings.edit');
		this.edit.offering_id = 0;
		this.edit.courses = {};
		this.edit.data = {};
		this.edit.sections = {
			'details':{'label':'', 'aside':'yes', 'fields':{
				'course_id':{'label':'Course', 'type':'select', 'options':{}, 'onchangeFn':'M.ciniki_fatt_offerings.edit.courseChange'},
				'price':{'label':'Price', 'type':'text', 'size':'small'},
				'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}, '5':{'name':'Online Registrations'}}},
				}},
			'_instructors':{'label':'Instructors', 'aside':'yes', 'active':'no', 'fields':{
				'instructors':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'itemname':'instructor', 'list':{}},
				}},
			'dates':{'label':'Dates', 'type':'simplegrid', 'num_cols':1,
				'cellClasses':['multiline'],
				'addTxt':'Add Date',
				'addFn':'M.ciniki_fatt_offerings.edit.dateEdit(0);',
				},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_offerings.offeringSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_offerings.offeringDelete(M.ciniki_fatt_offerings.edit.offering_id);'},
				}},
		};
		this.edit.sectionData = function(s) { return this.data[s]; }
		this.edit.fieldValue = function(s, i, d) {
			if( i == 'instructors' ) {
				var str = ''
				for(var j in this.data.instructors) { str += (str!=''?',':'') + this.data.instructors[j].instructor.id; }
				return str;
			}
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.offeringHistory', 'args':{'business_id':M.curBusinessID, 'offering_id':this.offering_id, 'field':i}};
		};
		this.edit.cellValue = function(s, i, j, d) {
			return '<span class="maintext">' + d.date.start_date + '</span><span class="subtext">' + d.date.location_name + '</span>';
		};
		this.edit.rowFn = function(s, i, d) {
			return 'M.ciniki_fatt_offerings.edit.dateEdit(\'' + d.date.id + '\');';
		};
		this.edit.courseChange = function(s, i) {
			// Only change the price for new offering add, existing courses may have price changed manually and don't want to override.
			if( this.data.price == '' ) {
				var cid = this.formValue(i);
				if( this.courses[cid] != null && this.courses[cid].price != null ) {
					this.setFieldValue('price', this.courses[cid].price);
				}
			}
		};
		this.edit.dateEdit = function(did) {
			if( this.offering_id == 0 ) {
				var c = this.serializeForm('yes');
				M.api.postJSONCb('ciniki.fatt.offeringAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_fatt_offerings.edit.offering_id = rsp.id;
					M.ciniki_fatt_offerings.dateEdit('M.ciniki_fatt_offerings.edit.datesUpdate();',did,rsp.id);
				});
			} else {
				M.ciniki_fatt_offerings.dateEdit('M.ciniki_fatt_offerings.edit.datesUpdate();',did,this.offering_id);
			}
		};
		this.edit.datesUpdate = function(dates) {
			this.refreshSection('dates');
			this.show();
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_fatt_offerings.offeringSave();');
		this.edit.addClose('Cancel');

		//
		// The offering date edit panel
		//
		this.odate = new M.panel('Course Offering Date',
			'ciniki_fatt_offerings', 'odate',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.offerings.odate');
		this.odate.date_id = 0;
		this.odate.offering_id = 0;
		this.odate.data = {};
		this.odate.sections = {
			'details':{'label':'', 'aside':'yes', 'fields':{
				'start_date':{'label':'Date', 'type':'appointment', 'caloffset':0,
					'start':'6:00',
					'end':'20:00',
					'interval':'30',
					'notimelabel':''},
				'day_number':{'label':'Day', 'type':'text', 'size':'small'},
				'num_hours':{'label':'Hours', 'type':'text', 'size':'small'},
				'location_id':{'label':'Location', 'type':'select', 'options':{}, 'onchangeFn':'M.ciniki_fatt_offerings.odate.locationChange'},
				}},
			'address':{'label':'', 'aside':'yes', 'visible':'hidden', 'fields':{
				'address1':{'label':'Street', 'type':'text'},
				'address2':{'label':'', 'type':'text'},
				'city':{'label':'City', 'type':'text', 'size':'medium'},
				'province':{'label':'Province', 'type':'text', 'size':'small'},
				'postal':{'label':'Postal', 'type':'text', 'size':'small'},
				}},
			'_map':{'label':'Location Map', 'aside':'yes', 'visible':'hidden', 'fields':{
				'latitude':{'label':'Latitude', 'type':'text', 'size':'small'},
				'longitude':{'label':'Longitude', 'type':'text', 'size':'small'},
				}},
			'_map_buttons':{'label':'', 'aside':'yes', 'visible':'hidden', 'buttons':{
				'_latlong':{'label':'Lookup Lat/Long', 'fn':'M.ciniki_fatt_offerings.odate.lookupLatLong();'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_offerings.dateSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_offerings.dateDelete(M.ciniki_fatt_offerings.odate.date_id);'},
				}},
		};
		this.odate.liveAppointmentDayEvents = function(i, day, cb) {
			if( i == 'start_date' ) {
				if( day == '--' ) { day = 'today';}
				M.api.getJSONCb('ciniki.calendars.appointments', {'business_id':M.curBusinessID, 'date':day}, cb);
			}
		};
		this.odate.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.odate.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.offeringDateHistory', 'args':{'business_id':M.curBusinessID, 'date_id':this.date_id, 'field':i}};
		};
		this.odate.locationChange = function(s, i) {
			var lid = this.formValue(i);
			if( this.locations[lid] != null && (this.locations[lid].flags&0x01) == 1 ) {
				M.gE(this.panelUID + '_section_address').style.display = '';	
				M.gE(this.panelUID + '_section__map').style.display = '';	
				M.gE(this.panelUID + '_section__map_buttons').style.display = '';	
			} else {
				M.gE(this.panelUID + '_section_address').style.display = 'none';	
				M.gE(this.panelUID + '_section__map').style.display = 'none';	
				M.gE(this.panelUID + '_section__map_buttons').style.display = 'none';	
			}
		};
//		this.add.fieldHistoryArgs = function(s, i) {
//			return {'method':'ciniki.fatt.offeringDateHistory', 'args':{'business_id':M.curBusinessID, 'date_id':this.date_id, 'field':i}};
//		};
		this.odate.lookupLatLong = function() {
			M.startLoad();
			if( document.getElementById('googlemaps_js') == null) {
				var script = document.createElement("script");
				script.id = 'googlemaps_js';
				script.type = "text/javascript";
				script.src = "https://maps.googleapis.com/maps/api/js?key=" + M.curBusiness.settings['googlemapsapikey'] + "&sensor=false&callback=M.ciniki_fatt_offerings.odate.lookupGoogleLatLong";
				document.body.appendChild(script);
			} else {
				this.lookupGoogleLatLong();
			}
		};
		this.odate.lookupGoogleLatLong = function() {
			var address = this.formValue('address1') + ', ' + this.formValue('address2') + ', ' + this.formValue('city') + ', ' + this.formValue('province');
			var geocoder = new google.maps.Geocoder();
			geocoder.geocode( { 'address': address}, function(results, status) {
				if (status == google.maps.GeocoderStatus.OK) {
					M.ciniki_fatt_offerings.odate.setFieldValue('latitude', results[0].geometry.location.lat());
					M.ciniki_fatt_offerings.odate.setFieldValue('longitude', results[0].geometry.location.lng());
					M.stopLoad();
				} else {
					alert('We were unable to lookup your latitude/longitude, please check your address in Settings: ' + status);
					M.stopLoad();
				}
			});	
		};
		this.odate.addButton('save', 'Save', 'M.ciniki_fatt_offerings.dateSave();');
		this.odate.addClose('Cancel');

		//
		// Show the consolidated appointment/courses at same time/location
		//
		this.class = new M.panel('Class',
			'ciniki_fatt_offerings', 'class',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.offerings.class');
		this.class.class_id = '';
		this.class.data = {};
		this.class.sections = {
			'details':{'label':'Class', 'aside':'yes', 'list':{
				'course_codes':{'label':'Courses'},
				'start_date':{'label':'When'},
				'location_name':{'label':'Location'},
				'location_address':{'label':'Address', 'visible':'no'},
				'seats_remaining':{'label':'Available'},
				}},
			'instructors':{'label':'Instructors', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
				'cellClasses':['multiline'],
				},
			'offerings':{'label':'Courses', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['multiline', 'textbuttons'],
				},
			'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':3,
//				'addTxt':'Add Registration',
//				'addFn':'M.startApp(\'ciniki.fatt.sapos\',null,\'M.ciniki_fatt_offerings.classShow();\',\'mc\',{\'offering_id\':M.ciniki_fatt_offerings.offering.offering_id,\'source\':\'offering\'});',
				},
			'_buttons':{'label':'', 'buttons':{
				'printlist':{'label':'Print Class List', 'fn':'M.ciniki_fatt_offerings.classPrintList(M.ciniki_fatt_offerings.class.class_id);'},
				}},
			};
		this.class.sectionData = function(s) { 
			if( s == 'details' ) { return this.sections[s].list; }
			return this.data[s]; 
		};
		this.class.listLabel = function(s, i, d) { return d.label; }
		this.class.listValue = function(s, i, d) { 
			if( i == 'seats_remaining' ) {
				if( this.data[i] < 0 ) { return Math.abs(this.data[i]) + ' oversold'; }
				if( this.data[i] == 0 ) { return 'Sold Out'; }	
				if( this.data[i] > 0 ) { return this.data[i]; }	
			}
			return this.data[i]; 
		};
		this.class.cellValue = function(s, i, j, d) {
			if( s == 'instructors' ) {
				return '<span class="maintext">' + d.instructor.name + '</span><span class="subtext">' + d.instructor.email + (d.instructor.email!=''?' - ':'') + d.instructor.phone + '</span>';
			} else if( s == 'registrations' ) {
				if( j == 0 ) {	
					return d.registration.course_code;
				} else if( j == 1 ) {
					if( d.registration.customer_id != d.registration.student_id ) {
						return (d.registration.student_display_name!=''?d.registration.student_display_name:'???') + ' <span class="subdue">[' + d.registration.customer_display_name + ']</span>';
					} 
					return d.registration.customer_display_name;
				} else if( j == 2 ) {
					return (d.registration.invoice_status!=null?d.registration.invoice_status:'');
				}
			} else if( s == 'offerings' ) {
				if( j == 0 ) {
					return '<span class="maintext">' + d.offering.course_name + '</span><span class="subtext">' + M.ciniki_fatt_offerings.courseReg(d.offering, 'comma') + '</span>';
				} else if( j == 1 ) {
					return '<button onclick="event.stopPropagation();M.startApp(\'ciniki.fatt.sapos\',null,\'M.ciniki_fatt_offerings.classShow();\',\'mc\',{\'offering_id\':\'' + d.offering.id + '\',\'source\':\'class\'});">Add Registration</button>';
				}
			}
		};
		this.class.rowFn = function(s, i, d) {
			if( s == 'registrations' ) {
				return 'M.startApp(\'ciniki.fatt.sapos\',null,\'M.ciniki_fatt_offerings.classShow();\',\'mc\',{\'registration_id\':\'' + d.registration.id + '\',\'source\':\'class\'});';
			} else if( s == 'offerings' ) {
				return 'M.ciniki_fatt_offerings.offeringShow(\'M.ciniki_fatt_offerings.classShow();\',\'' + d.offering.id + '\');';
			}
			return '';
		};
		this.class.addClose('Back');

		//
		// This panel is for adding offerings based on calendar date with auto settings
		//
		this.add = new M.panel('New Offering',
			'ciniki_fatt_offerings', 'add',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.offerings.add');
		this.add.data = {};
		this.add.customer_id = 0;
		this.add.sections = {
			'details':{'label':'', 'aside':'yes', 'fields':{
				'course_id':{'label':'Course', 'type':'select', 'options':{}, 'onchangeFn':'M.ciniki_fatt_offerings.add.courseChange'},
				'day1':{'label':'Day 1', 'type':'appointment', 'caloffset':0,
					'start':'6:00', 'end':'20:00', 'interval':'30', 'notimelabel':''},
				'day2':{'label':'Day 2', 'visible':'no', 'type':'appointment', 'caloffset':0,
					'start':'6:00', 'end':'20:00', 'interval':'30', 'notimelabel':''},
				'location_id':{'label':'Location', 'type':'select', 'options':{}, 'onchangeFn':'M.ciniki_fatt_offerings.add.locationChange'},
				'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}, '5':{'name':'Online Registrations'}}},
				}},
			'address':{'label':'', 'aside':'yes', 'visible':'hidden', 'fields':{
				'address1':{'label':'Street', 'type':'text'},
				'address2':{'label':'', 'type':'text'},
				'city':{'label':'City', 'type':'text', 'size':'medium'},
				'province':{'label':'Province', 'type':'text', 'size':'small'},
				'postal':{'label':'Postal', 'type':'text', 'size':'small'},
				}},
			'_map':{'label':'Location Map', 'aside':'yes', 'visible':'hidden', 'fields':{
				'latitude':{'label':'Latitude', 'type':'text', 'size':'small'},
				'longitude':{'label':'Longitude', 'type':'text', 'size':'small'},
				}},
			'_map_buttons':{'label':'', 'aside':'yes', 'visible':'hidden', 'buttons':{
				'_latlong':{'label':'Lookup Lat/Long', 'fn':'M.ciniki_fatt_offerings.add.lookupLatLong();'},
				}},
			'_instructors':{'label':'Instructors', 'aside':'yes', 'active':'no', 'fields':{
				'instructors':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'itemname':'instructor', 'list':{}},
				}},
			'customer_details':{'label':'Customer', 'aside':'yes', 'visible':'hidden', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['label',''],
				'addTxt':'',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_offerings.add.updateCustomer(null);\',\'mc\',{\'next\':\'M.ciniki_fatt_offerings.add.updateCustomer\',\'customer_id\':M.ciniki_fatt_sapos.registration.student_id});',
				'changeTxt':'Add Customer',
				'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_offerings.add.updateCustomer(null);\',\'mc\',{\'next\':\'M.ciniki_fatt_offerings.add.updateCustomer\',\'customer_id\':0});',
				},
			'customer_seats':{'label':'', 'visible':'hidden', 'fields':{
				'num_seats':{'label':'Seats', 'type':'text', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'add':{'label':'Add', 'fn':'M.ciniki_fatt_offerings.addCourses();'},
				}},
		};
		this.add.sectionData = function(s) { return this.data[s]; }
		this.add.liveAppointmentDayEvents = function(i, day, cb) {
			if( i == 'day1' ) {
				if( day == '--' ) { day = 'today';}
				M.api.getJSONCb('ciniki.calendars.appointments', {'business_id':M.curBusinessID, 'date':day}, cb);
			}
			else if( i == 'day2' ) {
				if( day == '--' ) { day = 'today';}
				M.api.getJSONCb('ciniki.calendars.appointments', {'business_id':M.curBusinessID, 'date':day}, cb);
			}
		};
		this.add.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.add.cellValue = function(s, i, j, d) {
			if( s == 'customer_details' ) {
				switch(j) {
					case 0: return d.detail.label;
					case 1: return d.detail.value.replace(/\n/, '<br/>');
				}
			} 
		};
		this.add.rowFn = function(s, i, d) {
			return '';
		};
		this.add.courseChange = function(s, i) {
			var cid = this.formValue(i);
			if( cid != '0' ) {
				if( this.courses[cid] != null && this.courses[cid].num_days > 1 ) {
					M.gE(this.panelUID + '_day2').parentNode.parentNode.style.display = 'table-row';	
				} else {
					M.gE(this.panelUID + '_day2').parentNode.parentNode.style.display = 'none';	
				}
				if( cid.match(/b-/) ) {
					M.gE(this.panelUID + '_section_customer_details').style.display = 'none';
					M.gE(this.panelUID + '_section_customer_seats').style.display = 'none';
				} else {
// FIXME: Add customer
//					M.gE(this.panelUID + '_section_customer_details').style.display = '';
//					if( this.customer_id > 0 ) {
//						M.gE(this.panelUID + '_section_customer_seats').style.display = '';
//					}
				}
			}
			// Only change the price for new offering add, existing courses may have price changed manually and don't want to override.
//			if( this.data.price == '' ) {
//				if( this.courses[cid] != null && this.courses[cid].price != null ) {
//					this.setFieldValue('price', this.courses[cid].price);
//				}
//			}
		};
		this.add.locationChange = function(s, i) {
			var lid = this.formValue(i);
			if( this.locations[lid] != null && (this.locations[lid].flags&0x01) == 1 ) {
				M.gE(this.panelUID + '_section_address').style.display = '';	
				M.gE(this.panelUID + '_section__map').style.display = '';	
				M.gE(this.panelUID + '_section__map_buttons').style.display = '';	
			} else {
				M.gE(this.panelUID + '_section_address').style.display = 'none';	
				M.gE(this.panelUID + '_section__map').style.display = 'none';	
				M.gE(this.panelUID + '_section__map_buttons').style.display = 'none';	
			}
		};
//		this.add.fieldHistoryArgs = function(s, i) {
//			return {'method':'ciniki.fatt.offeringDateHistory', 'args':{'business_id':M.curBusinessID, 'date_id':this.date_id, 'field':i}};
//		};
		this.add.lookupLatLong = function() {
			M.startLoad();
			if( document.getElementById('googlemaps_js') == null) {
				var script = document.createElement("script");
				script.id = 'googlemaps_js';
				script.type = "text/javascript";
				script.src = "https://maps.googleapis.com/maps/api/js?key=" + M.curBusiness.settings['googlemapsapikey'] + "&sensor=false&callback=M.ciniki_fatt_offerings.add.lookupGoogleLatLong";
				document.body.appendChild(script);
			} else {
				this.lookupGoogleLatLong();
			}
		};
		this.add.lookupGoogleLatLong = function() {
			var address = this.formValue('address1') + ', ' + this.formValue('address2') + ', ' + this.formValue('city') + ', ' + this.formValue('province');
			var geocoder = new google.maps.Geocoder();
			geocoder.geocode( { 'address': address}, function(results, status) {
				if (status == google.maps.GeocoderStatus.OK) {
					M.ciniki_fatt_offerings.add.setFieldValue('latitude', results[0].geometry.location.lat());
					M.ciniki_fatt_offerings.add.setFieldValue('longitude', results[0].geometry.location.lng());
					M.stopLoad();
				} else {
					alert('We were unable to lookup your latitude/longitude, please check your address in Settings: ' + status);
					M.stopLoad();
				}
			});	
		};
		this.add.updateCustomer = function(cid) {
			if( cid != null && cid != this.customer_id ) {
				this.customer_id = cid;
			}
			if( this.customer_id > 0 ) {
				this.sections.customer_details.visible = 'yes';
				this.sections.customer_seats.visible = 'yes';
				if( M.gE(this.panelUID + '_section_customer_seats') != null ) {
					M.gE(this.panelUID + '_section_customer_seats').style.display = '';
				}
				this.sections.customer_details.addTxt = 'Edit';
				this.sections.customer_details.changeTxt = 'Change';
				M.api.getJSONCb('ciniki.customers.customerDetails', {'business_id':M.curBusinessID, 'customer_id':this.customer_id, 'phones':'yes', 'emails':'yes', 'addresses':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_fatt_offerings.add;
					p.data.customer_details = rsp.details;
					p.refreshSection('customer_details');
					p.show();
				});
			} else {
				this.sections.customer_seats.visible = 'hidden';
				if( M.gE(this.panelUID + '_section_customer_seats') != null ) {
					M.gE(this.panelUID + '_section_customer_seats').style.display = 'none';
				}
				this.sections.customer_details.addTxt = '';
				this.sections.customer_details.changeTxt = 'Add';
				this.data.customer_details = {};
				this.refreshSection('customer_details');
				this.show();
			}
		};
		this.add.addButton('save', 'Save', 'M.ciniki_fatt_offerings.addCourses();');
		this.add.addClose('Cancel');
	};

	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_fatt_offerings', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		//
		// Use settings to update course list
		//
		this.edit.courses = {};
		this.add.courses = {};
		var courses = {'0':'pick a course'};
		var acourses = {'0':'pick a course'};
		//
		// Add the bundles to the list of courses for add
		//
		
		if( M.curBusiness.modules['ciniki.fatt'].settings.courses != null ) {
			for(var i in M.curBusiness.modules['ciniki.fatt'].settings.courses) {
				this.add.ndays = M.curBusiness.modules['ciniki.fatt'].settings.courses[i].course.num_days;
				courses[M.curBusiness.modules['ciniki.fatt'].settings.courses[i].course.id] = M.curBusiness.modules['ciniki.fatt'].settings.courses[i].course.name;
				acourses[M.curBusiness.modules['ciniki.fatt'].settings.courses[i].course.id] = M.curBusiness.modules['ciniki.fatt'].settings.courses[i].course.name;
				this.edit.courses[M.curBusiness.modules['ciniki.fatt'].settings.courses[i].course.id] = M.curBusiness.modules['ciniki.fatt'].settings.courses[i].course;
				this.add.courses[M.curBusiness.modules['ciniki.fatt'].settings.courses[i].course.id] = M.curBusiness.modules['ciniki.fatt'].settings.courses[i].course;
			}
		}
		this.edit.sections.details.fields.course_id.options = courses;

		if( M.curBusiness.modules['ciniki.fatt'].settings.bundles != null ) {
			for(var i in M.curBusiness.modules['ciniki.fatt'].settings.bundles) {
//				this.add.ndays = M.curBusiness.modules['ciniki.fatt'].settings.bundles[i].bundle.num_days;
				acourses['b-' + M.curBusiness.modules['ciniki.fatt'].settings.bundles[i].bundle.id] = 'Bundle: ' + M.curBusiness.modules['ciniki.fatt'].settings.bundles[i].bundle.name;
				this.add.courses['b-' + M.curBusiness.modules['ciniki.fatt'].settings.bundles[i].bundle.id] = M.curBusiness.modules['ciniki.fatt'].settings.bundles[i].bundle;
			}
		}
		this.add.sections.details.fields.course_id.options = acourses;
	
		//
		// Use settings to setup location list for offering date edit
		//
		var locations = {0:'Unknown'};
		this.add.locations = {};
		this.odate.locations = {};
		if( M.curBusiness.modules['ciniki.fatt'].settings.locations != null ) {
			for(var i in M.curBusiness.modules['ciniki.fatt'].settings.locations) {
				locations[M.curBusiness.modules['ciniki.fatt'].settings.locations[i].location.id] = M.curBusiness.modules['ciniki.fatt'].settings.locations[i].location.name;
				this.add.locations[M.curBusiness.modules['ciniki.fatt'].settings.locations[i].location.id] = M.curBusiness.modules['ciniki.fatt'].settings.locations[i].location;
				this.odate.locations[M.curBusiness.modules['ciniki.fatt'].settings.locations[i].location.id] = M.curBusiness.modules['ciniki.fatt'].settings.locations[i].location;
			}
		}
		this.odate.sections.details.fields.location_id.options = locations;
		this.add.sections.details.fields.location_id.options = locations;

		//
		// Use settings to update instructor list
		//
		this.edit.sections._instructors.active = 'no';
		this.add.sections._instructors.active = 'no';
		if( M.curBusiness.modules['ciniki.fatt'].settings.instructors != null ) {
			this.edit.sections._instructors.fields.instructors.list = M.curBusiness.modules['ciniki.fatt'].settings.instructors;
			this.add.sections._instructors.fields.instructors.list = M.curBusiness.modules['ciniki.fatt'].settings.instructors;
			if( M.curBusiness.modules['ciniki.fatt'].settings.instructors.length > 0 ) {
				this.edit.sections._instructors.active = 'yes';
				this.add.sections._instructors.active = 'yes';
			}
		} else {
			this.edit.sections._instructors.fields.instructors.list = {};
			this.add.sections._instructors.fields.instructors.list = {};
		}

		//
		// Decide what to show
		//
		if( args.add != null && args.add == 'courses' ) {
			this.addShow(cb, args.date, args.time, args.allday);
		} else if( args.appointment_id != null ) {
			this.classShow(cb, args.appointment_id);
		} else if( args.offering_id != null ) {
			this.offeringShow(cb, args.offering_id);
		} else {
			var dt = new Date();
			this.showOfferings(cb, dt.getFullYear(), 0);
		}
	};

	this.showOfferings = function(cb, year, month) {
		if( year != null ) {
			this.offerings.year = year;
			this.offerings.sections.years.selected = year;
		}
		if( month != null ) {
			this.offerings.month = month;
			this.offerings.sections.months.selected = month;
		}
		M.api.getJSONCb('ciniki.fatt.offeringList', {'business_id':M.curBusinessID,
			'year':this.offerings.year, 'month':this.offerings.month, 'years':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_offerings.offerings;
				p.data = {'offerings':rsp.offerings};
				p.sections.years.tabs = {};
				p.sections.years.visible = 'no';
				p.sections.months.visible = 'no';
				if( rsp.years != null ) {
					var yrs = rsp.years.split(',');
					for(var i in yrs) {
						p.sections.years.tabs[yrs[i]] = {'label':yrs[i], 'fn':'M.ciniki_fatt_offerings.showOfferings(null,\'' + yrs[i] + '\');'};
					}
					p.sections.years.visible = 'yes';
					p.sections.months.visible = 'yes';
				}
				p.refresh();
				p.show(cb);
			});
	};

	this.offeringShow = function(cb, oid) {
		if( oid != null ) { this.offering.offering_id = oid; }
		if( cb != null ) { this.offering.cb = cb; }
		M.api.getJSONCb('ciniki.fatt.offeringGet', {'business_id':M.curBusinessID, 
			'offering_id':this.offering.offering_id}, this.offeringShowFinish);
	};

	this.offeringShowFinish = function(rsp) {
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		var p = M.ciniki_fatt_offerings.offering;
		p.data = rsp.offering;
		p.data.location_address = '';
		p.sections.details.list.location_address.visible = 'no';
		if( rsp.offering.dates != null ) {
			for(var i in rsp.offering.dates) {
				if( (rsp.offering.dates[i].date.location_flags&0x01) == 1 ) {
					p.sections.details.list.location_address.visible = 'yes';
					p.data.location_address += (p.data.location_address!=''?'<br/>':'') + M.formatAddress(rsp.offering.dates[i].date);
				}
			}
		}
		p.refresh();
		p.show();
	}

	//
	// Offering Edit functions
	//
	this.offeringEdit = function(cb, oid) {
		if( oid != null ) { this.edit.offering_id = oid; }
		this.edit.sections._buttons.buttons.delete.visible = (this.edit.offering_id>0?'yes':'no');
		M.api.getJSONCb('ciniki.fatt.offeringGet', {'business_id':M.curBusinessID, 
			'offering_id':this.edit.offering_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_offerings.edit;
				p.data = rsp.offering;
				p.refresh();
				p.show(cb);
				p.courseChange('details', 'course_id');
		});
	};

	this.offeringSave = function() {
		if( this.edit.offering_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.fatt.offeringUpdate', {'business_id':M.curBusinessID,
					'offering_id':this.edit.offering_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_fatt_offerings.edit.close();
					});
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			M.api.postJSONCb('ciniki.fatt.offeringAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				if( rsp.offering != null ) {
					M.ciniki_fatt_offerings.offeringShowFinish(rsp);
				} else {
					M.ciniki_fatt_offerings.edit.close();
				}
			});
		}
	};

	this.offeringDelete = function(oid) {
		if( confirm('Are you sure you want to remove this offering?') ) {
			M.api.getJSONCb('ciniki.fatt.offeringDelete', {'business_id':M.curBusinessID, 'offering_id':oid}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_offerings.offering.close();
			});
		}
	};


	//
	// Date edit functions
	//
	this.dateEdit = function(cb, did, oid) {
		if( did != null ) { this.odate.date_id = did; }
		if( oid != null ) { this.odate.offering_id = oid; }
		this.odate.sections._buttons.buttons.delete.visible = (this.odate.date_id>0?'yes':'no');
		M.api.getJSONCb('ciniki.fatt.offeringDateGet', {'business_id':M.curBusinessID, 
			'date_id':this.odate.date_id, 'offering_id':this.odate.offering_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_offerings.odate;
				p.data = rsp.offeringdate;
				p.sections.address.visible = 'hidden';
				p.sections._map.visible = 'hidden';
				p.sections._map_buttons.visible = 'hidden';
				if( rsp.offeringdate.location_id > 0 ) {
					for(var i in p.locations) {
						if( rsp.offeringdate.location_id != null && p.locations != null && p.locations[rsp.offeringdate.location_id] != null && (p.locations[rsp.offeringdate.location_id].flags&0x01) == 1 ) {
							p.sections.address.visible = 'yes';
							p.sections._map.visible = 'yes';
							p.sections._map_buttons.visible = 'yes';
						}
					}
				}
				p.refresh();
				p.show(cb);
		});
	};

	this.dateSave = function() {
		if( this.odate.date_id > 0 ) {
			var c = this.odate.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.fatt.offeringDateUpdate', {'business_id':M.curBusinessID,
					'date_id':this.odate.date_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_fatt_offerings.edit.data.dates = (rsp.offering.dates!=null?rsp.offering.dates:{});
						M.ciniki_fatt_offerings.odate.close();
					});
			} else {
				this.odate.close();
			}
		} else {
			var c = this.odate.serializeForm('yes');
			M.api.postJSONCb('ciniki.fatt.offeringDateAdd', {'business_id':M.curBusinessID, 'offering_id':this.odate.offering_id}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_offerings.edit.data.dates = (rsp.offering.dates!=null?rsp.offering.dates:{});
				M.ciniki_fatt_offerings.odate.close();
			});
		}
	};

	this.dateDelete = function() {
		if( confirm('Are you sure you want to remove this date?') ) {
			M.api.getJSONCb('ciniki.fatt.offeringDateDelete', {'business_id':M.curBusinessID, 'date_id':this.odate.date_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_offerings.edit.data.dates = (rsp.offering.dates!=null?rsp.offering.dates:{});
				M.ciniki_fatt_offerings.odate.close();
			});
		}
	};

	this.showFromCustomer = function(cid) {
		this.registrationEdit(this.registration.cb, cid, this.registration.offering_id, 0);
	};

	this.classShow = function(cb, cid) {
		if( cid != null ) { this.class.class_id = cid; }
		M.api.getJSONCb('ciniki.fatt.classGet', {'business_id':M.curBusinessID, 
			'class_id':this.class.class_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_offerings.class;
				p.data = rsp.class;
				p.sections.details.list.location_address.visible = 'no';
				if( (rsp.class.location_flags&0x01) == 1 ) {
					p.sections.details.list.location_address.visible = 'yes';
					p.data.location_address = M.formatAddress(rsp.class);
				}
				if( rsp.class.registrations != null && rsp.class.registrations.length > 0 ) {
					p.sections.offerings.aside = 'yes';
					p.sections.registrations.visible = 'yes';
					p.sections._buttons.buttons.printlist.visible = 'yes';
				} else {
					p.sections.offerings.aside = 'no';
					p.sections.registrations.visible = 'no';
					p.sections._buttons.buttons.printlist.visible = 'no';
				}
				p.refresh();
				p.show(cb);
		});
	};

	this.classPrintList = function(cid) {
		M.api.openFile('ciniki.fatt.classRegistrations', {'business_id':M.curBusinessID, 'output':'pdf', 'class_id':cid});
	};

	this.addShow = function(cb, d, t, ad) {
		this.add.reset();
		var p = d.split(/-/);
		var d = new Date(p[0],p[1]-1,p[2]);
		this.add.data = {'day1':M.dateFormat(d) + (ad==0?' ' + t:' 8:30 am'), 'num_seats':'1'};
		d.setDate(d.getDate() + 1);
		this.add.data['day2'] = M.dateFormat(d) + (ad==0?' ' + t:' 8:30 am');
		this.add.sections.details.fields.day2.visible = (this.add.ndays>1?'yes':'no');
		this.add.refresh();
		this.add.show(cb);
	};

	this.addCourses = function() {
		var c = this.add.serializeForm('yes');
		if( this.add.customer_id > 0 ) {
			c += '&customer_id=' + encodeURIComponent(this.add.customer_id);
		}
		M.api.postJSONCb('ciniki.fatt.classAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			M.ciniki_fatt_offerings.add.close();
		});
	};
}
