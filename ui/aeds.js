//
// The panels to show offerings by year/month
//
function ciniki_fatt_aeds() {
	this.init = function() {
		//
		// The  panel
		//
		this.menu = new M.panel('AEDs',
			'ciniki_fatt_aeds', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.aeds.menu');
		this.menu.data = {};
		this.menu.sections = {	
			'tabs':{'label':'', 'type':'paneltabs', 'selected':'aeds', 'tabs':{
				'aeds':{'label':'Devices', 'fn':'M.ciniki_fatt_aeds.menuShow(null,"aeds");'},
				'owners':{'label':'Owners', 'fn':'M.ciniki_fatt_aeds.menuShow(null,"owners");'},
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
            }
		};
		this.menu.cellSortValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.display_name;
				case 1: return d.expiration_days;
			}
		};
        this.menu.rowClass = function(s, i, d) {
            return 'status' + d.alert_level;
        };
		this.menu.rowFn = function(s, i, d) {
            if( s == 'aeds' ) { 
                return 'M.ciniki_fatt_aeds.aedEdit(\'M.ciniki_fatt_aeds.menuShow();\',\'' + d.id + '\');';
            } 
            if( s == 'owners' ) {
                return 'M.ciniki_fatt_aeds.ownerShow(\'M.ciniki_fatt_aeds.menuShow();\',\'' + d.customer_id + '\');';
            }
		};
		this.menu.sectionData = function(s) { return this.data[s]; };
		this.menu.noData = function(s) { return this.sections[s].noData; };
		this.menu.addButton('add', 'Add', 'M.ciniki_fatt_aeds.aedAdd(\'M.ciniki_fatt_aeds.menuShow();\', 0);');
		this.menu.addClose('Back');

        //
        // The company/owner panel
        //
		this.owner = new M.panel('AEDs',
			'ciniki_fatt_aeds', 'owner',
			'mc', 'medium', 'sectioned', 'ciniki.fatt.aeds.owner');
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
			return 'M.ciniki_fatt_aeds.aedEdit(\'M.ciniki_fatt_aeds.ownerShow();\',\'' + d.id + '\');';
		};
		this.owner.sectionData = function(s) { return this.data[s]; };
		this.owner.noData = function(s) { return this.sections[s].noData; };
		this.owner.addButton('add', 'Add', 'M.ciniki_fatt_aeds.aedEdit(\'M.ciniki_fatt_aeds.ownerShow();\',0,M.ciniki_fatt_aeds.owner.customer_id);');
		this.owner.addClose('Back');


        //
        // The device panel
        //

        //
        // The edit device panel
        //
		this.edit = new M.panel('AED',
			'ciniki_fatt_aeds', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.aeds.edit');
		this.edit.aed_id = 0;
		this.edit.customer_id = 0;
		this.edit.data = {};
		this.edit.sections = {
            'customer_details':{'label':'Customer', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
                'cellClasses':['label', ''],
                'addTxt':'Edit',
                'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_aeds.edit.show();\',\'mc\',{\'next\':\'M.ciniki_fatt_aeds.aedEditUpdateCustomer\',\'customer_id\':M.ciniki_fatt_aeds.edit.data.customer_id});',
                'changeTxt':'Change customer',
                'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_fatt_aeds.edit.show();\',\'mc\',{\'next\':\'M.ciniki_fatt_aeds.aedEditUpdateCustomer\',\'customer_id\':0});',
                },
			'image':{'label':'', 'aside':'yes', 'fields':{
				'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
				}},
			'details':{'label':'', 'aside':'yes', 'fields':{
				'location':{'label':'Location', 'type':'text'},
				'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '40':'Out for service', '60':'Deleted'}},
//				'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}, '5':{'name':'Online Registrations'}}},
				'make':{'label':'Make', 'type':'text'},
				'model':{'label':'Model', 'type':'text'},
				'serial':{'label':'Serial', 'type':'text'},
				'flags1':{'label':'Options', 'type':'flagspiece', 'field':'flags', 'mask':0x3000, 'flags':{'13':{'name':'Wall Mount'}, '14':{'name':'Alarmed Cabinet'}}},
				}},
			'options':{'label':'Battery', 'fields':{
				'flags_1':{'label':'Secondary Battery', 'type':'flagtoggle', 'bit':0x01, 'field':'flags', 'default':'no', 'on_fields':['secondary_battery_expiration']},
				'flags_5':{'label':'Primary Adult Pads', 'type':'flagtoggle', 'bit':0x10, 'field':'flags', 'default':'yes', 'on_fields':['primary_adult_pads_expiration']},
				'flags_6':{'label':'Secondary Adult Pads', 'type':'flagtoggle', 'bit':0x20, 'field':'flags', 'default':'no', 'on_fields':['secondary_adult_pads_expiration']},
				'flags_9':{'label':'Primary Child Pads', 'type':'flagtoggle', 'bit':0x0100, 'field':'flags', 'default':'no', 'on_fields':['primary_child_pads_expiration']},
				'flags_10':{'label':'Secondary Child Pads', 'type':'flagtoggle', 'bit':0x0200, 'field':'flags', 'default':'no', 'on_fields':['secondary_child_pads_expiration']},
				}},
			'expirations':{'label':'Expiration Dates', 'fields':{
				'device_expiration':{'label':'Device Warranty', 'type':'date'},
				'primary_battery_expiration':{'label':'Primary Battery', 'type':'date'},
				'secondary_battery_expiration':{'label':'Secondary Battery', 'visible':function() {return (M.ciniki_fatt_aeds.edit.data.flags&0x01)>0?'yes':'no';}, 'type':'date'},
                }},
			'pads':{'label':'', 'fields':{
				'primary_adult_pads_expiration':{'label':'Primary Adult Pads', 'visible':function() {return (M.ciniki_fatt_aeds.edit.data.flags&0x10)>0?'yes':'no';}, 'type':'date'},
				'secondary_adult_pads_expiration':{'label':'Secondary Adult Pads', 'visible':function() {return (M.ciniki_fatt_aeds.edit.data.flags&0x20)>0?'yes':'no';}, 'type':'date'},
				'primary_child_pads_expiration':{'label':'Primary Child Pads', 'visible':function() {return (M.ciniki_fatt_aeds.edit.data.flags&0x0100)>0?'yes':'no';}, 'type':'date'},
				'secondary_child_pads_expiration':{'label':'Secondary Child Pads', 'visible':function() {return (M.ciniki_fatt_aeds.edit.data.flags&0x0200)>0?'yes':'no';}, 'type':'date'},
				}},
            '_notes':{'label':'Notes', 'fields':{
                'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
                }},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_fatt_aeds.aedSave();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_aeds.aedDelete(M.ciniki_fatt_aeds.edit.aed_id);'},
				}},
		};
		this.edit.sectionData = function(s) { return this.data[s]; }
		this.edit.fieldValue = function(s, i, d) {
			return this.data[i];
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.fatt.aedHistory', 'args':{'business_id':M.curBusinessID, 'aed_id':this.aed_id, 'field':i}};
		};
		this.edit.cellValue = function(s, i, j, d) {
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
        this.edit.rowFn = function(s, i, d) { return ''; }
		this.edit.addDropImage = function(iid) {
			M.ciniki_fatt_aeds.edit.setFieldValue('primary_image_id', iid, null, null);
			return true;
		};
		this.edit.deleteImage = function(fid) {
			this.setFieldValue(fid, 0, null, null);
			return true;
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_fatt_aeds.aedSave();');
		this.edit.addClose('Cancel');
	};

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
            this.ownerShow(cb, args.appointment_id.replace(/aedcustomer-/,''));
        } else {
            this.menuShow(cb, 'aeds');
        }
	};

	this.menuShow = function(cb, tab) {
        if( tab != null ) { this.menu.sections.tabs.selected = tab; }
        if( this.menu.sections.tabs.selected == 'aeds' ) {
            M.api.getJSONCb('ciniki.fatt.aedDeviceList', {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_aeds.menu;
                p.data = rsp;
                p.refresh();
                p.show(cb);
            });
       } else {
            M.api.getJSONCb('ciniki.fatt.aedOwnerList', {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_aeds.menu;
                p.data = rsp;
                p.refresh();
                p.show(cb);
            });
       }
	};

	this.aedShow = function(cb, aid) {
		if( aid != null ) { this.aed.aed_id = aid; }
		M.api.getJSONCb('ciniki.fatt.aedGet', {'business_id':M.curBusinessID, 'aed_id':this.aed.aed_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_aeds.aed;
            p.data = rsp.aed;
            p.refresh();
            p.show();
        });
	}

	this.ownerShow = function(cb, cid) {
		if( cid != null ) { this.owner.customer_id = cid; }
		M.api.getJSONCb('ciniki.fatt.aedDeviceList', {'business_id':M.curBusinessID, 'customer_id':this.owner.customer_id}, function(rsp) {
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

	//
	// AED Edit functions
	//
    this.aedAdd = function(cb, cid) {
        this.edit.cb = cb;
        this.edit.aed_id = 0;
        M.startApp('ciniki.customers.edit',null,cb,'mc',{'next':'M.ciniki_fatt_aeds.customerAEDEdit','customer_id':cid});
    };

    this.customerAEDEdit = function(cid) {
        this.aedEdit(null, null, cid);
    };

    this.aedEditUpdateCustomer = function(cid) {
        this.edit.customer_id = cid;
		M.api.getJSONCb('ciniki.fatt.aedGet', {'business_id':M.curBusinessID, 'aed_id':0, 'customer_id':this.edit.customer_id}, function(rsp) {
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

	this.aedEdit = function(cb, aid, cid) {
		if( aid != null ) { this.edit.aed_id = aid; }
        if( cid != null ) { this.edit.customer_id = cid; }
		this.edit.sections._buttons.buttons.delete.visible = (this.edit.aed_id>0?'yes':'no');
		M.api.getJSONCb('ciniki.fatt.aedGet', {'business_id':M.curBusinessID, 'aed_id':this.edit.aed_id, 'customer_id':this.edit.customer_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_aeds.edit;
            p.data = rsp.aed;
            p.customer_id = rsp.aed.customer_id;
            p.refresh();
            p.show(cb);
		});
	};

	this.aedSave = function() {
		if( this.edit.aed_id > 0 ) {
			var c = this.edit.serializeForm('no');
            if( this.edit.customer_id != this.edit.data.customer_id ) {
                c += '&customer_id=' + this.edit.customer_id;
            }
			if( c != '' ) {
				M.api.postJSONCb('ciniki.fatt.aedUpdate', {'business_id':M.curBusinessID, 'aed_id':this.edit.aed_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_fatt_aeds.edit.close();
                });
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
            c += '&customer_id=' + this.edit.data.customer_id;
			M.api.postJSONCb('ciniki.fatt.aedAdd', {'business_id':M.curBusinessID}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
                M.ciniki_fatt_aeds.edit.close();
			});
		}
	};

	this.aedDelete = function(aid) {
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
}
