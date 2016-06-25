//
// The panels that allow sapos to hook into fatt module
//
function ciniki_fatt_sapos() {
    // Placeholder for adding customer/registration
    this.regadd = {};

    this.regStatus = {
        '0':'Registered',
        '30':'Cancelled',
        '40':'No Show',
        '10':'Pass',
        '50':'Fail',
    };

    this.init = function() {
        //
        // The registration panel
        //
        this.registration = new M.panel('Registration',
            'ciniki_fatt_sapos', 'registration',
            'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.sapos.registration');
        this.registration.cbStacked = 'yes';
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
                'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_sapos.registration.updateStudent(null);\',\'mc\',{\'next\':\'M.ciniki_fatt_sapos.registration.updateStudent\',\'customer_id\':0,\'parent_id\':M.ciniki_fatt_sapos.registration.data.customer_id,\'parent_name\':escape(M.ciniki_fatt_sapos.registration.data.customer_name)});',
                },
            'invoice_details':{'label':'Invoice', 'type':'simplegrid', 'num_cols':2,
                'visible':function() { return (M.ciniki_fatt_sapos.registration.data.invoice_id > 0 ? 'yes' : 'no'); },
                'cellClasses':['label',''],
                },
            'details':{'label':'', 
                'visible':function() { return (M.ciniki_fatt_sapos.registration.data.invoice_id > 0 ? 'yes' : 'no'); },
                'fields':{
                    'unit_amount':{'label':'Price', 'type':'text', 'size':'small'},
                    'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
                    'unit_discount_percentage':{'label':'Discount %', 'type':'text', 'size':'small'},
                    'taxtype_id':{'label':'Taxes', 'type':'select', 'options':{}},
                }},
            '_status':{'label':'Registration Status', 'fields':{
                'status':{'label':'Status', 'type':'toggle', 'toggles':M.ciniki_fatt_sapos.regStatus},
                }},
//          '_test_results':{'label':'Test Results', 'fields':{
//              }},
            '_notes':{'label':'Notes', 'fields':{
                'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
                }},
            'alternate_courses':{'label':'Switch Course', 'visible':'hidden', 'type':'simplegrid', 'num_cols':1, 
                },
            'alternate_dates':{'label':'Switch Date', 'visible':'hidden', 'type':'simplegrid', 'num_cols':1, 
                'cellClasses':['multiline'],
                },
            '_switch':{'label':'', 'buttons':{
                'switchcourse':{'label':'Switch Course', 'visible':'no', 'fn':'M.ciniki_fatt_sapos.registration.showAlternateCourses();'},
                'switchdate':{'label':'Switch Date', 'visible':'no', 'fn':'M.ciniki_fatt_sapos.registration.showAlternateDates();'},
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
//            if( i == 'course_name' && this.data.alternate_courses != null ) { 
//                return this.data[i] + '  <button onclick="event.stopPropagation();M.ciniki_fatt_sapos.registration.showAlternateCourses();">Switch Course</button>'; 
//            }
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
            else if( s == 'alternate_courses' ) {
                return M.curBusiness.modules['ciniki.fatt'].settings.courses[d.course_id].name;
            }
            else if( s == 'alternate_dates' ) {
                var sr = '';
                if( d.seats_remaining > 0 ) { sr = d.seats_remaining + ' available'; }
                if( d.seats_remaining == 0 ) { sr = 'Sold Out'; }
                if( d.seats_remaining < 0 ) { sr = Math.abs(d.seats_remaining) + ' over sold'; }
                return '<span class="maintext">' + d.date_string + ' <span class="subdue">' + sr + '</span></span><span class="subtext">' + d.location + '</span>';
            }
        };
        this.registration.rowStyle = function(s, i, d) {
            if( s == 'alternate_dates' ) {
                return 'background: ' + d.colour + ';';
            }
            return '';
        };
        this.registration.rowFn = function(s, i, d) {
            if( s == 'invoice_details' && this._source != 'invoice' ) { 
                return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_fatt_sapos.registrationEdit();\',\'mc\',{\'invoice_id\':\'' + this.data.invoice_id + '\'});'; 
            }
            if( s == 'student_details' ) {
                return '';
            }
            if( s == 'alternate_courses' ) {
                return 'M.ciniki_fatt_sapos.registrationSwitchCourse(\'' + d.id + '\');';
            }
            if( s == 'alternate_dates' ) {
                return 'M.ciniki_fatt_sapos.registrationSwitchDate(\'' + d.id + '\');';
            }
        };
        this.registration.showAlternateCourses = function() {
            document.getElementById(this.panelUID + '_section_alternate_courses').style.display = '';
            document.getElementById(this.panelUID + '_section_alternate_dates').style.display = 'none';
        };
        this.registration.showAlternateDates = function() {
            document.getElementById(this.panelUID + '_section_alternate_courses').style.display = 'none';
            document.getElementById(this.panelUID + '_section_alternate_dates').style.display = '';
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
                this.sections.customer_details.visible = 'yes';
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
            this.registrationAdd(cb, args.offering_id, args.saveseats);
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
    this.registrationAdd = function(cb, oid, ss) {
        this.regadd.offering_id = oid;
        this.regadd.cb = cb;
        if( ss == 'yes' ) {
            M.startApp('ciniki.customers.edit',null,cb,'mc',{'next':'M.ciniki_fatt_sapos.saveSeats', 'customer_id':0});
        } else {
            M.startApp('ciniki.customers.edit',null,cb,'mc',{'next':'M.ciniki_fatt_sapos.invoiceCheck', 'customer_id':0});
        }
    };

    this.saveSeats = function(cid) {
        //
        // FIXME: Make this a screen to search customers or add customer
        //
        var ns = prompt('How many seats?');
        if( ns != null ) {
            this.invoiceCheck(cid, ns);
        }
    }

    this.invoiceCheck = function(cid, ns) {
        M.api.getJSONCb('ciniki.fatt.classCustomerInvoice', 
            {'business_id':M.curBusinessID, 'offering_id':this.regadd.offering_id, 'customer_id':cid},
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var sd = '';
                if( rsp.start_date != null && rsp.start_date != '' ) {
                    sd = rsp.start_date;
                }
                if( rsp.invoice_id != null && rsp.invoice_id > 0 ) {
                    M.ciniki_fatt_sapos.invoiceCreate(cid, ns, rsp.invoice_id,sd);
                } else {
                    M.ciniki_fatt_sapos.invoiceCreate(cid, ns, null, sd);
                }
            });
    }

    this.invoiceCreate = function(cid, ns, invoice_id, start_date) {
        if( ns != null && ns > 1 ) {
            args = {
                'customer_id':cid,
                'bill_parent':'yes',
                'payment_status':10,
                'objects':[],
                };
            for(var i=0;i<ns;i++) {
                args['objects'][i] = {'object':'ciniki.fatt.offering','id':this.regadd.offering_id};
            }
        } else {
            args = {
                'customer_id':cid,
                'bill_parent':'yes',
                'object':'ciniki.fatt.offering',
                'object_id':this.regadd.offering_id,
                'payment_status':10,
                };
        }
        if( invoice_id != null && invoice_id > 0 ) {
            args['invoice_id'] = invoice_id;
        }
        if( start_date != null && start_date != '' ) {
            args['invoice_date'] = start_date;
        }
        M.startApp('ciniki.sapos.invoice',null,this.regadd.cb,'mc',args);
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
                p.sections._switch.buttons.switchcourse.visible = (rsp.registration.alternate_courses != null ? 'yes' : 'no');
                p.sections._switch.buttons.switchdate.visible = (rsp.registration.alternate_dates != null ? 'yes' : 'no');
                if( rsp.registration.invoice_status < 50 || (M.curBusiness.sapos.settings['rules-invoice-paid-change-items'] != null && M.curBusiness.sapos.settings['rules-invoice-paid-change-items'] == 'yes')) {
                    p.sections._buttons.buttons.delete.visible = 'yes';
                } else { 
                    p.sections._buttons.buttons.delete.visible = 'no';
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

    this.registrationSwitchCourse = function(oid) {
        M.api.getJSONCb('ciniki.fatt.offeringRegistrationSwitchOffering', {'business_id':M.curBusinessID, 
            'registration_id':this.registration.registration_id, 'item_id':this.registration.data.item_id, 'offering_id':oid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_sapos.registration.close();
            });
    };

    this.registrationSwitchDate = function(oid) {
        M.api.getJSONCb('ciniki.fatt.offeringRegistrationSwitchOffering', {'business_id':M.curBusinessID, 
            'registration_id':this.registration.registration_id, 'item_id':this.registration.data.item_id, 'offering_id':oid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_sapos.registration.close();
            });
    };

    this.registrationDelete = function() {
        if( confirm('Are you sure you want to remove this registration? It will remove it from the invoice as well.') ) {
            M.api.getJSONCb('ciniki.fatt.offeringRegistrationDelete', {'business_id':M.curBusinessID, 'registration_id':this.registration.registration_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_sapos.registration.close();
            });
        }

    };

}
