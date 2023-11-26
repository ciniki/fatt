//
// This file contains the UI panels to manage course information, instructors, certs, locations and messages
//
function ciniki_fatt_settings() {
    this.toggleOptions = {'no':'Hide', 'yes':'Display'};
    this.positionOptions = {'left':'Left', 'center':'Center', 'right':'Right', 'off':'Off'};

    //
    // The menu panel
    //
    this.menu = new M.panel('Settings', 'ciniki_fatt_settings', 'menu', 'mc', 'narrow', 'sectioned', 'ciniki.fatt.settings.menu');
    this.menu.sections = {  
        '_':{'label':'', 'list':{
            'courses':{'label':'Courses', 'visible':'no', 'fn':'M.ciniki_fatt_settings.courses.open(\'M.ciniki_fatt_settings.menu.open();\');'},
            'instructors':{'label':'Instructors', 'visible':'no', 'fn':'M.ciniki_fatt_settings.instructors.open(\'M.ciniki_fatt_settings.menu.open();\');'},
            'locations':{'label':'Locations', 'visible':'no', 'fn':'M.ciniki_fatt_settings.locations.open(\'M.ciniki_fatt_settings.menu.open();\');'},
            'certs':{'label':'Certifications', 'visible':'no', 'fn':'M.ciniki_fatt_settings.certs.open(\'M.ciniki_fatt_settings.menu.open();\');'},
            }},
        '_classes':{'label':'', 'list':{
            'documents':{'label':'Document Headers', 'visible':'yes', 'fn':'M.ciniki_fatt_settings.documents.open(\'M.ciniki_fatt_settings.menu.open();\');'},
            'welcomemsg':{'label':'Welcome Message', 'visible':'yes', 'fn':'M.ciniki_fatt_settings.welcomemsg.open(\'M.ciniki_fatt_settings.menu.open();\',\'welcomemsg\');'},
            }},
        '_aeds':{'label':'', 'list':{
            'aeds':{'label':'AEDs', 'visible':'no', 'fn':'M.ciniki_fatt_settings.aeds.open(\'M.ciniki_fatt_settings.menu.open();\');'},
            }},
    };
    this.menu.open = function(cb) {
        this.refresh();
        this.show(cb);
    }
    this.menu.addClose('Back');

    //
    // Courses
    //
    this.courses = new M.panel('Courses', 'ciniki_fatt_settings', 'courses', 'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.courses');
    this.courses.sections = {
        'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Category',
            'addFn':'M.ciniki_fatt_settings.category.open(\'M.ciniki_fatt_settings.courses.open();\',0);',
            },
        'bundles':{'label':'Bundles', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add Bundle',
            'addFn':'M.ciniki_fatt_settings.bundle.open(\'M.ciniki_fatt_settings.courses.open();\',0);',
            },
        'courses':{'label':'Courses', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Code', 'Name', 'Price', 'Status'],
            'cellClasses':['multiline', 'multiline', 'multiline', 'multiline'],
            'addTxt':'Add Course',
            'addFn':'M.ciniki_fatt_settings.course.open(\'M.ciniki_fatt_settings.courses.open();\',0);',
            },
    };
    this.courses.cellValue = function(s, i, j, d) {
        if( s == 'categories' ) {
            return d.category.name;
        } else if( s == 'bundles' ) {
            return d.bundle.name;
        } else {
            switch(j) {
                case 0: return '<span class="maintext">' + d.course.code + '</span><span class="subtext">' + (d.course.num_seats_per_instructor>0?d.course.num_seats_per_instructor + ' seats':'unlimited') + '</span>';
                case 1: return '<span class="maintext">' + d.course.name + '</span><span class="subtext">' + d.course.num_hours + ' hour' + (d.course.num_hours!=1?'s':'') + ' over ' + d.course.num_days + ' day' + (d.course.num_days!=1?'s':'') + '</span>';
                case 2: return '<span class="maintext">' + d.course.price + '</span><span class="subtext">' + d.course.taxtype_name + '</span>';
                case 3: return '<span class="maintext">' + d.course.status_text + '</span><span class="subtext">' + d.course.visible + '</span>';
            }
        }
    };
    this.courses.sectionData = function(s) { return this.data[s]; }
    this.courses.rowFn = function(s, i, d) {
        if( d == null ) { return ''; }
        if( s == 'categories' ) {
            return 'M.ciniki_fatt_settings.category.open(\'M.ciniki_fatt_settings.courses.open();\',\'' + d.category.id + '\');';
        } else if( s == 'bundles' ) {
            return 'M.ciniki_fatt_settings.bundle.open(\'M.ciniki_fatt_settings.courses.open();\',\'' + d.bundle.id + '\');';
        } else {
            return 'M.ciniki_fatt_settings.course.open(\'M.ciniki_fatt_settings.courses.open();\',\'' + d.course.id + '\');';
        }
    };
    this.courses.open = function(cb) {
        M.api.getJSONCb('ciniki.fatt.courseList', {'tnid':M.curTenantID, 'categories':'yes', 'bundles':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.courses;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.courses.addButton('add', 'Course', 'M.ciniki_fatt_settings.course.open(\'M.ciniki_fatt_settings.courses.open();\',0);');
    this.courses.addClose('Back');

    //
    // The course edit panel
    //
    this.course = new M.panel('Course', 'ciniki_fatt_settings', 'course', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.settings.course');
    this.course.course_id = 0;
    this.course.data = {};
    this.course.sections = {
        'image':{'label':'', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'details':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'code':{'label':'Code', 'type':'text', 'size':'small'},
            'sequence':{'label':'Sequence', 'type':'text', 'size':'small'},
            'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':{'10':'Active', '50':'Archived'}},
            'price':{'label':'Price', 'type':'text', 'size':'small'},
            'taxtype_id':{'label':'Tax', 'active':'no', 'type':'select', 'options':{}},
            'num_days':{'label':'Days', 'type':'toggle', 'default':'1', 'toggles':{'1':'1', '2':'2'}},
            'num_hours':{'label':'Hours', 'type':'text', 'size':'small'},
            'num_seats_per_instructor':{'label':'Seats/Instructor', 'type':'text', 'size':'tiny'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
            'cover_letter':{'label':'Cover Letter', 'type':'select', 'options':{}, 'complex_options':{'value':'id', 'name':'name'}},
            'cert_form1':{'label':'Form 1', 'type':'select', 'options':{}, 'complex_options':{'value':'id', 'name':'name'}},
            'cert_form2':{'label':'Form 2', 'type':'select', 'options':{}, 'complex_options':{'value':'id', 'name':'name'}},
            }},
        '_categories':{'label':'Categories', 'aside':'yes', 'active':'no', 'fields':{
            'categories':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'itemname':'item', 'list':{}},
            }},
        '_bundles':{'label':'Bundles', 'aside':'yes', 'active':'no', 'fields':{
            'bundles':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'itemname':'item', 'list':{}},
            }},
        '_certs':{'label':'Certifications', 'aside':'yes', 'active':'no', 'fields':{
            'certs':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'itemname':'item', 'list':{}},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        'messages':{'label':'Messages', 'active':'no', 'type':'simplegrid', 'num_cols':2,
            'addTxt':'New Reminder',
            'addFn':'M.ciniki_fatt_settings.course.save(\'M.ciniki_fatt_settings.message.open("M.ciniki_fatt_settings.course.messagesUpdate();","ciniki.fatt.course",M.ciniki_fatt_settings.course.course_id,0);\');',
            },
        '_welcome_msg':{'label':'Welcome Message Course Details', 'fields':{
            'welcome_msg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.course.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.course.remove(M.ciniki_fatt_settings.course.course_id);'},
            }},
    };
    this.course.sectionData = function(s) { return this.data[s]; }
    this.course.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    };
    this.course.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.courseHistory', 'args':{'tnid':M.curTenantID, 'course_id':this.course_id, 'field':i}};
    }
    this.course.messagesUpdate = function() {
        M.api.getJSONCb('ciniki.fatt.courseGet', {'tnid':M.curTenantID, 'course_id':this.course_id, 'messages':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.course;
            p.data.messages = rsp.course.messages;
            p.refreshSection('messages');
            p.show();
        });
    }
    this.course.cellValue = function(s, i, j, d) {
        if( j == 0 ) {
            if( d.message.days < 0 ) {
                return Math.abs(d.message.days) + ' days before expiry';
            } else if( d.message.days == 0 ) {
                return 'on expiry day';
            } else if( d.message.days > 0 ) {
                return Math.abs(d.message.days) + ' days after expiry';
            }
        } else if( j == 1 ) {
            return d.message.subject;
        }
    }
    this.course.rowFn = function(s, i, d) {
        if( d == null ) { return ''; }
        return 'M.ciniki_fatt_settings.course.save(\'M.ciniki_fatt_settings.message.open("M.ciniki_fatt_settings.course.messagesUpdate();","ciniki.fatt.course",M.ciniki_fatt_settings.course.course_id,"' + d.message.id + '");\');';
    }
    this.course.addDropImage = function(iid) {
        M.ciniki_fatt_settings.course.setFieldValue('primary_image_id', iid, null, null);
        return true;
    };
    this.course.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.course.open = function(cb, cid) {
        if( cid != null ) { this.course_id = cid; }
        this.sections._buttons.buttons.delete.visible = (this.course_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.fatt.courseGet', {'tnid':M.curTenantID, 'course_id':this.course_id, 'messages':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.course;
            p.data = rsp.course;
            p.sections._categories.fields.categories.list = (rsp.categories!=null?rsp.categories:{});
            p.sections._bundles.fields.bundles.list = (rsp.bundles!=null?rsp.bundles:{});
            p.sections._certs.fields.certs.list = (rsp.certs!=null?rsp.certs:{});
            rsp.cover_letters.unshift({'value':'', 'name':'None'});
            p.sections.details.fields.cover_letter.options = rsp.cover_letters;
            rsp.forms.unshift({'value':'', 'name':'None'});
            p.sections.details.fields.cert_form1.options = rsp.forms;
            p.sections.details.fields.cert_form2.options = rsp.forms;
            p.refresh();
            p.show(cb);
        });
    };
    this.course.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_fatt_settings.course.close();'; }
        if( this.course_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.fatt.courseUpdate', {'tnid':M.curTenantID, 'course_id':this.course_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.fatt.courseAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                eval(cb);
            });
        }
    };
    this.course.remove = function(lid) {
        M.confirm('Are you sure you want to remove this course?',null,function() {
            M.api.getJSONCb('ciniki.fatt.courseDelete', {'tnid':M.curTenantID, 'course_id':lid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.course.close();
            });
        });
    };
    this.course.addButton('save', 'Save', 'M.ciniki_fatt_settings.course.save();');
    this.course.addClose('Cancel');

    //
    // The category edit panel 
    //
    this.category = new M.panel('Course Category', 'ciniki_fatt_settings', 'category', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.settings.category');
    this.category.category_id = 0;
    this.category.data = {};
    this.category.sections = {
        'image':{'label':'', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'details':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'sequence':{'label':'Sequence', 'type':'text', 'size':'tiny'},
            }},
        // Future synopsis if require 
//          '_synopsis':{'label':'Synopsis', 'fields':{
//              'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
//              }},
        '_courses':{'label':'Courses', 'active':'yes', 'fields':{
            'courses':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'itemname':'item', 'list':{}},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.category.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.category.remove(M.ciniki_fatt_settings.category.category_id);'},
            }},
    };
    this.category.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    };
    this.category.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.categoryHistory', 'args':{'tnid':M.curTenantID, 'category_id':this.category_id, 'field':i}};
    }
    this.category.addDropImage = function(iid) {
        M.ciniki_fatt_settings.category.setFieldValue('primary_image_id', iid, null, null);
        return true;
    };
    this.category.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.category.open = function(cb, cid) {
        if( cid != null ) { this.category_id = cid; }
        this.sections._buttons.buttons.delete.visible = (this.category_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.fatt.categoryGet', {'tnid':M.curTenantID, 
            'category_id':this.category_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_settings.category;
                p.data = rsp.category;
                p.sections._courses.fields.courses.list = (rsp.courses!=null?rsp.courses:{});
                p.refresh();
                p.show(cb);
        });
    };
    this.category.save = function() {
        if( this.category_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.fatt.categoryUpdate', {'tnid':M.curTenantID,
                    'category_id':this.category_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_fatt_settings.category.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.fatt.categoryAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.category.close();
            });
        }
    };
    this.category.remove = function(lid) {
        M.confirm('Are you sure you want to remove this category?',null,function() {
            M.api.getJSONCb('ciniki.fatt.categoryDelete', {'tnid':M.curTenantID, 'category_id':lid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.category.close();
            });
        });
    };
    this.category.addButton('save', 'Save', 'M.ciniki_fatt_settings.category.save();');
    this.category.addClose('Cancel');

    //
    // The bundle edit panel 
    //
    this.bundle = new M.panel('Course Bundle', 'ciniki_fatt_settings', 'bundle', 'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.bundle');
    this.bundle.bundle_id = 0;
    this.bundle.data = {};
    this.bundle.sections = {
        'details':{'label':'', 'aside':'no', 'fields':{
            'name':{'label':'Name', 'type':'text'},
//              'sequence':{'label':'Sequence', 'type':'text', 'size':'tiny'},
            }},
        '_courses':{'label':'Courses', 'active':'yes', 'fields':{
            'courses':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'itemname':'item', 'list':{}},
            }},
        // Future synopsis if require 
