//
// This file contains the UI panels to manage customer certs and their expirations
//
function ciniki_fatt_certs() {
	this.init = function() {
		//
		// The menu panel
		//
		this.main = new M.panel('Settings',
			'ciniki_fatt_certs', 'main',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.certs.main');
		this.main.sections = {	
		};
		this.main.addClose('Back');

		//
		// The cert expirations panel
		//
		this.expirations = new M.panel('Certication Expirations',
			'ciniki_fatt_certs', 'expirations',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.certs.expirations');
		this.expirations.data = {};
		this.expirations.sections = {
			'certs':{'label':'Expirations', 'type':'simplegrid', 'num_cols':3,
				'headerValues':['Certification', 'Customer', 'Expiration'],
				'sortable':'yes',
				'sortTypes':['text', 'text', 'altnumber'],
				'cellClasses':['multiline', 'multiline', 'multiline'],
				'noData':'No certifications',
				},
			};
		this.expirations.sectionData = function(s) { return this.data[s]; }
		this.expirations.noData = function(s) { return this.sections[s].noData; }
		this.expirations.cellValue = function(s, i, j, d) {
			switch (j) {
				case 0: return '<span class="maintext">' + d.cert.name + '</span><span class="subtext">' + d.cert.date_received + '</span>';
				case 1: return d.cert.display_name;
				case 2: return '<span class="maintext">' + d.cert.expiry_text + '</span><span class="subtext">' + d.cert.date_expiry + '</span>';
			}
		}
		this.expirations.cellSortValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.cert.name;
				case 1: return d.cert.display_name;
				case 2: return d.cert.days_till_expiry;
			}
		};
		this.expirations.rowFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_fatt_certs.expirationsShow();\',\'mc\',{\'customer_id\':\'' + d.cert.customer_id + '\'});';
		}
		this.expirations.addClose('Back');

		//
		// The cert customer edit panel
		//
		this.certcustomer = new M.panel('Customer Certification',
			'ciniki_fatt_certs', 'certcustomer',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.certs.certcustomer');
		this.certcustomer.certcustomer_id = 0;
		this.certcustomer.data = {};
		this.certcustomer.sections = {
			'customer_details':{'label':'Customer', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['label', ''],
				'addTxt':'Edit',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_certs.certcustomerShow();\',\'mc\',{\'next\':\'M.ciniki_fatt_certs.certcustomer.updateCustomer\',\'customer_id\':M.ciniki_fatt_certs.certcustomer.data.customer_id});',
				},
			'cert':{'label':'', 'fields':{
				'cert_id':{'label':'Certification', 'type':'select'},
				'date_received':{'label':'Date', 'type':'date'},
				'flags':{'label':'Options', 'type':'flags', 'default':'1', 'flags':{}},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_certs.certcustomerSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_certs.certcustomerDelete(M.ciniki_fatt_certs.certcustomer.certcustomer_id);'},
				}},
		};
		this.certcustomer.sectionData = function(s) { return this.data[s]; }
		this.certcustomer.fieldValue = function(s, i, d) {
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.certcustomer.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.certCustomerHistory', 'args':{'business_id':M.curBusinessID, 'certcustomer_id':this.certcustomer_id, 'field':i}};
		}
		this.certcustomer.cellValue = function(s, i, j, d) {
			if( s == 'customer_details' ) {
				switch (j) {
					case 0: return d.detail.label;
					case 1: return (d.detail.label == 'Email'?M.linkEmail(d.detail.value):d.detail.value);
				}
			}
		}
		this.certcustomer.rowFn = function(s, i, d) {
			return '';
		}
		this.certcustomer.updateCustomer = function(cid) {
			if( cid != this.customer_id ) { this.customer_id = cid; }
			M.api.getJSONCb('ciniki.fatt.certCustomerGet', {'business_id':M.curBusinessID, 
				'certcustomer_id':this.certcustomer_id, 
				'cert_id':this.cert_id, 
				'customer_id':this.customer_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_fatt_certs.certcustomer;
					p.data.customer_details = rsp.certcustomer.customer_details;
					p.refreshSection('customer_details');
					p.show();
			});
		}
		this.certcustomer.addButton('save', 'Save', 'M.ciniki_fatt_certs.certcustomerSave();');
		this.certcustomer.addClose('Cancel');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_fatt_certs', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		// 
		// Setup certcustomer cert listing
		//
		this.certcustomer.sections.cert.fields.cert_id.options = {};
		if( M.curBusiness.modules['ciniki.fatt'] != null
			&& M.curBusiness.modules['ciniki.fatt'].settings != null
			&& M.curBusiness.modules['ciniki.fatt'].settings.certs != null
			) {
			var certs = {};
			for(i in M.curBusiness.modules['ciniki.fatt'].settings.certs) {
				certs[M.curBusiness.modules['ciniki.fatt'].settings.certs[i].cert.id] = M.curBusiness.modules['ciniki.fatt'].settings.certs[i].cert.name;
			}
			this.certcustomer.sections.cert.fields.cert_id.options = certs;
		}

		//
		// Setup certcustomer flags
		//
		var flags = {};
		if( (M.curBusiness.modules['ciniki.fatt'].flags&0x20) > 0 ) { 
			flags['1'] = {'name':'Expiry Reminders'};
		}
		this.certcustomer.sections.cert.fields.flags.flags = flags;

		//
		// Decide what to show
		//
		if( args.certcustomer_id != null ) {
			this.certcustomerEdit(cb, args.certcustomer_id, args.cert_id, args.customer_id);
		} else {
			var edt = new Date();
			var sdt = new Date();
			edt.setDate(edt.getDate()+90);
			this.expirationsShow(cb, sdt.toDateString(), edt.toDateString());
		}
	}

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMain = function(cb) {
		this.main.refresh();
		this.main.show(cb);
	}

	this.expirationsShow = function(cb, sd, ed) {
		if( sd != null ) { this.expirations.start_date = sd; }
		if( ed != null ) { this.expirations.end_date = ed; }
		M.api.getJSONCb('ciniki.fatt.certCustomerExpirations', {'business_id':M.curBusinessID, 
			'start_date':this.expirations.start_date, 'end_date':this.expirations.end_date}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_certs.expirations;
				p.data = rsp;
				p.refresh();
				p.show(cb);
		});
	};

	this.certcustomerEdit = function(cb, ccid, cert_id, customer_id) {
		if( ccid != null ) { this.certcustomer.certcustomer_id = ccid; }
		if( cert_id != null ) { this.certcustomer.cert_id = cert_id; }
		if( customer_id != null ) { this.certcustomer.customer_id = customer_id; }
		this.certcustomer.sections._buttons.buttons.delete.visible = (this.certcustomer.certcustomer_id>0?'yes':'no');
		M.api.getJSONCb('ciniki.fatt.certCustomerGet', {'business_id':M.curBusinessID, 
			'certcustomer_id':this.certcustomer.certcustomer_id, 
			'cert_id':this.certcustomer.cert_id, 
			'customer_id':this.certcustomer.customer_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_certs.certcustomer;
				p.data = rsp.certcustomer;
				p.customer_id = rsp.certcustomer.customer_id;
				p.sections.customer_details.addTxt = (p.data.customer_id>0?'Edit Customer':'Select Customer');
				p.refresh();
				p.show(cb);
		});
	};

	this.certcustomerSave = function() {
		if( this.certcustomer.certcustomer_id > 0 ) {
			var c = this.certcustomer.serializeForm('no');
			if( this.certcustomer.customer_id != this.certcustomer.data.customer_id ) {
				c += '&customer_id=' + this.certcustomer.customer_id;
			}
			if( c != '' ) {
				M.api.postJSONCb('ciniki.fatt.certCustomerUpdate', {'business_id':M.curBusinessID,
					'certcustomer_id':this.certcustomer.certcustomer_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_fatt_certs.certcustomer.close();
					});
			} else {
				this.certcustomer.close();
			}
		} else {
			var c = this.certcustomer.serializeForm('yes');
			c += '&customer_id=' + this.certcustomer.customer_id;
			M.api.postJSONCb('ciniki.fatt.certCustomerAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_certs.certcustomer.close();
			});
		}
	};

	this.certcustomerDelete = function(cid) {
		if( confirm('Are you sure you want to remove this customer certification?') ) {
			M.api.getJSONCb('ciniki.fatt.certCustomerDelete', {'business_id':M.curBusinessID, 'certcustomer_id':cid}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_fatt_certs.certcustomer.close();
			});
		}
	};
}
