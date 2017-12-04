//
// This file contains the UI panels to manage customer certs and their expirations
//
function ciniki_fatt_reports() {
    //
    // The menu panel
    //
    this.menu = new M.panel('Reports', 'ciniki_fatt_reports', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.fatt.certs.menu');
    this.menu.sections = {  
        '_a':{'label':'', 'type':'simplelist', 'list':{
            'expirations':{'label':'Certificate Expirations', 'fn':'M.ciniki_fatt_reports.expirations.open(\'M.ciniki_fatt_reports.menu.open();\',\'\',\'\');'},
            }},
        '_b':{'label':'', 'type':'simplelist', 'list':{
            'tenants':{'label':'Tenant Report', 'fn':'M.ciniki_fatt_reports.tenants.open(\'M.ciniki_fatt_reports.menu.open();\');'},
            }},
        '_c':{'label':'', 'type':'simplelist', 'list':{
            'tenants':{'label':'Attendance Report', 'fn':'M.ciniki_fatt_reports.attendance.open(\'M.ciniki_fatt_reports.menu.open();\');'},
            }},
        '_d':{'label':'', 'type':'simplelist', 'list':{
            'passes':{'label':'Passes Report', 'fn':'M.ciniki_fatt_reports.passes.open(\'M.ciniki_fatt_reports.menu.open();\');'},
            }},
        };
    this.menu.open = function(cb) {
        this.refresh();
        this.show(cb);
    }
    this.menu.addClose('Back');

    //
    // The cert expirations panel
    //
    this.expirations = new M.panel('Certication Expirations',
        'ciniki_fatt_reports', 'expirations',
        'mc', 'medium', 'sectioned', 'ciniki.fatt.certs.expirations');
    this.expirations.data = {};
    this.expirations.sections = {
        'stats':{'label':'', 'type':'simplegrid', 'num_cols':7,
            'headerValues':['', '30', '60', '90', '120', '150', '180'],
            'cellClasses':['', 'alignright', 'alignright', 'alignright', 'alignright', 'alignright', 'alignright'],
            'headerClasses':['', 'alignright', 'alignright', 'alignright', 'alignright', 'alignright', 'alignright'],
            },
        'certs':{'label':'Expirations', 'visible':'no', 'type':'simplegrid', 'num_cols':3,
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
        if( s == 'stats' ) {
            switch (j) {
                case 0: return d.group.name;
                case 1: return d.group.ag0;
                case 2: return d.group.ag1;
                case 3: return d.group.ag2;
                case 4: return d.group.ag3;
                case 5: return d.group.ag4;
                case 6: return d.group.ag5;
            }
        } else if( s == 'certs' ) {
            switch (j) {
                case 0: return '<span class="maintext">' + d.cert.name + '</span><span class="subtext">' + d.cert.date_received + '</span>';
                case 1: return d.cert.display_name;
                case 2: return '<span class="maintext">' + d.cert.expiry_text + '</span><span class="subtext">' + d.cert.date_expiry + '</span>';
            }
        }
    }
    this.expirations.cellFn = function(s, i, j, d) {
        if( s == 'stats' ) {
            switch (j) {
                case 0: return 'M.ciniki_fatt_reports.expirations.open(null,\'' + escape(d.group.name) + '\',\'\');';
                case 1: return 'M.ciniki_fatt_reports.expirations.open(null,\'' + escape(d.group.name) + '\',0);';
                case 2: return 'M.ciniki_fatt_reports.expirations.open(null,\'' + escape(d.group.name) + '\',1);';
                case 3: return 'M.ciniki_fatt_reports.expirations.open(null,\'' + escape(d.group.name) + '\',2);';
                case 4: return 'M.ciniki_fatt_reports.expirations.open(null,\'' + escape(d.group.name) + '\',3);';
                case 5: return 'M.ciniki_fatt_reports.expirations.open(null,\'' + escape(d.group.name) + '\',4);';
                case 6: return 'M.ciniki_fatt_reports.expirations.open(null,\'' + escape(d.group.name) + '\',5);';
            }
        } 
        return '';
    }
    this.expirations.cellSortValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.cert.name;
            case 1: return d.cert.display_name;
            case 2: return d.cert.days_till_expiry;
        }
    };
    this.expirations.rowFn = function(s, i, d) {
        if( s == 'stats' ) {
            return '';
        } else if( s == 'certs' ) {
            return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_fatt_reports.expirations.open();\',\'mc\',{\'customer_id\':\'' + d.cert.customer_id + '\'});';
        }
    }
    this.expirations.open = function(cb, grouping, timespan) {
        if( grouping != null ) { this.grouping = unescape(grouping); }
        if( timespan != null ) { this.timespan = timespan; }
        this.sections.certs.label = this.grouping;
        if( this.timespan == 0 || this.timespan != '' ) {
            if( this.timespan == 0 ) {
                this.sections.certs.label += ' in the next 0-30 days';
            } else {
                this.sections.certs.label += ' in the next ' + ((this.timespan*30)+1) + '-' + ((this.timespan+1)*30) + ' days';
            }
        }
        M.api.getJSONCb('ciniki.fatt.certCustomerExpirations', {'tnid':M.curTenantID, 'stats':'yes',
            'grouping':this.grouping, 'timespan':this.timespan}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_reports.expirations;
                p.data = rsp;
                p.sections.certs.visible = (rsp.certs!=null?'yes':'no');
                p.refresh();
                p.show(cb);
        });
    };
    this.expirations.addClose('Back');

    //
    // The cert customer edit panel
    //
    this.certcustomer = new M.panel('Customer Certification',
        'ciniki_fatt_reports', 'certcustomer',
        'mc', 'medium', 'sectioned', 'ciniki.fatt.certs.certcustomer');
    this.certcustomer.certcustomer_id = 0;
    this.certcustomer.data = {};
    this.certcustomer.sections = {
        'customer_details':{'label':'Customer', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_reports.certcustomerShow();\',\'mc\',{\'next\':\'M.ciniki_fatt_reports.certcustomer.updateCustomer\',\'customer_id\':M.ciniki_fatt_reports.certcustomer.data.customer_id});',
            },
        'cert':{'label':'', 'fields':{
            'cert_id':{'label':'Certification', 'type':'select'},
            'date_received':{'label':'Date', 'type':'date'},
            'flags':{'label':'Options', 'type':'flags', 'default':'1', 'flags':{}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_reports.certcustomer.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_reports.certcustomer.remove(M.ciniki_fatt_reports.certcustomer.certcustomer_id);'},
            }},
    };
    this.certcustomer.sectionData = function(s) { return this.data[s]; }
    this.certcustomer.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    };
    this.certcustomer.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.certCustomerHistory', 'args':{'tnid':M.curTenantID, 'certcustomer_id':this.certcustomer_id, 'field':i}};
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
        M.api.getJSONCb('ciniki.fatt.certCustomerGet', {'tnid':M.curTenantID, 'certcustomer_id':this.certcustomer_id, 
            'cert_id':this.cert_id, 'customer_id':this.customer_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_reports.certcustomer;
                p.data.customer_details = rsp.certcustomer.customer_details;
                p.refreshSection('customer_details');
                p.show();
        });
    }
    this.certcustomer.open = function(cb, ccid, cert_id, customer_id) {
        if( ccid != null ) { this.certcustomer_id = ccid; }
        if( cert_id != null ) { this.cert_id = cert_id; }
        if( customer_id != null ) { this.customer_id = customer_id; }
        this.sections._buttons.buttons.delete.visible = (this.certcustomer_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.fatt.certCustomerGet', {'tnid':M.curTenantID, 'certcustomer_id':this.certcustomer_id, 'cert_id':this.cert_id, 
            'customer_id':this.customer_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_reports.certcustomer;
                p.data = rsp.certcustomer;
                p.customer_id = rsp.certcustomer.customer_id;
                p.sections.customer_details.addTxt = (p.data.customer_id>0?'Edit Customer':'Select Customer');
                p.refresh();
                p.show(cb);
        });
    };

    this.certcustomer.save = function() {
        if( this.certcustomer_id > 0 ) {
            var c = this.serializeForm('no');
            if( this.customer_id != this.data.customer_id ) {
                c += '&customer_id=' + this.customer_id;
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.fatt.certCustomerUpdate', {'tnid':M.curTenantID, 'certcustomer_id':this.certcustomer_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_fatt_reports.certcustomer.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            c += '&customer_id=' + this.customer_id;
            M.api.postJSONCb('ciniki.fatt.certCustomerAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_reports.certcustomer.close();
            });
        }
    };
    this.certcustomer.remove = function(cid) {
        if( confirm('Are you sure you want to remove this customer certification?') ) {
            M.api.getJSONCb('ciniki.fatt.certCustomerDelete', {'tnid':M.curTenantID, 'certcustomer_id':cid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_reports.certcustomer.close();
            });
        }
    };
    this.certcustomer.addButton('save', 'Save', 'M.ciniki_fatt_reports.certcustomer.save();');
    this.certcustomer.addClose('Cancel');

    //
    // The panel to display the tenant list
    //
    this.tenants = new M.panel('Tenants',
        'ciniki_fatt_reports', 'tenants',
        'mc', 'medium', 'sectioned', 'ciniki.fatt.certs.tenants');
    this.tenants.data = {};
    this.tenants.sections = {
        'customers':{'label':'', 'type':'simplegrid', 'num_cols':1},
        };
    this.tenants.sectionData = function(s) { return this.data[s]; }
    this.tenants.cellValue = function(s, i, j, d) {
        if( s == 'customers' ) {
            switch (j) {
                case 0: return d.customer.display_name;
            }
        }
    }
    this.tenants.rowFn = function(s, i, d) {
        return 'M.ciniki_fatt_reports.tenantcerts.open(\'M.ciniki_fatt_reports.tenants.open();\',\'' + d.customer.id + '\');';
    }
    this.tenants.open = function(cb) {
        M.api.getJSONCb('ciniki.fatt.certTenantList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_reports.tenants;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.tenants.addClose('Back');

    //
    // The panel to list the tenant employee certifications
    //
    this.tenantcerts = new M.panel('Tenants Certifications',
        'ciniki_fatt_reports', 'tenantcerts',
        'mc', 'medium', 'sectioned', 'ciniki.fatt.certs.tenantcerts');
    this.tenantcerts.customer_id = 0;
    this.tenantcerts.data = {};
    this.tenantcerts.sections = {
        'customer_details':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label',''],
//              'addTxt':'Edit',
//              'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_sapos_invoice.showInvoice();\',\'mc\',{\'next\':\'M.ciniki_sapos_invoice.updateInvoiceCustomer\',\'customer_id\':M.ciniki_sapos_invoice.invoice.data.customer_id});',
//              'changeTxt':'Change customer',
//              'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_sapos_invoice.showInvoice();\',\'mc\',{\'next\':\'M.ciniki_sapos_invoice.updateInvoiceCustomer\',\'customer_id\':0});',
            },
        'certs':{'label':'Certifications', 'type':'simplegrid', 'num_cols':3,
            'headerValues':['Customer', 'Certification', 'Expiration'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'altnumber'],
            'cellClasses':['multiline', 'multiline', 'multiline'],
            'noData':'No certifications',
            },
        '_buttons':{'label':'', 'buttons':{
            'print':{'label':'Print Report', 'fn':'M.ciniki_fatt_reports.downloadTenantCerts();'},
            }},
        };
    this.tenantcerts.sectionData = function(s) { return this.data[s]; }
    this.tenantcerts.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch (j) {
                case 0: return d.detail.label;
                case 1: return (d.detail.label == 'Email'?M.linkEmail(d.detail.value):d.detail.value);
            }
        }
        else if( s == 'certs' ) {
            switch (j) {
                case 0: return d.cert.display_name;
                case 1: return '<span class="maintext">' + d.cert.name + '</span><span class="subtext">' + d.cert.date_received + '</span>';
                case 2: return '<span class="maintext">' + d.cert.expiry_text + '</span><span class="subtext">' + d.cert.date_expiry + '</span>';
            }
        }
    }
    this.tenantcerts.cellSortValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.cert.display_name;
            case 1: return d.cert.name;
            case 2: return d.cert.days_till_expiry;
        }
    };
    this.tenantcerts.rowFn = function(s, i, d) {
        if( s == 'certs' ) {
            return 'M.startApp(\'ciniki.customers.main\',null,\'M.ciniki_fatt_reports.tenantcerts.open();\',\'mc\',{\'customer_id\':\'' + d.cert.customer_id + '\'});';
        }
        return '';
    }
    this.tenantcerts.open = function(cb, bid) {
        if( bid != null ) { this.customer_id = bid; }
        M.api.getJSONCb('ciniki.fatt.certTenantExpirations', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_reports.tenantcerts;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.tenantcerts.addClose('Back');

    this.attendance = new M.panel('Attendance', 'ciniki_fatt_reports', 'attendance', 'mc', 'medium', 'sectioned', 'ciniki.fatt.certs.attendance');
    this.attendance.data = {};
    this.attendance.sections = {
        'months':{'label':'', 'type':'simplegrid', 'num_cols':6,
            'headerValues':['Month', 'Incomplete', 'Pass', 'Cancel', 'Noshow', 'Total'],
            'sortable':'yes',
            'sortTypes':['date', 'number', 'number', 'number', 'number', 'number'],
            'cellClasses':['', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter'],
            },
        };
    this.attendance.sectionData = function(s) { return this.data[s]; }
    this.attendance.cellValue = function(s, i, j, d) {
        switch (j) {
            case 0: return d.month_text;
            case 1: return d.num_incomplete;
            case 2: return d.num_pass;
            case 3: return d.num_cancel;
            case 4: return d.num_noshow;
            case 5: return d.num_total;
        }
    }
    this.attendance.open = function(cb) {
        M.api.getJSONCb('ciniki.fatt.reportAttendance', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_reports.attendance;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.attendance.addClose('Back');

    //
    // The passes report ui
    //
    this.passes = new M.panel('Passing Students', 'ciniki_fatt_reports', 'passes', 'mc', 'full', 'sectioned', 'ciniki.fatt.certs.passes');
    this.passes.data = {};
    this.passes.sections = {
        '_years':{'label':'', 'type':'menutabs', 'year':'all', 'selected':'_all', 'tabs':{
            }},
        'years':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return (M.ciniki_fatt_reports.passes.sections._years.selected == '_all' ? 'yes' : 'no'); },
            'sortable':'yes',
            },
        'months':{'label':'', 'type':'simplegrid', 'num_cols':14,
            'visible':function() { return (M.ciniki_fatt_reports.passes.sections._years.selected != '_all' ? 'yes' : 'no'); },
            'headerValues':['Course', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec', 'Total'],
            'sortable':'yes',
            'sortTypes':['text', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number', 'number'],
            'headerClasses':['', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 
                'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', ],
            'cellClasses':['', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 
                'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', ],
            'footerClasses':['', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 
                'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', 'aligncenter', ],
            },
        };
    this.passes.sectionData = function(s) { return this.data.courses; }
    this.passes.cellValue = function(s, i, j, d) {
        if( s == 'years' ) {
            if( j == 0 ) {
                return d.code;
            }
            if( j == (this.sections[s].num_cols - 1) ) {
                if( d.num_passes == 0 ) { return ''; }
                return d.num_passes;
            }
            var y = this.years[(j-1)];
            if( d.years != null && d.years[y] != null && d.years[y].num_passes != null ) {
                if( d.years[y].num_passes == 0 ) { return ''; }
                return d.years[y].num_passes;
            }
        }
        else if( s == 'months' ) {
            if( j == 0 ) {
                return d.code;
            }
            var y = this.sections._years.year;
            if( j == (this.sections[s].num_cols - 1) ) {
                if( d.years != null && d.years[y] != null && d.years[y].num_passes > 0 ) { 
                    return d.years[y].num_passes;
                }
            }
            var m = j-1;
            if( d.years != null && d.years[y] != null && d.years[y].months != null && d.years[y].months[j] != null && d.years[y].months[j].num_passes > 0 ) { 
                return d.years[y].months[j].num_passes;
            }
        }
        return '';
    }
    this.passes.footerValue = function(s, i, d) {
        if( s == 'years' ) {
            if( i == 0 ) { return ''; }
            if( i == (this.sections[s].num_cols - 1) ) {
                return this.data.num_passes;
            }
            var y = this.years[(i-1)];
            if( this.data.years[y] != null && this.data.years[y].num_passes != null ) {
                return this.data.years[y].num_passes;
            }
        } 
        else if( s == 'months' ) {
            var y = this.sections._years.year;
            if( i == 0 ) { return ''; }
            if( i == (this.sections[s].num_cols - 1) ) {
                return this.data.years[y].num_passes;
            }
            if( this.data.years[y] != null && this.data.years[y].months != null ) {
                if( this.data.years[y].months[i] > 0 ) {
                    return this.data.years[y].months[i];
                }
            }
        }
        return '';
    }
    this.passes.switchTab = function(t) {
        this.sections._years.selected = '_' + t;
        this.sections._years.year = t;
        this.refresh();
        this.show();
    }
    this.passes.open = function(cb) {
        M.api.getJSONCb('ciniki.fatt.reportPasses', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_reports.passes;
            p.data = rsp;
            p.sections._years.tabs = {'_all':{'label':'All', 'fn':'M.ciniki_fatt_reports.passes.switchTab("all");'}};
            p.sections.years.num_cols = 1;
            p.sections.years.headerValues = ['Course'];
            p.sections.years.headerClasses = [''];
            p.sections.years.sortTypes = ['text'];
            p.years = [];
            for(var i in rsp.years) {
                p.sections._years.tabs['_' + rsp.years[i].year] = {'label':rsp.years[i].year, 'fn':'M.ciniki_fatt_reports.passes.switchTab("' + rsp.years[i].year + '");'};
                p.sections.years.headerValues[p.sections.years.num_cols] = rsp.years[i].year;
                p.sections.years.sortTypes[p.sections.years.num_cols] = 'number';
                p.sections.years.headerClasses[p.sections.years.num_cols] = 'aligncenter';
                p.years.push(rsp.years[i].year);
                p.sections.years.num_cols++;
            }
            p.sections.years.headerValues[p.sections.years.num_cols] = 'Total';
            p.sections.years.sortTypes[p.sections.years.num_cols] = 'number';
            p.sections.years.headerClasses[p.sections.years.num_cols] = 'aligncenter';
            p.sections.years.num_cols++;
            p.sections.years.cellClasses = p.sections.years.headerClasses;
            p.sections.years.footerClasses = p.sections.years.headerClasses;

            p.refresh();
            p.show(cb);
        });
    };
    this.passes.addClose('Back');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_fatt_reports', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        // 
        // Setup certcustomer cert listing
        //
        this.certcustomer.sections.cert.fields.cert_id.options = {};
        if( M.curTenant.modules['ciniki.fatt'] != null
            && M.curTenant.modules['ciniki.fatt'].settings != null
            && M.curTenant.modules['ciniki.fatt'].settings.certs != null
            ) {
            var certs = {};
            for(i in M.curTenant.modules['ciniki.fatt'].settings.certs) {
                certs[M.curTenant.modules['ciniki.fatt'].settings.certs[i].cert.id] = M.curTenant.modules['ciniki.fatt'].settings.certs[i].cert.name;
            }
            this.certcustomer.sections.cert.fields.cert_id.options = certs;
        }

        //
        // Setup certcustomer flags
        //
        var flags = {};
        if( (M.curTenant.modules['ciniki.fatt'].flags&0x20) > 0 ) { 
            flags['1'] = {'name':'Expiry Reminders'};
            flags['2'] = {'name':'Emails Finished'};
        }
        this.certcustomer.sections.cert.fields.flags.flags = flags;

        //
        // Decide what to show
        //
        if( args.certcustomer_id != null ) {
            this.certcustomer.open(cb, args.certcustomer_id, args.cert_id, args.customer_id);
        } else {
            this.menu.open(cb);
        }
    }

    this.downloadTenantCerts = function() {
        M.api.openPDF('ciniki.fatt.certTenantExpirations', {'tnid':M.curTenantID, 'customer_id':this.tenantcerts.customer_id, 'output':'pdf'});
    };
}
