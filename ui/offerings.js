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
				'headerValues':['Course', 'Dates', 'Status'],
				'sortTypes':['text', 'date', 'text'],
				'cellClasses':['multiline', 'multiline', 'multiline'],
				'noData':'No offerings',
				},
		};
		this.offerings.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.offering.course_name;
				case 1: return d.offering.start_date;
				case 2: return d.offering.status_text;
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
			this.showOfferings(cb);
		}
	};

	this.showOfferings(cb, year, month) {
	};

}
