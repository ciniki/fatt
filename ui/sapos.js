//
// The panels that allow sapos to hook into fatt module
//
function ciniki_fatt_sapos() {
	// Placeholder for adding customer/registration
	this.regadd = {};

	this.init = function() {
		//
		// The registration panel
		//
		this.registration = new M.panel('Registration',
			'ciniki_fatt_sapos', 'registration',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.sapos.registration');
		this.registration.offering_id = 0;
		this.registration.registration_id = 0;
		this.registration.student_id = 0;
		this.registration.item_id = 0;
		this.registration._source = '';
		this.registration.data = {};
		this.registration.sections = {
			'course':{'label':'Course', 'aside':'yes', 'list':{
				'course_name':{'label':'Course'},
				'price':{'label':'Price'},
				'date_string':{'label':'When'},
				'location':{'label':'Location'},
				}},
			'customer_details':{'label':'Bill To', 'visible':'no', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['label',''],
				},
			'student_details':{'label':'Student', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['label',''],
				'addTxt':'Edit',
				'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_sapos.registration.updateStudent(null);\',\'mc\',{\'next\':\'M.ciniki_fatt_sapos.registration.updateStudent\',\'customer_id\':M.ciniki_fatt_sapos.registration.student_id});',
				'changeTxt':'Edit',
				'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_sapos.registration.updateStudent(null);\',\'mc\',{\'next\':\'M.ciniki_fatt_sapos.registration.updateStudent\',\'customer_id\':0});',
				},
			'invoice_details':{'label':'Invoice', 'type':'simplegrid', 'num_cols':2,
				'cellClasses':['label',''],
				},
			'details':{'label':'', 'fields':{
				'unit_amount':{'label':'Price', 'type':'text', 'size':'small'},
				'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
				'unit_discount_percentage':{'label':'Discount %', 'type':'text', 'size':'small'},
				'taxtype_id':{'label':'Taxes', 'type':'select', 'options':{}},
				}},
//			'_test_results':{'label':'Test Results', 'fields':{
//				}},
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_sapos.registrationSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_sapos.registrationDelete();'},
				}},
		};
		this.registration.sectionData = function(s) { 
			if( s == 'course' ) { return this.sections[s].list; }
			return this.data[s];
		};
		this.registration.fieldValue = function(s, i, d) {
			if( s == 'invoice' ) { return this.data[s]!=null?{'invoice':this.data[s]}:{}; }
			if( this.data[i] == null ) { return ''; }
			return this.data[i];
		};
		this.registration.listLabel = function(s, i, d) {
			return d.label;
		};
		this.registration.listValue = function(s, i, d) {
			return this.data[i];
		};
		this.registration.listFn = function(s, i, d) {
			if( s == 'course' && this._source != 'offering' ) {
				return 'M.startApp(\'ciniki.fatt.offerings\',null,\'M.ciniki_fatt_sapos.registrationEdit();\',\'mc\',{\'offering_id\':\'' + this.data.offering_id + '\'});'; 
			}
			return '';
		};
		this.registration.fieldHistoryArgs = function(s, i) {
			if( s == 'details' ) {
				return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID, 'object':'ciniki.sapos.invoice_item', 'object_id':this.item_id, 'field':i}};
			}
			return {'method':'ciniki.fatt.offeringRegistrationHistory', 'args':{'business_id':M.curBusinessID, 'registration_id':this.registration_id, 'field':i}};
		};
		this.registration.cellValue = function(s, i, j, d) {
			if( s == 'customer_details' || s == 'student_details' || s == 'invoice_details' ) {
				switch(j) {
					case 0: return d.detail.label;
					case 1: return d.detail.value.replace(/\n/, '<br/>');
				}
			} 
		};
		this.registration.rowFn = function(s, i, d) {
			if( s == 'invoice_details' && this._source != 'invoice' ) { 
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_fatt_sapos.registrationEdit();\',\'mc\',{\'invoice_id\':\'' + this.data.invoice_id + '\'});'; 
			}
			if( s == 'student_details' ) {
				return '';
			}
		};
		this.registration.updateStudent = function(cid) {
			if( cid != null && cid != this.student_id ) {
				this.student_id = cid;
			}
			if( this.student_id > 0 ) {
				M.api.getJSONCb('ciniki.customers.customerDetails', {'business_id':M.curBusinessID, 'customer_id':this.student_id, 'phones':'yes', 'emails':'yes', 'addresses':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_fatt_sapos.registration;
					p.data.student_details = rsp.details;
					p.updateCustomers();
					p.refreshSection('student_details');
					p.refreshSection('customer_details');
					p.show();
				});
			} else {
				this.data.student_details = {};
				this.updateCustomers();
				this.refreshSection('student_details');
				this.refreshSection('customer_details');
				this.show();
			}
		};
		this.registration.updateCustomers = function() {
			if( this.data.customer_id == this.student_id ) {
				this.data.student_details = this.data.customer_details;
				this.sections.customer_details.visible = 'hidden';
			} else {
				this.sections.customer_details.visible = 'yes';
			}
			if( this.student_id == 0 ) {
				this.sections.student_details.addTxt = '';
				this.sections.student_details.changeTxt = 'Add';
			} else {
				this.sections.student_details.addTxt = 'Edit';
				this.sections.student_details.changeTxt = 'Change';
			}
		};
		this.registration.addButton('save', 'Save', 'M.ciniki_fatt_sapos.registrationSave();');
		this.registration.addClose('Cancel');
	};

	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_fatt_sapos', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		//
		// Setup the taxtypes available for the business
		//
		if( M.curBusiness.modules['ciniki.taxes'] != null ) {
			this.registration.sections.details.fields.taxtype_id.active = 'yes';
			this.registration.sections.details.fields.taxtype_id.options = {'0':'No Taxes'};
			if( M.curBusiness.modules != null && M.curBusiness.modules['ciniki.taxes'] != null && M.curBusiness.modules['ciniki.taxes'].settings.types != null ) {
				for(i in M.curBusiness.taxes.settings.types) {
					this.registration.sections.details.fields.taxtype_id.options[M.curBusiness.taxes.settings.types[i].type.id] = M.curBusiness.taxes.settings.types[i].type.name;
				}
			}
		} else {
			this.registration.sections.details.fields.taxtype_id.active = 'no';
			this.registration.sections.details.fields.taxtype_id.options = {'0':'No Taxes'};
		}
		
		//
		// Decide what to show
		//
		if( args.offering_id != null ) {
			this.registrationAdd(cb, args.offering_id);
		} else if( args.item_object != null && args.item_object == 'ciniki.fatt.offeringregistration' ) {
			this.registrationEdit(cb, args.item_object_id, args.source);
		} else if( args.registration_id != null ) {
			this.registrationEdit(cb, args.registration_id, args.source);
		} else {
			console.log('UI Error: unrecognized object');
		}
	};

	//
	// Registration Edit
	//
	this.registrationAdd = function(cb, oid) {
		this.regadd.offering_id = oid;
		this.regadd.cb = cb;
		M.startApp('ciniki.customers.edit',null,cb,'mc',{'next':'M.ciniki_fatt_sapos.invoiceCreate', 'customer_id':0});
	};

	this.invoiceCreate = function(cid) {
		args = {
			'customer_id':cid,
			'object':'ciniki.fatt.offering',
			'object_id':this.regadd.offering_id,
			'payment_status':10,
			};
		M.startApp('ciniki.sapos.invoice',null,this.regadd.cb,'mc',args);
//		M.api.getJSONCb('ciniki.fatt.offeringRegistrationAdd', {'business_id':M.curBusinessID, 'offering_id':this.registration.offering_id}, function(rsp) {
//			if( rsp.stat != 'ok' ) {
//				M.api.err(rsp);
//				return false;
//			}
//			var p = M.ciniki_fatt_offerings.registration;
//			p.data = rsp.registration;
//			p.registration_id = rsp.registration.id;
//			p.refresh();
//			p.show();
//		});
	};

	this.registrationEdit = function(cb, rid, source) {
		this.registration.reset();
		if( rid != null ) { this.registration.registration_id = rid; }
		if( source != null ) { this.registration._source = source; }
		M.api.getJSONCb('ciniki.fatt.offeringRegistrationGet', {'business_id':M.curBusinessID, 
			'registration_id':this.registration.registration_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_fatt_sapos.registration;
				p.data = rsp.registration;
				if( rsp.registration.item_id != null ) {
					p.item_id = rsp.registration.item_id;
				}
				p.student_id = rsp.registration.student_id;
				p.updateCustomers();
				p.refresh();
				p.show(cb);
		});
	};

	this.registrationSave = function() {
		c = this.registration.serializeForm('no');
		if( this.registration.student_id != this.registration.data.student_id ) {
			c += '&student_id=' + this.registration.student_id;
		}
		if( c != '' ) {
			M.api.postJSONCb('ciniki.fatt.offeringRegistrationUpdate', {'business_id':M.curBusinessID,
				'registration_id':this.registration.registration_id, 'item_id':this.registration.item_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_fatt_sapos.registration.close();
				});
		} else {
			this.registration.close();
		}
	};

	this.registrationDelete = function() {

	};

}
