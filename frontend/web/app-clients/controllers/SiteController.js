app.controller('SiteController', ['$scope', '$state', 'UserBehaviorsService','AppService','$timeout', function(a, b, c, d, e) {
    a.Model = {
        LoginForm: {
            username: null,
            password: null
        },
		SiginForm: {
			username:null,
			password:null,
			displayName:null,
			email:null
		}
    };
	a.Tabname = "Jooto Login";
    a.submitted = false;
    a.errors = {};
    a.login = function() {
        a.submitted = true;
        a.errors = {};
        c.login(a.Model).then(
          function(user) {
			d.load();
            b.go('app.dashboard');
        },function(errors) {
            angular.forEach(errors, function(error) {
                a.errors[error.field] = error.message;
            });
			popupLoginCenter();
        });
    };
    a.signup = function() {
		
    };
	a.SignupErrors = {};
	/* register user*/
	a.register = function(){
		a.sumitted = true;
		c.register(a.Model).then(
			function(user){
				b.go('app.dashboard');
			},function(errors){
				angular.forEach(errors,function(error){
					a.SignupErrors[error.field] = error.message;
				});
				popupLoginCenter();
			}
		)
	}
	a.forgot = function(){
		a.sumitted = true;
		c.forgot(a.Model).then(
			function(respone){
				if(respone.success){
					a.ForgotSuccess = respone.message;
					a.ForgotError 	= "";
				}else{
					a.ForgotError 	= respone.message;
					a.ForgotSuccess = "";
				}					
			}
		),function(errors){
			angular.forEach(errors,function(error){
				a.SignupErrors[error.field] = error.message;
			});
			popupLoginCenter();
		}
	}
	a.clear = function(){
		a.SignupErrors = {};
		a.Model.SignupForm = {};
		a.errors = {};
		a.Model.LoginForm = {};
		popupLoginCenter();
	}
	var popupLoginCenter = function(){
		_.defer(function(){
			var $heightwindow = $(window).height()-100;
			var $heightPopup = $('.loginJooto').outerHeight();
			$top = ($heightwindow - $heightPopup)/2;
			if($top > 0)
				$('.loginJooto').closest('.login-container').css("top",$top+"px");
		});
	}
}]);