//          '_synopsis':{'label':'Synopsis', 'fields':{
//              'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
//              }},
//          '_description':{'label':'Description', 'fields':{
//              'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
//              }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.bundle.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.bundle.remove(M.ciniki_fatt_settings.bundle.bundle_id);'},
            }},
    };
    this.bundle.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    };
    this.bundle.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.bundleHistory', 'args':{'tnid':M.curTenantID, 'bundle_id':this.bundle_id, 'field':i}};
    }
    this.bundle.addDropImage = function(iid) {
        M.ciniki_fatt_settings.bundle.setFieldValue('primary_image_id', iid, null, null);
        return true;
    };
    this.bundle.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.bundle.open = function(cb, cid) {
        if( cid != null ) { this.bundle_id = cid; }
        this.sections._buttons.buttons.delete.visible = (this.bundle_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.fatt.bundleGet', {'tnid':M.curTenantID, 
            'bundle_id':this.bundle_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_settings.bundle;
                p.data = rsp.bundle;
                p.sections._courses.fields.courses.list = (rsp.courses!=null?rsp.courses:{});
                p.refresh();
                p.show(cb);
        });
    };
    this.bundle.save = function() {
        if( this.bundle_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.fatt.bundleUpdate', {'tnid':M.curTenantID,
                    'bundle_id':this.bundle_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_fatt_settings.bundle.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.fatt.bundleAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.bundle.close();
            });
        }
    };
    this.bundle.remove = function(lid) {
        M.confirm('Are you sure you want to remove this bundle?',null,function() {
            M.api.getJSONCb('ciniki.fatt.bundleDelete', {'tnid':M.curTenantID, 'bundle_id':lid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.bundle.close();
            });
        });
    };
    this.bundle.addButton('save', 'Save', 'M.ciniki_fatt_settings.bundle.save();');
    this.bundle.addClose('Cancel');

    //
    // Instructors
    //
    this.instructors = new M.panel('Instructors', 'ciniki_fatt_settings', 'instructors', 'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.instructors');
    this.instructors.sections = {
        'instructors':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'headerValues':['Name', 'Status'],
            'cellClasses':['multiline', ''],
            'addTxt':'Add Instructor',
            'addFn':'M.ciniki_fatt_settings.instructor.open(\'M.ciniki_fatt_settings.instructors.open();\',0);',
            },
    };
    this.instructors.sectionData = function(s) { return this.data[s]; }
    this.instructors.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return '<span class="maintext">' + d.instructor.name + (d.instructor.initials!=''?' ['+d.instructor.initials+']':'') + (d.instructor.id_number!=''?' <span class="subdue">'+d.instructor.id_number+'</span>':'') + '</span><span class="subtext">' + d.instructor.email + (d.instructor.email!=''&&d.instructor.phone!=''?' - ':'') + d.instructor.phone + '</span>';
            case 1: return d.instructor.status_text;
        }
    };
    this.instructors.rowFn = function(s, i, d) {
        if( d == null ) { return ''; }
        return 'M.ciniki_fatt_settings.instructor.open(\'M.ciniki_fatt_settings.instructors.open();\',\'' + d.instructor.id + '\');';
    };
    this.instructors.open = function(cb) {
        M.api.getJSONCb('ciniki.fatt.instructorList', {'tnid':M.curTenantID, 'instructor_id':this.instructor_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.instructors;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.instructors.addButton('add', 'Add', 'M.ciniki_fatt_settings.instructor.open(\'M.ciniki_fatt_settings.instructors.open();\',0);');
    this.instructors.addClose('Back');

    //
    // The instructor edit panel
    //
    this.instructor = new M.panel('Instructor', 'ciniki_fatt_settings', 'instructor', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.settings.instructor');
    this.instructor.instructor_id = 0;
    this.instructor.data = {};
    this.instructor.sections = {
        'image':{'label':'', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'details':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'initials':{'label':'Initials', 'type':'text', 'size':'small'},
            'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':{'10':'Active', '50':'Archived'}},
            'id_number':{'label':'ID #', 'type':'text'},
            'email':{'label':'Email', 'type':'text'},
            'phone':{'label':'Phone', 'type':'text'},
            'url':{'label':'Website', 'type':'text'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_bio':{'label':'Biography', 'fields':{
            'bio':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.instructor.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.instructor.remove(M.ciniki_fatt_settings.instructor.instructor_id);'},
            }},
    };
    this.instructor.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    };
    this.instructor.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.instructorHistory', 'args':{'tnid':M.curTenantID, 'instructor_id':this.instructor_id, 'field':i}};
    }
    this.instructor.addDropImage = function(iid) {
        M.ciniki_fatt_settings.instructor.setFieldValue('primary_image_id', iid, null, null);
        return true;
    };
    this.instructor.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.instructor.open = function(cb, iid) {
        if( iid != null ) { this.instructor_id = iid; }
        this.sections._buttons.buttons.delete.visible = (this.instructor_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.fatt.instructorGet', {'tnid':M.curTenantID, 
            'instructor_id':this.instructor_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_settings.instructor;
                p.data = rsp.instructor;
                p.refresh();
                p.show(cb);
        });
    };
    this.instructor.save = function() {
        if( this.instructor_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.fatt.instructorUpdate', {'tnid':M.curTenantID,
                    'instructor_id':this.instructor_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_fatt_settings.instructor.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.fatt.instructorAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.instructor.close();
            });
        }
    };
    this.instructor.remove = function(lid) {
        M.confirm('Are you sure you want to remove this instructor?',null,function() {
            M.api.getJSONCb('ciniki.fatt.instructorDelete', {'tnid':M.curTenantID, 'instructor_id':lid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.instructor.close();
            });
        });
    };
    this.instructor.addButton('save', 'Save', 'M.ciniki_fatt_settings.instructor.save();');
    this.instructor.addClose('Cancel');

    //
    // Locations
    //
    this.locations = new M.panel('Locations', 'ciniki_fatt_settings', 'locations', 'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.locations');
    this.locations.sections = {
        'locations':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'headerValues':['Name', 'Status'],
            'cellClasses':['', ''],
            'addTxt':'Add Location',
            'addFn':'M.ciniki_fatt_settings.location.open(\'M.ciniki_fatt_settings.locations.open();\',0);',
            },
    };
    this.locations.sectionData = function(s) { return this.data[s]; }
    this.locations.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return (d.location.code!=''?d.location.code+' - ':'') + d.location.name;
            case 1: return d.location.status_text;
        }
    };
    this.locations.rowFn = function(s, i, d) {
        if( d == null ) { return ''; }
        return 'M.ciniki_fatt_settings.location.open(\'M.ciniki_fatt_settings.locations.open();\',\'' + d.location.id + '\');';
    };
    this.locations.open = function(cb) {
        M.api.getJSONCb('ciniki.fatt.locationList', {'tnid':M.curTenantID, 'location_id':this.location_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.locations;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.locations.addButton('add', 'Add', 'M.ciniki_fatt_settings.location.open(\'M.ciniki_fatt_settings.locations.open();\',0);');
    this.locations.addClose('Back');

    //
    // The location edit panel 
    //
    this.location = new M.panel('Location', 'ciniki_fatt_settings', 'location', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.settings.location');
    this.location.location_id = 0;
    this.location.data = {};
    this.location.sections = {
        'details':{'label':'', 'aside':'yes', 'fields':{
            'code':{'label':'Code', 'type':'text', 'size':'small'},
            'name':{'label':'Name', 'type':'text'},
            'url':{'label':'Website', 'type':'text'},
            'num_seats':{'label':'Seats', 'type':'text', 'size':'small'},
            'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':{'10':'Active', '50':'Archived'}},
            'colour':{'label':'Colour', 'type':'colour'},
            }},
        '_address_flags':{'label':'', 'aside':'yes', 'fields':{
            'flags_1':{'label':'Address', 'type':'flagtoggle', 'bit':0x01, 'field':'flags', 
                'default':'off', 'off':'Fixed Location', 'on':'Variable Location', 
                'off_sections':['address', '_map', '_map_buttons']},
            }},
        'address':{'label':'', 'aside':'yes', 'visible':'hidden', 'fields':{
            'address1':{'label':'Street', 'type':'text'},
            'address2':{'label':'', 'type':'text'},
            'city':{'label':'City', 'type':'text', 'size':'medium'},
            'province':{'label':'Province', 'type':'text', 'size':'small'},
            'postal':{'label':'Postal', 'type':'text', 'size':'small'},
            }},
        '_map':{'label':'Location Map', 'aside':'yes', 'visible':'hidden', 'fields':{
            'latitude':{'label':'Latitude', 'type':'text', 'size':'small'},
            'longitude':{'label':'Longitude', 'type':'text', 'size':'small'},
            }},
        '_map_buttons':{'label':'', 'aside':'yes', 'visible':'hidden', 'buttons':{
            '_latlong':{'label':'Lookup Lat/Long', 'fn':'M.ciniki_fatt_settings.location.lookupLatLong();'},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.location.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.location.remove(M.ciniki_fatt_settings.location.location_id);'},
            }},
    };
    this.location.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    };
    this.location.lookupLatLong = function() {
        M.startLoad();
        if( document.getElementById('googlemaps_js') == null) {
            var script = document.createElement("script");
            script.id = 'googlemaps_js';
            script.type = "text/javascript";
            script.src = "https://maps.googleapis.com/maps/api/js?key=" + M.curTenant.settings['googlemapsapikey'] + "&sensor=false&callback=M.ciniki_fatt_settings.location.lookupGoogleLatLong";
            document.body.appendChild(script);
        } else {
            this.lookupGoogleLatLong();
        }
    };
    this.location.lookupGoogleLatLong = function() {
        var address = this.formValue('address1') + ', ' + this.formValue('address2') + ', ' + this.formValue('city') + ', ' + this.formValue('province');
        var geocoder = new google.maps.Geocoder();
        geocoder.geocode( { 'address': address}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                M.ciniki_fatt_settings.location.setFieldValue('latitude', results[0].geometry.location.lat());
                M.ciniki_fatt_settings.location.setFieldValue('longitude', results[0].geometry.location.lng());
                M.stopLoad();
            } else {
                M.alert('We were unable to lookup your latitude/longitude, please check your address in Settings: ' + status);
                M.stopLoad();
            }
        }); 
    };
    this.location.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.locationHistory', 'args':{'tnid':M.curTenantID, 'location_id':this.location_id, 'field':i}};
    }
    this.location.open = function(cb, lid) {
        if( lid != null ) { this.location_id = lid; }
        this.sections._buttons.buttons.delete.visible = (this.location_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.fatt.locationGet', {'tnid':M.curTenantID, 'location_id':this.location_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.location;
            p.data = rsp.location;
            p.refresh();
            p.show(cb);
        });
    };
    this.location.save = function() {
        if( this.location_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.fatt.locationUpdate', {'tnid':M.curTenantID, 'location_id':this.location_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_fatt_settings.location.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.fatt.locationAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.location.close();
            });
        }
    };
    this.location.remove = function(lid) {
        M.confirm('Are you sure you want to remove this location?',null,function() {
            M.api.getJSONCb('ciniki.fatt.locationDelete', {'tnid':M.curTenantID, 'location_id':lid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.location.close();
            });
        });
    };
    this.location.addButton('save', 'Save', 'M.ciniki_fatt_settings.location.save();');
    this.location.addClose('Cancel');

    //
    // Certs
    //
    this.certs = new M.panel('Certifications', 'ciniki_fatt_settings', 'certs', 'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.certs');
    this.certs.sections = {
        'certs':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'headerValues':['Grouping', 'Name', 'Status'],
            'cellClasses':['', ''],
            'addTxt':'Add Certification',
            'addFn':'M.ciniki_fatt_settings.cert.open(\'M.ciniki_fatt_settings.certs.open();\',0);',
            },
    };
    this.certs.sectionData = function(s) { return this.data[s]; }
    this.certs.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.cert.grouping;
            case 1: return d.cert.name;
            case 2: return d.cert.status_text;
        }
    };
    this.certs.rowFn = function(s, i, d) {
        if( d == null ) { return ''; }
        return 'M.ciniki_fatt_settings.cert.open(\'M.ciniki_fatt_settings.certs.open();\',\'' + d.cert.id + '\');';
    };
    this.certs.open = function(cb) {
        M.api.getJSONCb('ciniki.fatt.certList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.certs;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    };
    this.certs.addButton('add', 'Add', 'M.ciniki_fatt_settings.cert.open(\'M.ciniki_fatt_settings.certs.open();\',0);');
    this.certs.addClose('Back');

    //
    // The cert edit panel
    //
    this.cert = new M.panel('Certification', 'ciniki_fatt_settings', 'cert', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.settings.cert');
    this.cert.cert_id = 0;
    this.cert.data = {};
    this.cert.sections = {
        'details':{'label':'Certification', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'grouping':{'label':'Grouping', 'type':'text', 'size':'small'},
            'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':{'10':'Active', '50':'Archived'}},
            'years_valid':{'label':'Valid For', 'type':'text', 'size':'small'},
            'alt_cert_id':{'label':'Alternate', 'type':'select', 'complex_options':{'name':'name', 'value':'id'}, 'options':{}},
            }},
        '_courses':{'label':'Courses', 'aside':'yes', 'active':'yes', 'fields':{
            'courses':{'label':'', 'hidelabel':'yes', 'type':'idlist', 'itemname':'item', 'list':{}},
            }},
        'messages':{'label':'Messages', 'active':'no', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['multiline', 'multiline'],
            'addTxt':'New Reminder',
//            'addFn':'M.ciniki_fatt_settings.cert.message.open(0)',
            'addFn':'M.ciniki_fatt_settings.cert.save(\'M.ciniki_fatt_settings.message.open("M.ciniki_fatt_settings.cert.messagesUpdate();","ciniki.fatt.cert",M.ciniki_fatt_settings.cert.cert_id,0);\');',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.cert.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.cert.remove(M.ciniki_fatt_settings.cert.cert_id);'},
            }},
    };
    this.cert.sectionData = function(s) { return this.data[s]; }
    this.cert.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    };
    this.cert.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.certHistory', 'args':{'tnid':M.curTenantID, 'cert_id':this.cert_id, 'field':i}};
    }
    this.cert.messagesUpdate = function() {
        M.api.getJSONCb('ciniki.fatt.certGet', {'tnid':M.curTenantID, 'cert_id':this.cert_id, 'messages':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.cert;
            p.data.messages = rsp.cert.messages;
            p.refreshSection('messages');
            p.show();
        });
    }
    this.cert.cellValue = function(s, i, j, d) {
        if( j == 0 ) {
            if( d.message.days < 0 ) {
                return '<span class="maintext">' + Math.abs(d.message.days) + ' days before expiry' + '</span><span class="subtext">' + d.message.status_text + '</span>';
            } else if( d.message.days == 0 ) {
                return '<span class="maintext">on expiry day' + '</span><span class="subtext">' + d.message.status_text + '</span>';
            } else if( d.message.days > 0 ) {
                return '<span class="maintext">' + Math.abs(d.message.days) + ' days after expiry' + '</span><span class="subtext">' + d.message.status_text + '</span>';
            }
        } else if( j == 1 ) {
            return '<span class="maintext">' + d.message.subject + '</span><span class="subtext">' + d.message.parent_subject + '</span>';
        }
    }
    this.cert.rowFn = function(s, i, d) {
        if( d == null ) { return ''; }
        return 'M.ciniki_fatt_settings.cert.save(\'M.ciniki_fatt_settings.message.open("M.ciniki_fatt_settings.cert.messagesUpdate();","ciniki.fatt.cert",M.ciniki_fatt_settings.cert.cert_id,"' + d.message.id + '");\');';
    }
    this.cert.open = function(cb, cid) {
        if( cid != null ) { this.cert_id = cid; }
        this.sections._buttons.buttons.delete.visible = (this.cert_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.fatt.certGet', {'tnid':M.curTenantID, 'cert_id':this.cert_id, 'messages':'yes', 'certs':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.cert;
            p.data = rsp.cert;
            p.sections._courses.fields.courses.list = (rsp.courses!=null?rsp.courses:{});
            p.sections.details.fields.alt_cert_id.options = rsp.certs;
            p.refresh();
            p.show(cb);
        });
    };
    this.cert.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_fatt_settings.cert.close();'; }
        if( this.cert_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.fatt.certUpdate', {'tnid':M.curTenantID, 'cert_id':this.cert_id}, c, function(rsp) {
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
            M.api.postJSONCb('ciniki.fatt.certAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                eval(cb);
            });
        }
    };
    this.cert.remove = function(cid) {
        M.confirm('Are you sure you want to remove this certification?',null,function() {
            M.api.getJSONCb('ciniki.fatt.certDelete', {'tnid':M.curTenantID, 'cert_id':cid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.cert.close();
            });
        });
    };
    this.cert.addButton('save', 'Save', 'M.ciniki_fatt_settings.cert.save();');
    this.cert.addClose('Cancel');

    //
    // Message panel
    //
    this.message = new M.panel('Message', 'ciniki_fatt_settings', 'message', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.fatt.settings.message');
    this.message.message_id = 0;
    this.message.object = '';
    this.message.object_id = 0;
    this.message.data = {};
    this.message.sections = {
        'details':{'label':'', 'aside':'yes', 'fields':{
            'status':{'label':'Status', 'type':'select', 'options':{'0':'Inactive', '10':'Require Approval', '20':'Auto Send'}},
            'days':{'label':'Days', 'type':'text', 'size':'small'},
            }},
        '_subject':{'label':'', 'aside':'yes', 'fields':{
            'subject':{'label':'Subject', 'type':'text'},
            }},
        '_message':{'label':'', 'aside':'yes', 'fields':{
            'message':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_parent_subject':{'label':'', 'fields':{
            'parent_subject':{'label':'Subject', 'type':'text'},
            }},
        '_parent_message':{'label':'', 'fields':{
            'parent_message':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.message.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_fatt_settings.message.remove(M.ciniki_fatt_settings.message.message_id);'},
            }},
    };
    this.message.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    };
    this.message.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.messageHistory', 'args':{'tnid':M.curTenantID, 'message_id':this.message_id, 'field':i}};
    }
    this.message.open = function(cb, o, oid, mid) {
        if( o != null ) { this.object = o; }
        if( oid != null ) { this.object_id = oid; }
        if( mid != null ) { this.message_id = mid; }
        this.sections._buttons.buttons.delete.visible = (this.message_id>0?'yes':'no');
        M.api.getJSONCb('ciniki.fatt.messageGet', {'tnid':M.curTenantID, 
            'message_id':this.message_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_fatt_settings.message;
                p.data = rsp.message;
                p.refresh();
                p.show(cb);
        });
    };
    this.message.save = function() {
        if( this.message_id > 0 ) {
            var c = this.serializeForm('no');
            if( this.data.object != this.object ) {
                c += '&object=' + this.object;
            }
            if( this.data.object_id != this.object_id ) {
                c += '&object_id=' + this.object_id;
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.fatt.messageUpdate', {'tnid':M.curTenantID,
                    'message_id':this.message_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_fatt_settings.message.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            c += '&object=' + this.object + '&object_id=' + this.object_id;
            M.api.postJSONCb('ciniki.fatt.messageAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.message.close();
            });
        }
    };
    this.message.remove = function(cid) {
        M.confirm('Are you sure you want to remove this message?',null,function() {
            M.api.getJSONCb('ciniki.fatt.messageDelete', {'tnid':M.curTenantID, 'message_id':cid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.message.close();
            });
        });
    };
    this.message.addButton('save', 'Save', 'M.ciniki_fatt_settings.message.save();');
    this.message.addClose('Cancel');

    //
    // The documents settings panel
    //
    this.documents = new M.panel('Documents', 'ciniki_fatt_settings', 'documents', 'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.documents');
    this.documents.sections = {
        'image':{'label':'Header Image', 'fields':{
            'default-header-image':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'header':{'label':'Header Address Options', 'fields':{
            'default-header-contact-position':{'label':'Position', 'type':'toggle', 'default':'center', 'toggles':this.positionOptions},
            'default-header-name':{'label':'Tenant Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'default-header-address':{'label':'Address', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'default-header-phone':{'label':'Phone', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'default-header-cell':{'label':'Cell', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'default-header-fax':{'label':'Fax', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'default-header-email':{'label':'Email', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'default-header-website':{'label':'Website', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.documents.save();'},
            }},
    };
    this.documents.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.settingsHistory', 
            'args':{'tnid':M.curTenantID, 'setting':i}};
    }
    this.documents.fieldValue = function(s, i, d) {
        if( this.data[i] == null && d.default != null ) { return d.default; }
        return this.data[i];
    };
    this.documents.addDropImage = function(iid) {
        M.ciniki_fatt_settings.documents.setFieldValue('default-header-image', iid);
        return true;
    };
    this.documents.deleteImage = function(fid) {
        this.setFieldValue(fid, 0);
        return true;
    };
    this.documents.open = function(cb) {
        M.api.getJSONCb('ciniki.fatt.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.documents;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    };
    this.documents.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.fatt.settingsUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.documents.close();
            });
        } else {
            this.close();
        }
    };
    this.documents.addButton('save', 'Save', 'M.ciniki_fatt_settings.documents.save();');
    this.documents.addClose('Cancel');

    //
    // The aed settings panel
    //
    this.aeds = new M.panel('AED Settings', 'ciniki_fatt_settings', 'aeds', 'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.aeds');
    this.aeds.sections = {
        'header':{'label':'Header Address Options', 'fields':{
            'aeds-expirations-message-enabled':{'label':'Enabled', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
            'aeds-expirations-message-next':{'label':'Next Date', 'type':'date'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.aeds.save();'},
            }},
    };
    this.aeds.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.settingsHistory', 
            'args':{'tnid':M.curTenantID, 'setting':i}};
    }
    this.aeds.fieldValue = function(s, i, d) {
        if( this.data[i] == null && d.default != null ) { return d.default; }
        return this.data[i];
    };
    this.aeds.addDropImage = function(iid) {
        M.ciniki_fatt_settings.aeds.setFieldValue('default-header-image', iid);
        return true;
    };
    this.aeds.deleteImage = function(fid) {
        this.setFieldValue(fid, 0);
        return true;
    };
    this.aeds.open = function(cb) {
        M.api.getJSONCb('ciniki.fatt.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.aeds;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    };
    this.aeds.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.fatt.settingsUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.aeds.close();
            });
        } else {
            this.close();
        }
    };
    this.aeds.addButton('save', 'Save', 'M.ciniki_fatt_settings.aeds.save();');
    this.aeds.addClose('Cancel');

    //
    // Message panel
    //
    this.welcomemsg = new M.panel('Message', 'ciniki_fatt_settings', 'welcomemsg', 'mc', 'medium', 'sectioned', 'ciniki.fatt.settings.welcomemsg');
    this.welcomemsg.message_id = 0;
    this.welcomemsg.object = 'ciniki.fatt.welcomemsg';
    this.welcomemsg.object_id = 0;
    this.welcomemsg.data = {};
    this.welcomemsg.sections = {
        'details':{'label':'', 'aside':'yes', 'fields':{
            'status':{'label':'Status', 'type':'select', 'options':{'0':'Inactive', '10':'Require Approval', '20':'Auto Send'}},
//            'days':{'label':'Days', 'type':'text', 'size':'small'},
            }},
        '_subject':{'label':'', 'aside':'yes', 'fields':{
            'subject':{'label':'Subject', 'type':'text'},
            }},
        '_message':{'label':'', 'aside':'yes', 'fields':{
            'message':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
//        '_parent_subject':{'label':'', 'fields':{
//            'parent_subject':{'label':'Subject', 'type':'text'},
//            }},
//        '_parent_message':{'label':'', 'fields':{
//            'parent_message':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
//            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_fatt_settings.welcomemsg.save();'},
            }},
    }
    this.welcomemsg.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    };
    this.welcomemsg.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.fatt.messageHistory', 'args':{'tnid':M.curTenantID, 'message_id':this.message_id, 'field':i}};
    }
    this.welcomemsg.open = function(cb, mid) {
        if( mid != null ) { this.message_id = mid; }
        M.api.getJSONCb('ciniki.fatt.messageGet', {'tnid':M.curTenantID, 'message_id':'welcomemsg'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_fatt_settings.welcomemsg;
            p.data = rsp.message;
            p.message_id = rsp.message.id;
            p.refresh();
            p.show(cb);
        });
    };
    this.welcomemsg.save = function() {
        if( this.message_id > 0 ) {
            var c = this.serializeForm('no');
            if( this.data.object != this.object ) {
                c += '&object=' + this.object;
            }
            if( this.data.object_id != this.object_id ) {
                c += '&object_id=' + this.object_id;
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.fatt.messageUpdate', {'tnid':M.curTenantID, 'message_id':this.message_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_fatt_settings.welcomemsg.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            c += '&object=' + this.object + '&object_id=' + this.object_id;
            M.api.postJSONCb('ciniki.fatt.messageAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_fatt_settings.welcomemsg.close();
            });
        }
    };
    this.welcomemsg.addButton('save', 'Save', 'M.ciniki_fatt_settings.welcomemsg.save();');
    this.welcomemsg.addClose('Cancel');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_fatt_settings', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        // 
        // Setup main menu
        //
        this.menu.sections._.list.courses.visible = M.modFlagSet('ciniki.fatt', 0x01); // (M.curTenant.modules['ciniki.fatt'].flags&0x01)>0?'yes':'no';
        this.menu.sections._.list.instructors.visible = M.modFlagSet('ciniki.fatt', 0x01); // (M.curTenant.modules['ciniki.fatt'].flags&0x01)>0?'yes':'no';
        this.menu.sections._.list.certs.visible = M.modFlagSet('ciniki.fatt', 0x10); // (M.curTenant.modules['ciniki.fatt'].flags&0x10)>0?'yes':'no';
        this.menu.sections._.list.locations.visible = M.modFlagSet('ciniki.fatt', 0x04); // (M.curTenant.modules['ciniki.fatt'].flags&0x04)>0?'yes':'no';
        this.menu.sections._aeds.list.aeds.visible = M.modFlagSet('ciniki.fatt', 0x0100); // (M.curTenant.modules['ciniki.fatt'].flags&0x0100)>0?'yes':'no';

        //
        // Determine what is visible
        //
        if( M.modFlagOn('ciniki.fatt', 0x02) ) {
            this.courses.sections.courses.label = 'Courses';
            this.courses.sections.categories.active = 'yes';
//          this.courses.sections.courses.num_cols = 4;
//          this.courses.sections.courses.headerValues = ['Category', 'Name', 'Price', 'Status'];
            this.courses.addButton('add_c', 'Category', 'M.ciniki_fatt_settings.category.open(\'M.ciniki_fatt_settings.courses.open();\',0);', 'add');
        } else {
            this.courses.sections.courses.label = '';
            this.courses.sections.categories.active = 'no';
//          this.courses.sections.courses.num_cols = 3;
//          this.courses.sections.courses.headerValues = ['Name', 'Price', 'Status'];
            this.courses.delButton('add_c');
        }
        if( M.modFlagOn('ciniki.fatt', 0x40) ) {
            this.courses.sections.bundles.active = 'yes';
        } else {
            this.courses.sections.bundles.active = 'no';
        }
        this.cert.sections.messages.active = M.modFlagOn('ciniki.fatt', 0x20); // ((M.curTenant.modules['ciniki.fatt'].flags&0x20) > 0?'yes':'no');
        this.course.sections._categories.active = M.modFlagOn('ciniki.fatt', 0x02); // ((M.curTenant.modules['ciniki.fatt'].flags&0x02) > 0?'yes':'no');
        this.course.sections._bundles.active = M.modFlagOn('ciniki.fatt', 0x40); // ((M.curTenant.modules['ciniki.fatt'].flags&0x40) > 0?'yes':'no');
        this.course.sections._certs.active = M.modFlagOn('ciniki.fatt', 0x10); // ((M.curTenant.modules['ciniki.fatt'].flags&0x10) > 0?'yes':'no');
        this.course.sections.messages.active = M.modFlagOn('ciniki.fatt', 0x08); // ((M.curTenant.modules['ciniki.fatt'].flags&0x08) > 0?'yes':'no');
        if( M.curTenant.modules['ciniki.customers'].settings != null 
            && M.curTenant.modules['ciniki.customers'].settings['ui-labels-parent'] != null ) {
            this.message.sections._parent_subject.label = M.curTenant.modules['ciniki.customers'].settings['ui-labels-parent'] + ' Message';
        } else {
            this.message.sections._parent_subject.label = 'Parent Message';
        }
        if( M.curTenant.modules['ciniki.customers'].settings != null 
            && M.curTenant.modules['ciniki.customers'].settings['ui-labels-child'] != null ) {
            this.message.sections._subject.label = M.curTenant.modules['ciniki.customers'].settings['ui-labels-child'] + ' Message';
        } else {
            this.message.sections._subject.label = 'Customer Message';
        }

        //
        // Setup the tax types
        //
        if( M.modOn('ciniki.taxes') ) {
            this.course.sections.details.fields.taxtype_id.active = 'yes';
            this.course.sections.details.fields.taxtype_id.options = {'0':'No Taxes'};
            if( M.curTenant.taxes != null && M.curTenant.taxes.settings.types != null ) {
                for(i in M.curTenant.taxes.settings.types) {
                    this.course.sections.details.fields.taxtype_id.options[M.curTenant.taxes.settings.types[i].type.id] = M.curTenant.taxes.settings.types[i].type.name;
                }
            }
        } else {
            this.course.sections.details.fields.taxtype_id.active = 'no';
            this.course.sections.details.fields.taxtype_id.options = {'0':'No Taxes'};
        }
        
        if( args.manage != null ) {
            switch(args.manage) {
                case 'courses': this.courses.open(cb); break;
                case 'instructors': this.instructors.open(cb); break;
                case 'locations': this.locations.open(cb); break;
                case 'certs': this.certs.open(cb); break;
                case 'message': this.messageList(cb); break;
                default: this.menu.open(cb); break;
            }
        } else if( args.course_id != null ) {
            this.course.open(cb, args.course_id);
        } else if( args.instructor_id != null ) {
            this.instructor.open(cb, args.instructor_id);
        } else if( args.location_id != null ) {
            this.location.open(cb, args.location_id);
        } else if( args.cert_id != null ) {
            this.cert.open(cb, args.cert_id);
        } else {
            this.menu.open(cb);
        }
    }
}
