/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
(function(window, angular, undefined) {
'use strict';
angular.module('app.constants', [])
    .constant('API_CONFIG', {
        url: function() {
            var config   = {'http:':80, 'https:':443},
            location = window.location;
            var hostname =  location.hostname;
            return location.protocol + '//api' + location.hostname.replace("jsc","") + (location.port && (location.port != config[location.protocol]) ? ':' + location.port : '') + '/v1';
        },
        urlSuffix: '',
        to: function(route) {
            if (!angular.isString(route)) {
                return;
            }
            if (route.match(/^http([s]?):\/\/.*/)) {
                return route;
            }
            route = route.replace(/\/\//g, '/');
            var apiUrl = this.url();
            var r = route[0] === '/' ? apiUrl + route : apiUrl + '/' + route;
                r = r.replace(/\/?$/, '');
            return r + this.urlSuffix
        }
    })
    .constant('WS_CONFIG', {
        url: function() {
            var config   = {'http:':80, 'https:':443},
                location = window.location;
            return 'ws://api.bluecloudvn.com:8888';
        }
    })
	//ws://apijooto.bluecloud.tokyo:8888 : link socket server
    .constant('LIBS_CONFIG', {
        card: []
    })
    .constant('MODULES_CONFIG',[{
        name: "assetsDashboard",
        files: ["/app-clients/controllers/DashBoardController.js", '/assets/core/jsScrollPopover.js']
    }, {
        name: "assetsOrganization",
        files: ["/app-clients/controllers/OrganizationController.js", "/assets/css/chanel-detail.css", "/assets/css/chanel-profile.css"]
    },{
        name: "assetsMe",
        files: ["/app-clients/controllers/MeController.js", "/assets/css/chanel-detail.css", "/assets/css/chanel-profile.css"]
    }, {
        name: "assetsBoards",
        files: [
					"/app-clients/controllers/BoardController.js", 
					"/assets/css/chanel-detail.css", 
				]
    },{
        name: "assetsSearch",
        files: ["/app-clients/controllers/SearchController.js", "/assets/css/search.css"]
    }, {
		name: "assetsNotification",
		files: ["/app-clients/controllers/NotificationsController.js", "/assets/css/chanel-detail.css", "/assets/css/notifications.css"]
	  }, {
        name: "assetsExport",
        files: ["/app-clients/controllers/ExportController.js"]
    }])
    .constant('PATH', {
        controllers: "/app-clients/controllers/",
        views: "/app-clients/views/",
        widgets: "/app-clients/widgets/",
        assets: "/assets/"
    })
    .constant('HTTP_EXCEPTION', {
        Badrequest: 'Badrequest',
        Unauthorized: 'Unauthorized',
        Forbidden: 'Forbidden',
        NotFound: 'NotFound',
        MethodNotAllowed: 'MethodNotAllowed',
        ServerError: 'ServerError'
    })
    .constant('MEMBER_ROLES', {
        normal: 'Normal',
        admin: 'Admin'
    })
    .constant('ACTION_ACTIVITY', {
        action_changed_board_background: "{memberCreator} changed the background of this board"
    });
})(window, window.angular);