app.controller('ForgotController', ['$scope', '$state','UserBehaviorsService','AppService','$timeout', function(a, b,c,d,e) {
	var $token = a.forgotParam.token;
	c.forgot({"token":$token}).then(function(){
		e(function(){
			b.go('login');
		},3000);
	});
}]);
