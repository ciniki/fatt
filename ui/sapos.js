//
// The panels that allow sapos to hook into fatt module
//
function ciniki_fatt_sapos() {
    // Placeholder for adding customer/registration
    this.regadd = {};

    this.regStatus = {
        '0':'Registered',
        '20':'Incomplete',
        '30':'Cancelled',
        '40':'No Show',
        '10':'Pass',
        '50':'Fail',
    };

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
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_sapos.registration.updateStudent(null);\',\'mc\',{\'next\':\'M.ciniki_fatt_sapos.registration.updateStudent\',\'action\':\'edit\',\'customer_id\':M.ciniki_fatt_sapos.registration.student_id});',
            'changeTxt':'Edit',
            'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_sapos.registration.updateStudent(null);\',\'mc\',{\'next\':\'M.ciniki_fatt_sapos.registration.updateStudent\',\'action\':\'change\',\'current_id\':M.ciniki_fatt_sapos.registration.student_id,\'customer_id\':0,\'parent_id\':M.ciniki_fatt_sapos.registration.data.customer_id,\'parent_name\':escape(M.ciniki_fatt_sapos.registration.data.customer_name)});',
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
            'status':{'label':'Status', 'type':'toggle', 'toggles':this.regStatus},
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
        'messages':{'label':'Messages', 'visible':'yes', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return (M.ciniki_fatt_sapos.registration.data.messages != null && M.ciniki_fatt_sapos.registration.data.messages.length > 0 ? 'yes' : 'no'); },
            'cellClasses':['multiline', 'multiline'],
//            'addTxt':'Email Customer',
//            'addFn':'M.ciniki_fatt_sapos.emailCustomer(\'M.ciniki_fatt_sapos.registration.open();\',M.ciniki_sapos_invoice.invoice.data);',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_sapos.registration.save();'},
            'emailwelcome':{'label':'Email Welcome Message', 'fn':'M.ciniki_fatt_sapos.registration.save(\'M.ciniki_fatt_sapos.registration.emailWelcomeMsg();\');'},
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
            return 'M.startApp(\'ciniki.fatt.offerings\',null,\'M.ciniki_fatt_sapos.registration.open();\',\'mc\',{\'offering_id\':\'' + this.data.offering_id + '\'});'; 
        }
        return '';
    };
    this.registration.fieldHistoryArgs = function(s, i) {
        if( s == 'details' ) {
            return {'method':'ciniki.sapos.history', 'args':{'tnid':M.curTenantID, 'object':'ciniki.sapos.invoice_item', 'object_id':this.item_id, 'field':i}};
        }
        return {'method':'ciniki.fatt.offeringRegistrationHistory', 'args':{'tnid':M.curTenantID, 'registration_id':this.registration_id, 'field':i}};
    };
    this.registration.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' || s == 'student_details' || s == 'invoice_details' ) {
            if( d.detail != null ) {
                switch(j) {
                    case 0: return d.detail.label;
                    case 1: return d.detail.value.replace(/\n/, '<br/>');
                }
            } else {
                switch(j) {
                    case 0: return d.label;
                    case 1: return d.value.replace(/\n/, '<br/>');
                }
            }
        } 
        else if( s == 'alternate_courses' ) {
            return M.curTenant.modules['ciniki.fatt'].settings.courses[d.course_id].name;
        }
        else if( s == 'alternate_dates' ) {
            var sr = '';
            if( d.seats_remaining > 0 ) { sr = d.seats_remaining + ' available'; }
            if( d.seats_remaining == 0 ) { sr = 'Sold Out'; }
            if( d.seats_remaining < 0 ) { sr = Math.abs(d.seats_remaining) + ' over sold'; }
            return '<span class="maintext">' + d.date_string + ' <span class="subdue">' + sr + '</span></span><span class="subtext">' + d.location + '</span>';
        }
        else if( s == 'messages' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.status_text + '</span><span class="subtext">' + d.date_sent + '</span>';
                case 1: return '<span class="maintext">' + d.customer_email + '</span><span class="subtext">' + d.subject + '</span>';
            }
        }
    };
    this.registration.rowStyle = function(s, i, d) {
        if( s == 'alternate_dates' ) {
            return 'background: ' + d.colour + ';';
        }
        return '';
    };
    this.registration.rowFn = function(s, i, d) {
        if( d == null ) { return ''; }
        if( s == 'invoice_details' && this._source != 'invoice' ) { 
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_fatt_sapos.registration.open();\',\'mc\',{\'invoice_id\':\'' + this.data.invoice_id + '\'});'; 
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
            M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID, 'customer_id':this.student_id, 'phones':'yes', 'emails':'yes', 'addresses':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_sapos.registration;
                if( p.student_id == p.data.customer_id ) {
                    p.data.customer_details = rsp.customer_details;
                } else {
                    p.data.student_details = rsp.customer_details;
                }
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
    this.registration.emailWelcomeMsg = function() {
        M.api.getJSONCb('ciniki.fatt.offeringRegistrationWelcomeEmailSend', {'tnid':M.curTenantID, 'registration_id':this.registration_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_fatt_sapos.registration.open();
        });
    }
    this.registration.open = function(cb, rid, source) {
        this.reset();
        if( rid != null ) { this.registration_id = rid; }
        if( source != null ) { this._source = source; }
        M.api.getJSONCb('ciniki.fatt.offeringRegistrationGet', {'tnid':M.curTenantID, 'registration_id':this.registration_id}, function(rsp) {
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
            if( rsp.registration.invoice_status < 50 || (M.curTenant.sapos.settings['rules-invoice-paid-change-items'] != null && M.curTenant.sapos.settings['rules-invoice-paid-change-items'] == 'yes')) {
                p.sections._buttons.buttons.delete.visible = 'yes';
            } else { 
                p.sections._buttons.buttons.delete.visible = 'no';
            }
            p.student_id = rsp.registration.student_id;
            p.updateCustomers();
            p.refresh();
            p.show(cb);
        });
    }
    this.registration.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_fatt_sapos.registration.close();'; }
        c = this.serializeForm('no');
        if( this.student_id != this.data.student_id ) {
            c += '&student_id=' + this.student_id;
        }
        if( c != '' ) {
            M.api.postJSONCb('ciniki.fatt.offeringRegistrationUpdate', {'tnid':M.curTenantID,
                'registration_id':this.registration_id, 'item_id':this.item_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
        } else {
            eval(cb);
        }
    }
    this.registration.addButton('save', 'Save', 'M.ciniki_fatt_sapos.registration.save();');
    this.registration.addClose('Cancel');

    //
    // The search for saving seats
    //
    this.reserve = new M.panel('Registration', 'ciniki_fatt_sapos', 'reserve', 'mc', 'medium', 'sectioned', 'ciniki.fatt.sapos.reserve');
    this.reserve.sections = {
        'search':{'label':'Search Customers', 'type':'livesearchgrid', 'livesearchcols':1},
        'buttons':{'label':'', 'buttons':{
//            'add1':{'label':'New Customer', 'fn':'M.ciniki_fatt_sapos.reserve.addcustomer();'},
//            'add2':{'label':'New Employee', 'fn':'M.ciniki_fatt_sapos.reserve.addcustomer();'},
            'add3':{'label':'New Business', 'fn':'M.ciniki_fatt_sapos.reserve.addbusiness();'},
            }},
    }
    this.reserve.addcustomer = function() {
        M.startApp('ciniki.customers.edit',null,this.cb,'mc',{'next':'M.ciniki_fatt_sapos.saveSeats', 'customer_id':0, 'type':10});
    }
    this.reserve.addbusiness = function() {
        M.startApp('ciniki.customers.edit',null,this.cb,'mc',{'next':'M.ciniki_fatt_sapos.saveSeats', 'customer_id':0, 'type':30});
    }
    this.reserve.liveSearchCb = function(s, i, value) {
        if( s == 'search' && value != '' ) {
            // FIXME: Remove after change to IFB Accounts in customers
            if( M.modFlagOn('ciniki.customers', 0x0800) ) {
                M.api.getJSONBgCb('ciniki.customers.searchQuick', {'tnid':M.curTenantID, 'start_needle':encodeURIComponent(value), 'limit':'25', 'types':'10,20,30'}, 
                    function(rsp) { 
                        M.ciniki_fatt_sapos.reserve.liveSearchShow('search', null, M.gE(M.ciniki_fatt_sapos.reserve.panelUID + '_' + s), rsp.customers); 
                    });
            } else {
                M.api.getJSONBgCb('ciniki.customers.searchQuick', {'tnid':M.curTenantID, 'start_needle':encodeURIComponent(value), 'limit':'25'}, 
                    function(rsp) { 
                        M.ciniki_fatt_sapos.reserve.liveSearchShow('search', null, M.gE(M.ciniki_fatt_sapos.reserve.panelUID + '_' + s), rsp.customers); 
                    });
            }
            return true;
        }
    };
    this.reserve.liveSearchResultValue = function(s, f, i, j, d) {
        if( d.parent_name != null && d.parent_name != '' ) {
            return d.parent_name;
        } else {
            return d.display_name;
        }
        return '';
    }
    this.reserve.liveSearchResultRowFn = function(s, f, i, j, d) { 
        return 'M.ciniki_fatt_sapos.saveSeats(\'' + d.id + '\');'; 
    };
    this.reserve.open = function(cb, next) {
        this.nextFn = next;
        this.refresh();
        this.show(cb);
    }
    this.reserve.addClose('Cancel');

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
        // Setup the taxtypes available for the tenant
        //
        if( M.curTenant.modules['ciniki.taxes'] != null ) {
            this.registration.sections.details.fields.taxtype_id.active = 'yes';
            this.registration.sections.details.fields.taxtype_id.options = {'0':'No Taxes'};
            if( M.curTenant.modules != null && M.curTenant.modules['ciniki.taxes'] != null && M.curTenant.modules['ciniki.taxes'].settings.types != null ) {
                for(i in M.curTenant.taxes.settings.types) {
                    this.registration.sections.details.fields.taxtype_id.options[M.curTenant.taxes.settings.types[i].type.id] = M.curTenant.taxes.settings.types[i].type.name;
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
            this.registration.open(cb, args.item_object_id, args.source);
        } else if( args.registration_id != null ) {
            this.registration.open(cb, args.registration_id, args.source);
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
            this.reserve.open(cb, 'M.ciniki_fatt_sapos.saveSeats');
//            M.startApp('ciniki.customers.edit',null,cb,'mc',{'next':'M.ciniki_fatt_sapos.saveSeats', 'customer_id':0});
        } else {
            M.startApp('ciniki.customers.edit',null,cb,'mc',{'next':'M.ciniki_fatt_sapos.invoiceCheck', 'action':'choose', 'customer_id':0});
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
            {'tnid':M.curTenantID, 'offering_id':this.regadd.offering_id, 'customer_id':cid},
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
        if( ns == null || ns == 1 ) {
            args['regOpenFn'] = 'M.ciniki_fatt_sapos.regAdded';
        }
        M.startApp('ciniki.sapos.invoice',null,this.regadd.cb,'mc',args);
    }

    this.regAdded = function(invoice_id, object, object_id) {
        this.registration.open(this.regadd.cb, object_id, 'offering');
    }

    this.registrationSwitchCourse = function(oid) {
        M.api.getJSONCb('ciniki.fatt.offeringRegistrationSwitchOffering', {'tnid':M.curTenantID, 
            'registration_id':this.registration.registration_id, 'item_id':this.registration.data.item_id, 'offering_id':oid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_sapos.registration.close();
            });
    };

    this.registrationSwitchDate = function(oid) {
        M.api.getJSONCb('ciniki.fatt.offeringRegistrationSwitchOffering', {'tnid':M.curTenantID, 
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
            M.api.getJSONCb('ciniki.fatt.offeringRegistrationDelete', {'tnid':M.curTenantID, 'registration_id':this.registration.registration_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_sapos.registration.close();
            });
        }

    };

}
