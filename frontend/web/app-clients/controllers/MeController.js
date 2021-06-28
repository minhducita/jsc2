/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
 app.controller("MeController", ['$rootScope', '$scope', 'AppService', 'Socket', 'MEMBER_ROLES','$location', 'ngMeta', 'toaster' , function(a, b, c, d, e, f, g, h) {
	b.ProfileName = '';
	b.Profile = a.appAssets;
	b.errors = {};
	b.success = "";
	a.configs = {
		backgroundColor: "",
		bodyClass: ""
	};
	var reloadProfile = function (data) {
		b.formProfile 						= {};
		b.formProfile.displayName			= data.displayName;
		b.formProfile.initialsName 			= data.initialsName;
	}
	//* click button edit displayName and initialsName*//
	b.editProfile  = function () {
		b.formProfile.displayName			= a.appAssets.displayName;
		b.formProfile.initialsName 			= a.appAssets.initialsName;
	}
	
	reloadProfile(a.appAssets);
    var errorCallback = function(errors) {
        angular.forEach(errors, function(error) {
            b.errors[error.field] = error.message;
        });
    };
	/* Function set title Me*/
	var setMetaTitle = function(){
		if(f.path().indexOf('cards') >= 0) {
			g.setTitle('CARDS '+b.Profile.displayName);
		} else if(f.path().indexOf('account')  >= 0) {
			g.setTitle('ACCOUNT '+b.Profile.displayName);
		} else {
			g.setTitle('PROFILE '+b.Profile.displayName);
		}
	}
	/* END Function set title Me*/
	if(f.path().indexOf('cards') >= 0 ) { // access tab cards
		setMetaTitle();
		c.cards.getCard();		

		a.$on('E:CARD_GETTED',function(event,data) { //c.cards.getCard() reurn cards 
			b.cards = data;
		});		
	} else if(f.path().indexOf('account') >= 0 ) { // access tab setting
		setMetaTitle();
		b.changePassword = function(data){
			c.members.changePassword(data).then(function(response){
				_.defer(function(){
					 $(document.body).find('.js-cancel').trigger('click');
				});
			},errorCallback);
		}
	} else {
		setMetaTitle();
	}
	/* access edit profile */
	d.on('WE:UPDATE_PROFILE', function(data) { // socket update profile
		a.appAssets = angular.extend(a.appAssets, data);
		b.Profile = a.appAssets;
		reloadProfile(data);
    });		
	
	
	if(angular.isUndefined(b.Profile.username)) { // check load page 
		a.$on("reloadData",function() {
			b.ProfileName = a.appAssets.displayName;
			b.Profile =  a.appAssets;
			setMetaTitle();
			reloadProfile(a.appAssets);
		});
	}else{
		b.ProfileName = b.Profile.displayName; 
	}
	
	b.updateMeProfile = function(data) {
		c.members.updateProfile(data).then(function(response){
			b.Profile =  a.appAssets = angular.extend(a.appAssets, data);
			b.ProfileName = response.displayName;
			reloadProfile(b.Profile);
			h.pop('success','Successful edit Profile')
			//a.$broadcast("E:UPDATE_ME_PROFILE",data);
			_.defer( function() {
				var typeimg = response.avatarHash.split("_");
				if(typeimg.length > 1) {
					var imageSrc50 = '/assets/img/profiles/'+typeimg[0]+'/50.'.typeimg[1];
					var imageSrc170 = '/assets/img/profiles/'+typeimg[0]+'/170.'.typeimg[1];
				} else {
					var imageSrc50 = '/assets/img/profiles/'+response.avatarHash+'/50.jpg';
					var imageSrc170 = '/assets/img/profiles/'+response.avatarHash+'/170.jpg';
				}
				$('img[src^="'+imageSrc50+'"]').
					attr('src',imageSrc50+'?time='+$.now());
				
				$('img[src^="'+imageSrc170+'"]').
					attr('src',imageSrc170+'?time='+$.now());	
				$('.js-cancel-edit-profile').trigger('click');
				 b.errors = [];
			});
		},errorCallback);
	}
	/*
		Images uplload  image cardAttachments
	*/
    b.meAttachments = function(files, errFiles) {
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
        return !(a.appAssets.id == b.Profile.id) ? void 0 : c.members.uploadAvatar(b.files, errFiles).then(function(response) {
			a.appAssets = angular.extend(a.appAssets,response);
			/* reload image */
			_.defer( function() {
				var typeimg = response.avatarHash.split("_");
				if(typeimg.length > 1) {
					var imageSrc50 = '/assets/img/profiles/'+typeimg[0]+'/50.'.typeimg[1];
					var imageSrc170 = '/assets/img/profiles/'+typeimg[0]+'/170.'.typeimg[1];
				} else {
					var imageSrc50 = '/assets/img/profiles/'+response.avatarHash+'/50.jpg';
					var imageSrc170 = '/assets/img/profiles/'+response.avatarHash+'/170.jpg';
				}
				$('img[src^="'+imageSrc50+'"]').
					attr('src',imageSrc50+'?time='+$.now());
				
				$('img[src^="'+imageSrc170+'"]').
					attr('src',imageSrc170+'?time='+$.now());			
			});
			h.pop('success','Successful upload files');
        });
    };
	
	b.password = {
		oldpass:"",
		newpass:"",
		repeatnewpass:""
	}
 }]);