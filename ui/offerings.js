//
// The panels to show offerings by year/month
//
function ciniki_fatt_offerings() {
	this.courseForms = {
		'':'None',
		};

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
				'sortables':'yes',
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
				case 2: return d.offering.location_name;
				case 3: 
					var mt = '';
					var mt = d.offering.seats_remaining + ' left';
					if( d.offering.seats_remaining < 0 ) {
						mt = Math.abs(d.offering.seats_remaining) + ' oversold';
					} else if( d.offering.seats_remaining == 0 ) {
						mt = 'Sold Out';
					}
					if( d.offering.max_seats > 0 ) {
						var st = d.offering.num_registrations + ' of ' + d.offering.max_seats;
					} else {
						var st = d.offering.num_registrations + ' registered';
					}
					return '<span class="maintext">' + mt + '</span><span class="subtext">' + st + '</span>';
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
		this.offering.data = {};
		this.offering.sections = {	
			'details':{'label':'', 'aside':'yes', 'list':{
				'course_name':{'label':'Course'},
				'price':{'label':'Price'},
				'date_display':{'label':'When'},
				'location':{'label':'Location'},
				'flags_display':{'label':'Options'},
				'max_seats':{'label':'Max Capacity'},
				'seats_remaining':{'label':'Available'},
				}},
			'instructors':{'label':'Instructors', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
				'cellClasses':['multiline', 'multiline'],
				},
			'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':1,
				'addTxt':'Add Registration',
				'addFn':'M.ciniki_fatt_offerings.registrationAdd();',
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
			return this.data[i]; };
		this.offering.cellValue = function(s, i, j, d) {
			if( s == 'instructors' ) {
				return '<span class="maintext">' + d.instructor.name + '</span><span class="subtext">' + d.instructor.email + (d.instructor.email!=''?' - ':'') + d.instructor.phone + '</span>';
			} else if( s == 'registrations' ) {
				if( d.registration.customer_id != d.registration.student_id ) {
					return (d.registration.student_name!=''?d.registration.student_name:'???') + ' <span class="subdue">[' + d.regisration.customer_name + ']</span>';
				} 
				return d.registration.customer_name;
			}
		};
		this.offering.rowFn = function(s, i, d) {
			if( s == 'registrations' ) {
				return 'M.ciniki_fatt_offerings.offeringEdit(\'M.ciniki_fatt_offerings.offeringShow();\',M.ciniki_fatt_offerings.offering.offering_id);';
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
		this.edit.course_id = 0;
		this.edit.data = {};
		this.edit.sections = {
			'details':{'label':'', 'aside':'yes', 'fields':{
				'course_id':{'label':'Course', 'type':'select', 'options':{}},
				'price':{'label':'Price', 'type':'text', 'size':'small'},
				'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}, '5':{'name':'Online Registrations'}}},
				}},
			'_instructors':{'label':'Instructors', 'aside':'yes', 'active':'no', 'fields':{
				'instructors':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'itemname':'instructor', 'list':{}},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_offerings.offeringSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_offerings.offeringDelete(M.ciniki_fatt_offerings.edit.offering_id);'},
				}},
		};
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
		}
		this.edit.addButton('save', 'Save', 'M.ciniki_fatt_offerings.offeringSave();');
		this.edit.addClose('Cancel');
	
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
		var courses = {};
		if( M.curBusiness.modules['ciniki.fatt'].settings.courses != null ) {
			for(var i in M.curBusiness.modules['ciniki.fatt'].settings.courses) {
				courses[M.curBusiness.modules['ciniki.fatt'].settings.courses[i].course.id] = M.curBusiness.modules['ciniki.fatt'].settings.courses[i].course.name;
			}
		}
		this.edit.sections.details.fields.course_id.options = courses;

		//
		// Use settings to update instructor list
		//
		this.edit.sections._instructors.active = 'no';
		if( M.curBusiness.modules['ciniki.fatt'].settings.instructors != null ) {
			this.edit.sections._instructors.fields.instructors.list = M.curBusiness.modules['ciniki.fatt'].settings.instructors;
			if( M.curBusiness.modules['ciniki.fatt'].settings.instructors.length > 0 ) {
				this.edit.sections._instructors.active = 'yes';
			}
		} else {
			this.edit.sections._instructors.fields.instructors.list = {};
		}

		//
		// Decide what to show
		//
		if( args.offering_id != null ) {
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
						p.sections.years.tabs[i] = {'label':yrs[i], 'fn':'M.ciniki_fatt_offerings.showOfferings(null,\'' + yrs[i] + '\');'};
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

	this.offeringDelete = function() {
		if( confirm('Are you sure you want to remove this offering?') ) {
			M.api.getJSONCb('ciniki.fatt.offeringDelete', {'business_id':M.curBusinessID, 'course_id':lid}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_settings.edit.close();
			});
		}
	};


	//
	// Date edit functions
	//
	this.dateEdit = function(cb, oid, did) {
		if( oid != null ) { this.odate.offering_id = oid; }
		if( did != null ) { this.odate.date_id = did; }
		
	};

	this.dateSave = function() {
	};

	this.dateDelete = function() {
	};

	//
	// Registration Edit
	//
	this.registrationEdit = function(cb, oid, rid) {
		if( oid != null ) { this.odate.offering_id = oid; }
		if( rid != null ) { this.odate.registration_id = rid; }
		
	};

	this.registrationDelete = function() {

	};

}
