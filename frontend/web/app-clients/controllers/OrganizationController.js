/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
app.controller('OrganizationController', ['$rootScope', '$scope', 'AppService', 'Socket', 'MEMBER_ROLES','$location', 'ngMeta', 'toaster', '$timeout', function(a, b, c, d, e, f, g, h, l) {

    b.MEMBER_ROLES = e;
    b.organizationsPageAssets = [];
	a.configs = {
		backgroundColor: "",
		bodyClass: ""
	};
    c.organizations.getOrganization().then(function(response) {
        b.organizationsPageAssets = response;
		b.organizationDisplayName  = response.displayName;
		
	},function (reason){
		h.pop('error','You do not have permission to view this team');
		//l(function(){window.location.href='/'},3000);
	});
    a.$on('E:ORGANIZATION_GETTED', function(event, data) {
		b.organizationsPageAssets = data;		
    });
   
    d.on('WE:ADDED_MEMBER_TO_ORGANIZATION', function(member) {
        if (angular.isDefined(b.organizationsPageAssets.members)) {
            b.organizationsPageAssets.members.push(member);
        }
        if (angular.isDefined(b.organizationsPageAssets.memberShips)) {
            b.organizationsPageAssets.memberShips.push({
                idMember: mermber.id,
                idOrganization: b.organizationsPageAssets.id,
                memberType: 'normal'
            });
        }
    });
	
	d.on('WE:ORGANIZATION_UPDATE', function(data) {
		if(angular.isDefined(b.organizationsPageAssets)) {
			var organization_name =  b.organizationsPageAssets.name;
			b.organizationsPageAssets = angular.extend(b.organizationsPageAssets, data);
			b.organizationDisplayName  = data.displayName;
			if(organization_name == data.name) {
				b.showProfile = "";
			}
		}				
    });
	
    d.on('WE:REMOVED_MEMBER_OF_ORGANIZATION', function(idMember) {
        if (angular.isDefined(b.organizationsPageAssets.members)) {
            b.organizationsPageAssets.members = _.filter(b.organizationsPageAssets.members, function(member) {
                return member.id !== idMember;
            });
        }
        if (angular.isDefined(b.organizationsPageAssets.memberShips)) {
            b.organizationsPageAssets.memberShips = _.filter(b.organizationsPageAssets.memberShips, function(memberShips) {
                return memberShips.idMember !== idMember;
            });
        }
        if (a.appAssets.id == idMember) {
            window.location.href = '/';
        }
    });
	
    d.on('WE:ORGANIZATION_MEMBER_ROLE_CHANGED', function(data) {
        var memberShips = _.find(b.organizationsPageAssets.memberShips, {idMember: data[0]});
        if (angular.isDefined(memberShips)) {
            memberShips.memberType = data[1];
        }
		if(memberShips.memberType == 'normal') {
			b.showProfile = false;
		}
    });
	
	d.on('WE:ORGANIZATION_DELETED',function(data){
		if(data.id == b.organizationsPageAssets.id) {
			window.location.href = '/';
		}
	});
	/**
	 * ORGANIZATION delete logo
	 */
	d.on("WE:ORGANIZATION_DELETE_LOGO", function(data) {
		if(data.id == b.organizationsPageAssets.id) {
			b.organizationsPageAssets  = angular.extend(b.organizationsPageAssets, data);
		}
		var organization = _.find(a.appAssets.organizations, {id : data.id});
		organization = angular.extend(organization, data);
	});
	/**
	 * ORGANIZATION_CHANGE_LOGO
	 */
	d.on('WE:ORGANIZATION_CHANGE_LOGO', function(data) {
		if(data.id == b.organizationsPageAssets.id) {
			b.organizationsPageAssets  = angular.extend(b.organizationsPageAssets, data);
		}
		var organization = _.find(a.appAssets.organizations,{id:data.id});
		organization = angular.extend(organization,data);
	});
    /*
    *@desc create new board
    */
    b.createBoard = function(model) {
        c.boards.create(model).success(function(response) {
            a.$location.path(response._links.url.href).replace();
        });
    };
    /*
     *@params string query
     *@desc search member list by keyword
     */
    b.searchMembers = function(query) {
       var params = {
            type: 'organizations',
            query: query
       };
       c.organizations.searchMembers(params).then(function(response) {
           b.organizationMembers = response;
       });
    };
    /**
     * @desc add Member to organization
     * @param member
     */
    b.addMemberToOrganization = function(member) {
		$organization = _.find(a.appAssets.organizations,{id:b.organizationsPageAssets.id});
        return c.organizations.model = b.organizationsPageAssets, c.organizations.hasMemberInOrganization(member.id) ? void 0 : c.organizations.addMemberToOrganization(member,$organization).then(function(response) {
            if (angular.isDefined(b.organizationsPageAssets.members)) {
                b.organizationsPageAssets.members.push(response);
            }
            if (angular.isDefined(b.organizationsPageAssets.memberShips)) {
                b.organizationsPageAssets.memberShips.push({
                    idMember: member.id,
                    idOrganization: b.organizationsPageAssets.id,
                    memberType: 'normal'
                });
            }
			var listMember = _.pluck(b.organizationsPageAssets.memberShips,'idMember');
			d.emit('WE:GET_NOTIFICATIONS',{listMember:listMember});
        });
    };
    /**
     * @desc remove member of organization
     * @param member
     */
    b.removeMemberOfOrganization = function(idMember) {
        var r;
        function removeMember(idMember, redirect) {
            return c.organizations.removeMemberOfOrganization(idMember).then(function(response) {
                if (angular.isDefined(b.organizationsPageAssets.members)) {
                    b.organizationsPageAssets.members = _.filter(b.organizationsPageAssets.members, function(member) {
                        return member.id !== idMember;
                    });
                }
                if (angular.isDefined(b.organizationsPageAssets.memberShips)) {
                    b.organizationsPageAssets.memberShips = _.filter(b.organizationsPageAssets.memberShips, function(memberShips) {
                        return memberShips.idMember !== idMember;
                    });
                }
                if (a.appAssets.id == idMember) {
                    window.location.href = '/';
                }
				var listMember = _.pluck(b.organizationsPageAssets.memberShips,'idMember');
				listMember.push(idMember);
				d.emit('WE:GET_NOTIFICATIONS',{listMember:listMember});
            });
        };
        return c.organizations.model = b.organizationsPageAssets, r = b.getOrganizationMemberRoleLabel(a.appAssets.id), r === 'Admin' && b.isOwner(idMember) ? void 0 : (r === 'Admin' && !b.isOwner(idMember) ? removeMember(idMember) : (r === 'Normal' && a.appAssets.id === idMember ? removeMember(idMember, true) : void 0));
    };
    b.changeMemberRole = function(idMember, role) {
        var r;
        return c.organizations.model = b.organizationsPageAssets, r = angular.lowercase(b.getOrganizationMemberRoleLabel(idMember)) === role ? void 0 : c.organizations.changeRole(idMember, role).then(function(response) {
            var memberShips = _.find(b.organizationsPageAssets.memberShips, {idMember: idMember});
            if (angular.isDefined(memberShips)) {
                memberShips.memberType = role;
            }
            d.emit('WE:ORGANIZATION_MEMBER_ROLE_CHANGED', [idMember, role]);
			var listMember = _.pluck(b.organizationsPageAssets.memberShips,'idMember');
			d.emit('WE:GET_NOTIFICATIONS',{listMember:listMember});
        });
    };

    b.isOwner = function(idMember) {
        return c.organizations.isOwner(idMember);
    };

    b.getOrganizationMemberRoleLabel = function(idMember) {
        var memberShips = _.find(b.organizationsPageAssets.memberShips, {idMember: idMember});
        return memberShips ? e[memberShips.memberType] : e['normal'];
    };
	b.errors = {};
	 var errorCallback = function(errors) {
        angular.forEach(errors, function(error) {
            b.errors[error.field] = error.message;
        });
    };
	
	/* update organization*/
	b.submitted = false;
	b.updateOrganization = function(model){
		b.submitted = true;
		var organization_name =  b.organizationsPageAssets.name;
		c.organizations.update(model.id, model).then(function(response) {
            b.organizationsPageAssets = angular.extend(b.organizationsPageAssets, response);
			b.organizationDisplayName  = response.displayName;
			if(organization_name == response.name) {
				b.showProfile = false;
			}
			h.pop('success','Successful edit Team');
		}, errorCallback);
	}
	/* Delete organization */
	b.deleteOrganization = function() {
		c.organizations.delete(b.organizationsPageAssets.id).then(function(response){
			a.appAssets.organizations = _.filter(a.appAssets.organizations,function($organization){
				return response.id != $organization.id;
			});
			angular.forEach(a.appAssets.boards,function($board,$key){
				if($board.idOrganization == response.id) {
					a.appAssets.boards[$key].idOrganization = 0;
				} 
			});
			var listMember = _.pluck(b.organizationsPageAssets.memberShips,'idMember');
			d.emit('WE:GET_NOTIFICATIONS',{listMember:listMember});
			window.location.href = '/';
		});
	}
	/* check Role admin*/
	b.checkRole = function() {
		$role = _.find(b.organizationsPageAssets.memberShips,{idMember:a.appAssets.id});
		return ($role && $role.memberType == 'admin') ?  true : false ;
	}
	/* SET TITLE*/
	if(f.path().indexOf('members') >= 0) {
		g.setTitle("ORGANIZATION MEMBERS");
	} else if(f.path().indexOf('account') >= 0) {
		g.setTitle("ORGANIZATION ACCOUNT");	
	} else {
		g.setTitle('ORGANIZATION BOARDS');
	}
	/*
		Images: uplload  image logo
	*/
    b.logoAttachment = function(files, errFiles) {
        b.files = files;
        b.errFiles = errFiles;
		if(errFiles.length > 0) {
			angular.forEach(errFiles,function(error){
				if(error.$error == "maxSize") {
					h.pop ('error','File can not be larger than '+error.$errorParam);
				} else if (error.$error == "pattern") {
					h.pop ('error',"Incorrect file format");
				} else if (error.$error == "maxHeight") {
					h.pop ('error',"file height must be less than "+ error.$errorParam);
				} else if (error.$error == "maxWidth") {
					h.pop ('error',"file width must be less than "+ error.$errorParam);
				}
			});
		}
        return  !(b.getOrganizationMemberRoleLabel(a.appAssets.id) == 'Admin') ? h.pop ('error',"you not upload logo. Permission Asset")  : c.organizations.uploadimage(b.files, errFiles).then(function(response) {
			b.organizationsPageAssets  = angular.extend(b.organizationsPageAssets,response);
			var organization = _.find(a.appAssets.organizations,{id:response.id});
			organization = angular.extend(organization,response);
			_.defer(function(){
				$('.modal-close').trigger('click');
			});
			h.pop('success','Successful upload files');
        });
    };
	/**
	 * Remove logo organization 
	 */
	b.deleteLogo = function () {
		return !(b.getOrganizationMemberRoleLabel(a.appAssets.id) == 'Admin') ? h.pop ('error',"you not delete logo. Permission Asset") : c.organizations.deletelogo().then(function(response) {
			var organization = _.find(a.appAssets.organizations, {id:response.id});
			organization = angular.extend(organization, response);
			b.organizationsPageAssets  = angular.extend(b.organizationsPageAssets, response);
		});
	}
	/*
	 * click button edit organization
	*/
	b.showProfile = false;
	b.editOrganization = function($showProfile) {
		b.showProfile = $showProfile;
		b.formOrganization = {
			id			: b.organizationsPageAssets.id,
			displayName : b.organizationsPageAssets.displayName,
			name		: b.organizationsPageAssets.name,
			website		: b.organizationsPageAssets.website,
			desc		: b.organizationsPageAssets.desc
		}
	}
	
	
}]);