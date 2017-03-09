//
// The panels to show offerings by year/month
//
function ciniki_fatt_aeds() {
    //
    // The  panel
    //
    this.menu = new M.panel('AEDs', 'ciniki_fatt_aeds', 'menu', 'mc', 'medium', 'sectioned', 'ciniki.fatt.aeds.menu');
    this.menu.data = {};
    this.menu.sections = {  
        'tabs':{'label':'', 'type':'paneltabs', 'selected':'aeds', 'tabs':{
            'aeds':{'label':'Devices', 'fn':'M.ciniki_fatt_aeds.menu.open(null,"aeds");'},
            'owners':{'label':'Owners', 'fn':'M.ciniki_fatt_aeds.menu.open(null,"owners");'},
            'expirations':{'label':'Expirations', 'fn':'M.ciniki_fatt_aeds.menu.open(null,"expirations");'},
            }},
        'aeds':{'label':'Devices', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_fatt_aeds.menu.sections.tabs.selected=='aeds'?'yes':'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'altnumber'],
            'headerValues':['Company/Device', 'Expirations'],
            'cellClasses':['multiline', 'multiline'],
            'noData':'No devices',
            },
        'owners':{'label':'Companies & Owners', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_fatt_aeds.menu.sections.tabs.selected=='owners'?'yes':'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'altnumber'],
            'headerValues':['Company/Owner', 'Expirations'],
            'cellClasses':['', ''],
            'noData':'No companies/owners',
            },
        'expirations':{'label':'Expirations', 'type':'simplegrid', 'num_cols':11,
            'visible':function() { return M.ciniki_fatt_aeds.menu.sections.tabs.selected=='expirations'?'yes':'no'; },
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'text', 'altnumber', 'altnumber', 'altnumber', 'altnumber', 'altnumber', 'altnumber', 'altnumber'],
            'headerValues':['Company', 'Make', 'Model', 'Serial', 'Device', 'Battery(A)', 'Battery(B)', 'Adult(A)', 'Adult(B)', 'Child(A)', 'Child(B)'],
            'cellClasses':['', '', '', '', 'nobreak multiline', 'nobreak multiline', 'nobreak multiline', 'nobreak multiline', 'nobreak multiline', 'nobreak multiline', 'nobreak multiline'],
            'noData':'No devices',
            },
    };
    this.menu.cellValue = function(s, i, j, d) {
        var lstr = '';
        if( d.location != '' ) { lstr += (lstr != '' ? ', ' : '') + d.location; }
        if( d.make != '' ) { lstr += (lstr != '' ? ', ' : '') + d.make; }
        if( d.model != '' ) { lstr += (lstr != '' ? ', ' : '') + d.model; }
        if( s == 'aeds' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.display_name + '</span><span class="subtext">' + lstr + '</span>';
                case 1: return '<span class="maintext">' + d.expiration_days_text + '</span><span class="subtext">' + d.expiring_pieces + '</span>';
            }
        } else if( s == 'owners' ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: return d.expiration_days_text;
            }
        } else if( s == 'expirations' ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: return d.make;
                case 2: return d.model;
                case 3: return d.serial;
                case 4: return '<span class="maintext">' + d.device_expiration_text + '</span>'
                    + '<span class="subtext">' + d.device_expiration_days_text + '</span>';
                case 5: return '<span class="maintext">' + d.primary_battery_expiration_text + '</span>'
                    + '<span class="subtext">' + d.primary_battery_expiration_days_text + '</span>';
                case 6: return '<span class="maintext">' + d.secondary_battery_expiration_text + '</span>'
                    + '<span class="subtext">' + d.secondary_battery_expiration_days_text + '</span>';
                case 7: return '<span class="maintext">' + d.primary_adult_pads_expiration_text + '</span>'
                    + '<span class="subtext">' + d.primary_adult_pads_expiration_days_text + '</span>';
                case 9: return '<span class="maintext">' + d.secondary_adult_pads_expiration_text + '</span>'
                    + '<span class="subtext">' + d.secondary_adult_pads_expiration_days_text + '</span>';
                case 9: return '<span class="maintext">' + d.primary_child_pads_expiration_text + '</span>'
                    + '<span class="subtext">' + d.primary_child_pads_expiration_days_text + '</span>';
                case 10: return '<span class="maintext">' + d.secondary_child_pads_expiration_text + '</span>'
                    + '<span class="subtext">' + d.secondary_child_pads_expiration_days_text + '</span>';
            }
        }
    };
    this.menu.cellSortValue = function(s, i, j, d) {
        if( s == 'aeds' || s == 'owners' ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: return d.expiration_days;
            }
        }
        if( s == 'expirations' ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: return d.make;
                case 2: return d.model;
                case 3: return d.serial;
                case 4: return d.device_expiration_days;
                case 5: return d.primary_battery_expiration_days;
                case 6: return d.secondary_battery_expiration_days;
                case 7: return d.primary_adult_pads_expiration;
                case 8: return d.secondary_adult_pads_expiration;
                case 9: return d.primary_child_pads_expiration;
                case 10: return d.secondary_child_pads_expiration;
            }
        }
    };
    this.menu.rowClass = function(s, i, d) {
        return 'status' + d.alert_level;
    };
    this.menu.rowFn = function(s, i, d) {
        if( s == 'aeds' || s == 'expirations' ) { 
            return 'M.ciniki_fatt_aeds.edit.open(\'M.ciniki_fatt_aeds.menu.open();\',\'' + d.id + '\');';
        } 
        if( s == 'owners' ) {
            return 'M.ciniki_fatt_aeds.owner.open(\'M.ciniki_fatt_aeds.menu.open();\',\'' + d.customer_id + '\');';
        }
    };
    this.menu.sectionData = function(s) { return this.data[s]; };
    this.menu.noData = function(s) { return this.sections[s].noData; };
    this.menu.open = function(cb, tab) {
        if( tab != null ) { this.sections.tabs.selected = tab; }
        if( this.sections.tabs.selected == 'aeds' ) {
            M.api.getJSONCb('ciniki.fatt.aedDeviceList', {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_aeds.menu;
                p.size = 'medium';
                p.data = rsp;
                p.refresh();
                p.show(cb);
            });
        } else if( this.sections.tabs.selected == 'owners' ) {
            M.api.getJSONCb('ciniki.fatt.aedOwnerList', {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_aeds.menu;
                p.size = 'medium';
                p.data = rsp;
                p.refresh();
                p.show(cb);
            });
        } else {
            M.api.getJSONCb('ciniki.fatt.aedDeviceList', {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_aeds.menu;
                p.size = 'full';
                p.data.expirations = rsp.aeds;
                p.refresh();
                p.show(cb);
            });
        }
    };
    this.menu.addButton('add', 'Add', 'M.ciniki_fatt_aeds.edit.addAED(\'M.ciniki_fatt_aeds.menu.open();\', 0);');
    this.menu.addButton('tools', 'Tools', 'M.ciniki_fatt_aeds.tools.open(\'M.ciniki_fatt_aeds.menu.open();\');');
    this.menu.addClose('Back');

    //
    // The company/owner panel
    //
    this.owner = new M.panel('AEDs', 'ciniki_fatt_aeds', 'owner', 'mc', 'medium', 'sectioned', 'ciniki.fatt.aeds.owner');
    this.owner.customer_id = 0;
    this.owner.data = {};
    this.owner.sections = { 
        'customer_details':{'label':'Customer', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            },
        'aeds':{'label':'Devices', 'type':'simplegrid', 'num_cols':2,
            'sortable':'yes',
            'sortTypes':['text', 'altnumber'],
            'headerValues':['Company/Device', 'Expirations'],
            'cellClasses':['multiline', 'multiline'],
            'noData':'No devices',
            },
    };
    this.owner.cellValue = function(s, i, j, d) {
        var lstr = '';
        if( d.location != '' ) { lstr += (lstr != '' ? ', ' : '') + d.location; }
        if( d.make != '' ) { lstr += (lstr != '' ? ', ' : '') + d.make; }
        if( d.model != '' ) { lstr += (lstr != '' ? ', ' : '') + d.model; }
        if( s == 'aeds' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + lstr + '</span><span class="subtext">' + d.serial + '</span>';
                case 1: return '<span class="maintext">' + d.expiration_days_text + '</span><span class="subtext">' + d.expiring_pieces + '</span>';
            }
        } else if( s == 'customer_details' && j == 0 ) { 
            return d.detail.label;
        } else if( s == 'customer_details' && j == 1 ) {
            if( d.detail.label == 'Email' ) {
                return M.linkEmail(d.detail.value);
            } else if( d.detail.label == 'Address' ) {
                return d.detail.value.replace(/\n/g, '<br/>');
            }
            return d.detail.value;
        }
    };
    this.owner.cellSortValue = function(s, i, j, d) {
        if( s == 'aeds' ) {
            switch(j) {
                case 0: return d.display_name;
                case 1: return d.expiration_days;
            }
        }
    };
    this.owner.rowClass = function(s, i, d) {
        return 'status' + d.alert_level;
    };
    this.owner.rowFn = function(s, i, d) {
        return 'M.ciniki_fatt_aeds.edit.open(\'M.ciniki_fatt_aeds.owner.open();\',\'' + d.id + '\');';
    };
    this.owner.sectionData = function(s) { return this.data[s]; };
    this.owner.noData = function(s) { return this.sections[s].noData; };
    this.owner.open = function(cb, cid) {
        if( cid != null ) { this.customer_id = cid; }
        M.api.getJSONCb('ciniki.fatt.aedDeviceList', {'business_id':M.curBusinessID, 'customer_id':this.customer_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_aeds.owner;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.owner.addButton('add', 'Add', 'M.ciniki_fatt_aeds.edit.open(\'M.ciniki_fatt_aeds.owner.open();\',0,M.ciniki_fatt_aeds.owner.customer_id);');
    this.owner.addClose('Back');

    //
    // The edit device panel
    //
    this.edit = new M.panel('AED', 'ciniki_fatt_aeds', 'edit', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.aeds.edit');
    this.edit.aed_id = 0;
    this.edit.customer_id = 0;
    this.edit.data = {};
    this.edit.sections = {
        'customer_details':{'label':'Customer', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_aeds.edit.show();\',\'mc\',{\'next\':\'M.ciniki_fatt_aeds.edit.updateCustomer\',\'customer_id\':M.ciniki_fatt_aeds.edit.data.customer_id});',
            'changeTxt':'Change customer',
            'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_aeds.edit.show();\',\'mc\',{\'next\':\'M.ciniki_fatt_aeds.edit.updateCustomer\',\'customer_id\':0});',
            },
        '_image':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_fatt_aeds.edit.setFieldValue('primary_image_id', iid, null, null);
                    return true;
                    },
                'addDropImageRefresh':'',
                'deleteImage':function(fid) {
                        M.ciniki_fatt_aeds.edit.setFieldValue(fid, 0, null, null);
                        return true;
                    },
                },
            }},
        'details':{'label':'', 'aside':'yes', 'fields':{
            'location':{'label':'Location', 'type':'text'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '40':'Out for service', '60':'Deleted'}},
//              'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}, '5':{'name':'Online Registrations'}}},
            'make':{'label':'Make', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'model':{'label':'Model', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'serial':{'label':'Serial', 'type':'text'},
            'flags1':{'label':'Options', 'type':'flagspiece', 'field':'flags', 'mask':0x3000, 'flags':{'13':{'name':'Wall Mount'}, '14':{'name':'Alarmed Cabinet'}}},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'expirations', 'tabs':{
            'expirations':{'label':'Expirations', 'fn':'M.ciniki_fatt_aeds.edit.switchTab("expirations");'},
            'images':{'label':'Images', 'fn':'M.ciniki_fatt_aeds.edit.switchTab("images");'},
            'notes':{'label':'Notes', 'fn':'M.ciniki_fatt_aeds.edit.switchTab("notes");'},
            }},
        'options':{'label':'Tracking Options', 
            'visible':function() { return M.ciniki_fatt_aeds.edit.sections._tabs.selected == 'expirations' ? 'yes' : 'hidden'; },
            'fields':{
                'flags_1':{'label':'Device Warranty', 'type':'flagtoggle', 'bit':0x01, 'field':'flags', 'default':'no', 'on_fields':['device_expiration']},
                'flags_3':{'label':'Secondary Battery', 'type':'flagtoggle', 'bit':0x04, 'field':'flags', 'default':'no', 'on_fields':['secondary_battery_expiration']},
                'flags_5':{'label':'Primary Adult Pads', 'type':'flagtoggle', 'bit':0x10, 'field':'flags', 'default':'yes', 'on_fields':['primary_adult_pads_expiration']},
                'flags_6':{'label':'Secondary Adult Pads', 'type':'flagtoggle', 'bit':0x20, 'field':'flags', 'default':'no', 'on_fields':['secondary_adult_pads_expiration']},
                'flags_9':{'label':'Primary Child Pads', 'type':'flagtoggle', 'bit':0x0100, 'field':'flags', 'default':'no', 'on_fields':['primary_child_pads_expiration']},
                'flags_10':{'label':'Secondary Child Pads', 'type':'flagtoggle', 'bit':0x0200, 'field':'flags', 'default':'no', 'on_fields':['secondary_child_pads_expiration']},
            }},
        'expirations':{'label':'Expiration Dates', 
            'visible':function() { return M.ciniki_fatt_aeds.edit.sections._tabs.selected == 'expirations' ? 'yes' : 'hidden'; },
            'fields':{
                'device_expiration':{'label':'Device Warranty', 'visible':'no', 'type':'date'},
                'primary_battery_expiration':{'label':'Primary Battery', 'type':'date'},
                'secondary_battery_expiration':{'label':'Secondary Battery', 'visible':'no', 'type':'date'},
            }},
        'pads':{'label':'', 
            'visible':function() { return M.ciniki_fatt_aeds.edit.sections._tabs.selected == 'expirations' ? 'yes' : 'hidden'; },
            'fields':{
                'primary_adult_pads_expiration':{'label':'Primary Adult Pads', 'visible':'no', 'type':'date'},
                'secondary_adult_pads_expiration':{'label':'Secondary Adult Pads', 'visible':'no', 'type':'date'},
                'primary_child_pads_expiration':{'label':'Primary Child Pads', 'visible':'no', 'type':'date'},
                'secondary_child_pads_expiration':{'label':'Secondary Child Pads', 'visible':'no', 'type':'date'},
            }},
        'images':{'label':'Additional Images', 'type':'simplethumbs',
            'visible':function() { return (M.ciniki_fatt_aeds.edit.sections._tabs.selected == 'images' ? 'yes':'hidden');},
            },
        '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return (M.ciniki_fatt_aeds.edit.sections._tabs.selected == 'images' ? 'yes':'hidden');},
            'addTxt':'Add Additional Image',
            'addFn':'M.ciniki_fatt_aeds.edit.save("M.ciniki_fatt_aeds.aedimage.open(\'M.ciniki_fatt_aeds.edit.refreshImages();\',0,M.ciniki_fatt_aeds.edit.aed_id);");',
            },
        'notes':{'label':'Notes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_fatt_aeds.edit.sections._tabs.selected == 'notes' ? 'yes' : 'hidden'; },
            'cellClasses':['multiline'],
            'addTxt':'Add Note',
            'addFn':'M.ciniki_fatt_aeds.edit.save(\'M.ciniki_fatt_aeds.aednote.open("M.ciniki_fatt_aeds.edit.updateNotes();",0,M.ciniki_fatt_aeds.edit.aed_id);\');',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_aeds.edit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_aeds.edit.remove(M.ciniki_fatt_aeds.edit.aed_id);'},
            }},
    };
    this.edit.sectionData = function(s) { return this.data[s]; }
    this.edit.fieldValue = function(s, i, d) {
        return this.data[i];
    };
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.aedHistory', 'args':{'business_id':M.curBusinessID, 'aed_id':this.aed_id, 'field':i}};
    };
    this.edit.liveSearchCb = function(s, i, value) {
        M.api.getJSONBgCb('ciniki.fatt.aedFieldSearch', {'business_id':M.curBusinessID, 'field':i, 'start_needle':value},
            function(rsp) {
                M.ciniki_fatt_aeds.edit.liveSearchShow(s, i, M.gE(M.ciniki_fatt_aeds.edit.panelUID + '_' + i), rsp.results);
            });
    }
    this.edit.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'make' ) { return d.make + '/' + d.model; }
        return d[f];
    }
    this.edit.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_fatt_aeds.edit.updateField(\'' + s + '\',\'' + f + '\',\'' + M.eU(d.make) + '\',\'' + M.eU(d.model) + '\');';
    }
    this.edit.updateField = function(s, f, make, model) {
        if( f == 'make' ) {
            this.setFieldValue('make', M.dU(make));
        }
        this.setFieldValue('model', M.dU(model));
        this.removeLiveSearch(s, f);
    }
    this.edit.cellValue = function(s, i, j, d) {
        if( s == 'notes' ) {
            return '<span class="maintext">' + d.note_date + '</span><span class="subtext">' + d.content + '</span>';
        }
        if( s == 'customer_details' && j == 0 ) { return d.detail.label; }
        if( s == 'customer_details' && j == 1 ) {
            if( d.detail.label == 'Email' ) {
                return M.linkEmail(d.detail.value);
            } else if( d.detail.label == 'Address' ) {
                return d.detail.value.replace(/\n/g, '<br/>');
            }
            return d.detail.value;
        }
    };
    this.edit.rowFn = function(s, i, d) { 
        if( s == 'notes' ) {
            return 'M.ciniki_fatt_aeds.aednote.open(\'M.ciniki_fatt_aeds.edit.updateNotes();\',\'' + d.id + '\',M.ciniki_fatt_aeds.edit.aed_id);';
        }
        return ''; 
    }
    this.edit.addDropImage = function(iid) {
        if( this.aed_id == 0 ) {
            var c = this.serializeForm('yes');
            c += '&customer_id=' + this.data.customer_id;
            M.api.postJSONCb('ciniki.fatt.aedAdd', {'business_id':M.curBusinessID, 'aed_id':this.aed_id, 'image_id':iid}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_fatt_aeds.edit.aed_id = rsp.id;
                    M.ciniki_fatt_aeds.edit.refreshImages();
                });
        } else {
            M.api.getJSONCb('ciniki.fatt.aedImageAdd', {'business_id':M.curBusinessID, 'image_id':iid, 'name':'', 'aed_id':this.aed_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_aeds.edit.refreshImages();
            });
        }
        return true;
    };
    this.edit.thumbFn = function(s, i, d) {
        return 'M.ciniki_fatt_aeds.aedimage.open(\'M.ciniki_fatt_aeds.edit.refreshImages();\',\'' + d.id + '\');';
    };
    this.edit.switchTab = function(tab) {
        var p = M.ciniki_fatt_aeds.edit;
        p.sections._tabs.selected = tab;
        p.refreshSection('_tabs');
        p.showHideSection('options');
        p.showHideSection('expirations');
        p.showHideSection('pads');
        p.showHideSection('images');
        p.showHideSection('_images');
        p.showHideSection('notes');
    };
    this.edit.addAED = function(cb, cid) {
        this.cb = cb;
        this.aed_id = 0;
        M.startApp('ciniki.customers.edit',null,cb,'mc',{'next':'M.ciniki_fatt_aeds.edit.newCustomer','customer_id':cid});
    };
    this.edit.newCustomer = function(cid) {
        this.open(null, null, cid);
    };
    this.edit.updateCustomer = function(cid) {
        this.customer_id = cid;
        M.api.getJSONCb('ciniki.fatt.aedGet', {'business_id':M.curBusinessID, 'aed_id':0, 'customer_id':this.customer_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_aeds.edit;
            p.data.customer_details = rsp.aed.customer_details;
            p.refreshSection('customer_details');
            p.show();
        });
    };
    this.edit.open = function(cb, aid, cid) {
        if( aid != null ) { this.aed_id = aid; }
        if( cid != null ) { this.customer_id = cid; }
        this.sections._buttons.buttons.delete.visible = (this.aed_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.fatt.aedGet', {'business_id':M.curBusinessID, 'aed_id':this.aed_id, 'customer_id':this.customer_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_aeds.edit;
            p.reset();
            p.data = rsp.aed;
            p.sections.expirations.fields.device_expiration.visible = ((p.data.flags&0x01) > 0 ? 'yes' : 'no');
            p.sections.expirations.fields.secondary_battery_expiration.visible = ((p.data.flags&0x04) > 0 ? 'yes' : 'no');
            p.sections.pads.fields.primary_adult_pads_expiration.visible = ((p.data.flags&0x10) > 0 ? 'yes' : 'no');
            p.sections.pads.fields.secondary_adult_pads_expiration.visible = ((p.data.flags&0x20) > 0 ? 'yes' : 'no');
            p.sections.pads.fields.primary_child_pads_expiration.visible = ((p.data.flags&0x0100) > 0 ? 'yes' : 'no');
            p.sections.pads.fields.secondary_child_pads_expiration.visible = ((p.data.flags&0x0200) > 0 ? 'yes' : 'no');
            p.customer_id = rsp.aed.customer_id;
            p.refresh();
            p.show(cb);
        });
    };
    this.edit.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_fatt_aeds.edit.close();'; }
        if( this.aed_id > 0 ) {
            var c = this.serializeForm('no');
            if( this.customer_id != this.data.customer_id ) {
                c += '&customer_id=' + this.customer_id;
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.fatt.aedUpdate', {'business_id':M.curBusinessID, 'aed_id':this.aed_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            c += '&customer_id=' + this.data.customer_id;
            M.api.postJSONCb('ciniki.fatt.aedAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                eval(cb);
            });
        }
    };
    this.edit.remove = function(aid) {
        if( confirm('Are you sure you want to remove this aed? All records will be lost, there is no recovering once deleted.') ) {
            M.api.getJSONCb('ciniki.fatt.aedDelete', {'business_id':M.curBusinessID, 'aed_id':aid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_aeds.aed.close();
            });
        }
    };
    this.edit.refreshImages = function() {
        if( M.ciniki_fatt_aeds.edit.aed_id > 0 ) {
            M.api.getJSONCb('ciniki.fatt.aedGet', {'business_id':M.curBusinessID, 'aed_id':this.aed_id, 'images':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_aeds.edit;
                p.data.images = rsp.aed.images;
                p.refreshSection('images');
                p.show();
            });
        }
    }
    this.edit.updateNotes = function() {
        M.api.getJSONCb('ciniki.fatt.aedGet', {'business_id':M.curBusinessID, 'aed_id':this.aed_id, 'notes':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_aeds.edit;
            p.data.notes = rsp.aed.notes;
            p.refreshSection('notes');
            p.show();
        });
    }
    this.edit.addButton('save', 'Save', 'M.ciniki_fatt_aeds.edit.save();');
    this.edit.addClose('Cancel');

    //
    // The panel to aed image edit form
    //
    this.aedimage = new M.panel('Edit Image', 'ciniki_fatt_aeds', 'aedimage', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.aeds.aedimage');
    this.aedimage.data = {};
    this.aedimage.aedimage_id = 0;
    this.aedimage.aed_id = 0;
    this.aedimage.sections = {
        '_image':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'info':{'label':'Information', 'type':'simpleform', 'fields':{
            'image_date':{'label':'Date', 'type':'date'},
            }},
        '_description':{'label':'Description', 'type':'simpleform', 'fields':{
            'description':{'label':'', 'type':'textarea', 'size':'medium', 'hidelabel':'yes'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_aeds.aedimage.save();'},
            'delete':{'label':'Delete', 'visible':function() {return M.ciniki_fatt_aeds.aedimage.aedimage_id > 0 ? 'yes' : 'no';}, 'fn':'M.ciniki_fatt_aeds.aedimage.remove();'},
            }},
    };
    this.aedimage.fieldValue = function(s, i, d) { 
        if( this.data[i] != null ) { return this.data[i]; } 
        return ''; 
    };
    this.aedimage.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.aedImageHistory', 'args':{'business_id':M.curBusinessID, 'aedimage_id':this.aedimage_id, 'field':i}};
    };
    this.aedimage.addDropImage = function(iid) {
        M.ciniki_fatt_aeds.aedimage.setFieldValue('image_id', iid, null, null);
        return true;
    };
    this.aedimage.open = function(cb, iid, aid) {
        if( iid != null ) { this.aedimage_id = iid; }
        if( aid != null ) { this.aed_id = aid; }
        this.reset();
        M.api.getJSONCb('ciniki.fatt.aedImageGet', {'business_id':M.curBusinessID, 'aedimage_id':this.aedimage_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_aeds.aedimage;
            p.data = rsp.aedimage;
            p.refresh();
            p.show(cb);
        });
    };
    this.aedimage.save = function() {
        if( this.aedimage_id > 0 ) {
            var c = this.serializeFormData('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.fatt.aedImageUpdate', {'business_id':M.curBusinessID, 
                    'aedimage_id':this.aedimage_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_fatt_aeds.aedimage.close();
                        }
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeFormData('yes');
            M.api.postJSONFormData('ciniki.fatt.aedImageAdd', {'business_id':M.curBusinessID, 'aed_id':this.aed_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_fatt_aeds.aedimage.aedimage_id = rsp.id;
                M.ciniki_fatt_aeds.aedimage.close();
            });
        }
    };
    this.aedimage.remove = function() {
        if( confirm('Are you sure you want to delete this image?') ) {
            M.api.getJSONCb('ciniki.fatt.aedImageDelete', {'business_id':M.curBusinessID, 
                'aedimage_id':this.aedimage_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_fatt_aeds.aedimage.close();
                });
        }
    };
    this.aedimage.addButton('save', 'Save', 'M.ciniki_fatt_aeds.aedimage.save();');
    this.aedimage.addClose('Cancel');

    //
    // The panel for editing notes
    //
    this.aednote = new M.panel('Note', 'ciniki_fatt_aeds', 'aednote', 'mc', 'medium', 'sectioned', 'ciniki.fatt.aeds.aednote');
    this.aednote.data = {};
    this.aednote.note_id = 0;
    this.aednote.aed_id = 0;
    this.aednote.sections = { 
        'general':{'label':'', 'aside':'yes', 'fields':{
            'note_date':{'label':'Date', 'type':'date'},
            }}, 
        '_content':{'label':'Note', 'aside':'yes', 'fields':{
            'content':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'large', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_aeds.aednote.save();'},
            'delete':{'label':'Delete', 'visible':function() {return M.ciniki_fatt_aeds.aednote.note_id>0?'yes':'no';}, 'fn':'M.ciniki_fatt_aeds.aednote.remove();'},
            }},
        };  
    this.aednote.fieldValue = function(s, i, d) { return this.data[i]; }
    this.aednote.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.aedNoteHistory', 'args':{'business_id':M.curBusinessID, 'note_id':this.note_id, 'field':i}};
    }
    this.aednote.open = function(cb, id, aid) {
        this.reset();
        if( id != null ) { this.note_id = id; }
        if( aid != null ) { this.aed_id = aid; }
        M.api.getJSONCb('ciniki.fatt.aedNoteGet', {'business_id':M.curBusinessID, 'note_id':this.note_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_aeds.aednote;
            p.data = rsp.note;
            p.refresh();
            p.show(cb);
        });
    }
    this.aednote.save = function() {
        if( this.note_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.fatt.aedNoteUpdate', {'business_id':M.curBusinessID, 'note_id':this.note_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_fatt_aeds.aednote.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.fatt.aedNoteAdd', {'business_id':M.curBusinessID, 'note_id':this.note_id, 'aed_id':this.aed_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_fatt_aeds.aednote.note_id = rsp.id;
                    M.ciniki_fatt_aeds.aednote.close();
                });
        }
    };
    this.aednote.remove = function() {
        if( confirm('Are you sure you want to remove this note?') ) {
            M.api.getJSONCb('ciniki.fatt.aedNoteDelete', {'business_id':M.curBusinessID, 'note_id':this.note_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_fatt_aeds.aednote.close();
            });
        }
    };
    this.aednote.addButton('save', 'Save', 'M.ciniki_fatt_aeds.aednote.save();');
    this.aednote.addClose('Cancel');

    //
    // The tools panel
    //
    this.tools = new M.panel('Tools', 'ciniki_fatt_aeds', 'tools', 'mc', 'narrow', 'sectioned', 'ciniki.fatt.aeds.tools');
    this.tools.sections = {  
        '_':{'label':'', 'list':{
            'excel':{'label':'Export AEDs to Excel', 'fn':'M.ciniki_fatt_aeds.tools.downloadExcel();'},
            'pdf':{'label':'AED PDF Report', 'fn':'M.ciniki_fatt_aeds.tools.downloadPDF();'},
            }},
    };
    this.tools.open = function(cb) {
        this.refresh();
        this.show(cb);
    }
    this.tools.downloadExcel = function() {
        M.api.openFile('ciniki.fatt.aedDeviceList', {'business_id':M.curBusinessID, 'output':'excel'});
    }
    this.tools.downloadPDF = function() {
        M.api.openFile('ciniki.fatt.aedDeviceList', {'business_id':M.curBusinessID, 'output':'pdf'});
    }
    this.tools.addClose('Back');

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_fatt_aeds', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        //
        // Decide what to show
        //
        if( args.appointment_id != null ) {
            this.owner.open(cb, args.appointment_id.replace(/aedcustomer-/,''));
        } else {
            this.menu.open(cb, 'aeds');
        }
    };
}
