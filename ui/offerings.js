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
			'offerings':{'label':'Courses', 'type':'simplegrid', 'num_cols':2,
				'sortables':'yes',
				'headerValues':['Date', 'Course', 'Location', '#', 'Status'],
				'sortTypes':['text', 'date', 'text'],
				'cellClasses':['multiline', 'multiline', 'multiline'],
				'noData':'No offerings',
				},
		};
		this.offerings.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.offering.start_date;
				case 1: return d.offering.course_name;
				case 2: return d.offering.location_name;
				case 3: return d.offering.seats_remaining + '/' + d.offering.num_registrations;
				case 4: return d.offering.status_text;
			}
		};
		this.offerings.rowFn = function(s, i, d) {
			return 'M.ciniki_fatt_offerings.offeringShow(\'M.ciniki_fatt_offerings.showOfferings();\',\'' + d.offering.id + \'');';
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
		
		this.offering.addButton('edit', 'Edit', 'M.ciniki_fatt_offerings.offeringEdit(\'M.ciniki_fatt_offerings.offeringShow();\',0);');
		this.offering.addClose('Back');
	
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

		if( args.offering_id != null ) {
			this.offeringShow(cb, args.offering_id);
		} else {
			var dt = new Date();
			this.showOfferings(cb, dt.getFullYear(), 0);
		}
	};

	this.showOfferings(cb, year, month) {
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
				p.data.offerings = rsp.offerings;
				p.sections.years.tabs = {};
				if( rsp.years != null ) {
					for(var i in rsp.years) {
						p.sections.years.tabs[i] = {'label':rsp.years[i].year.year, 'fn':'M.ciniki_fatt_offerings.showOfferings(null,\'' + rsp.years[i].year.year + '\');'};
					}
				}
				p.refresh();
				p.show(cb);
			});
	};

}
