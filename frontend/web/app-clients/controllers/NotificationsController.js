/**
 * Auth: LY NAM
 * Email: lynam1990@gmail.com
 */
app.controller("NotificationsController", ['$rootScope', '$scope', 'Notification', 'Socket', 'MEMBER_ROLES','$location', 'ngMeta', 'toaster' , function(a, b, c, d, e, f, g, h) {
	b.notificationPageAssets  = a.appAssets;
	a.configs = {
		backgroundColor: "",
		bodyClass: ""
	};
	a.$on('reloadData', function() {
		b.notificationPageAssets = a.appAssets;
	});
	/* load notification pagging */
	b.notifyActiveMore = true;
	b.notifyPerpage = 20,
	b.notifyLimit = 10,
	b.loadMoreNotify = function() {
		c.getNotificationlimit(b.notifyLimit,b.notifyPerpage,'member',0).then(function(response){
			if(response.length > 0) {
				b.notifyPerpage += b.notifyLimit;
				if(angular.isDefined(response)) {
					angular.forEach(response, function($val, $key) {
						b.notificationPageAssets.notifications.push($val);
					})
				}
			} else {
				b.notifyActiveMore = false;
			}
		});
	} 
}]);