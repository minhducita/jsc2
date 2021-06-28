/**
 * Author: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
'use strict';
angular.module('app', [
    'yaru22.angular-timeago',
    'ui.router',
    'ui.sortable',
    'oc.lazyLoad',
    'angular-storage',
    'toaster',
	'pickadate',
    'ngAnimate',
    'ngFileUpload',
    'mgcrea.ngStrap',
    'app.constants',
    'app.directives',
    'app.factorys',
    'app.services',
    'app.filters',
    'app.jsCore',
    'ngWebsocket',
    'app.autocomplete',
	'ngMeta',
	'ngSanitize',
	'perfect_scrollbar',
	'color.picker',
	'ui.calendar',
	'ui.toggle'
]);
var app = angular.module('app').config(['$controllerProvider', '$compileProvider', '$filterProvider', '$provide', function(a, b, c, d) {
    app.controller = a.register, 
	app.directive = b.directive, 
	app.filter = c.register, 
	app.factory = d.factory, 
	app.service = d.service, 
	app.constant = d.constant, 
	app.value = d.value;
}]);

app.config(['$ocLazyLoadProvider', 'MODULES_CONFIG', 'ngMetaProvider', function(a, b, c) {
    a.config({
        debug: !0,
        events: !0,
        modules: b
    });
	c.useTitleSuffix(true);
	c.setDefaultTitle('Jooto');
	c.setDefaultTitleSuffix(' | JAWHM');
}]).run(["$rootScope", "$state", "$stateParams", "$location", "ngMeta", function(a, b, c, d, e) {
	e.init(); // load meta description keyword
    a.$state = b;
	a.$stateParams = c; 
	a.$location = d;
	
}]).config(['$stateProvider', '$urlRouterProvider', '$locationProvider', 'LIBS_CONFIG', 'MODULES_CONFIG', 'PATH', function(a, b, c, d, e, f) {
	b.rule(function ($injector, $location) {
        var path = $location.path(), normalized = path.toLowerCase();
		
        if (path != normalized) {
            $location.replace().path(normalized);
        }
	
        if (path[path.length - 1] == '/') {
            path = path.substring(0, path.length - 1);
            $location.replace().path(path);
        }
		
    }).otherwise(function ($injector, $location) {
        var c = $injector.get('$state');
        c.go('app.dashboard');
    });
    function g(a, b) {
        return {
            deps: ["$ocLazyLoad", "$q", function(e, f) {
                var g = f.defer(),
                    h = !1,
                    name = null;
                return a = angular.isArray(a) ? a : a.split(/\s+/), h || (h = g.promise), angular.forEach(a, function(a) {
                    h = h.then(function() {
                        return c[a] ? e.load(c[a]) : (angular.forEach(d, function(b) {
                            b.name == a ? name = b.name : name = a
                        }), e.load(name))
                    })
                }), g.resolve(), b ? h.then(function() {
                    return b()
                }) : h
            }]
        }
    }
	
    a.state('app', {
        abstract: !0,
        url: '',
        templateUrl: f.views + 'layouts/app.html'
    }).state('app.dashboard', {
        url: '/dashboard',
		meta: {
		  'title': 'DashBoard',
		  'description': 'Jawhm jooto',
		},
        templateUrl: f.views + 'dashboard/dashboard.html',
        controller: 'DashBoardController',
        resolve: g(["assetsDashboard"])
    }).state('app.organization', {
        url: '/o/:id{optionParams:(?:/|/members|/account)?}',
        controller: 'OrganizationController',
		meta: {
		  'title': 'ORGANIZATION BOARD',
		  'description': 'Jawhm jooto organization board',
		},
        templateUrl: f.views + 'organization/view.html',
        resolve:g(["assetsOrganization"])
    }).state('app.notifications', {
		url:'/notifications/:id{optionParams:(?:/[a-zA-Z0-9一-龠ぁ-ゔァ-ヴー々〆〤０-９Ａ-ｚァ-ンｧ-ﾝﾞﾟ\-]*)?}',
		meta: {
			'title': 'Notifications',
			'description': 'JSC notifications '
		},
		controller: 'NotificationsController',
		templateUrl: f.views+'notifications/default.html',
		resolve:g(["assetsNotification"])		
	}).state('app.me', {
        url: '/me/:id{optionParams:(?:/|/cards|/account)?}',
		meta: {
		  'title': 'Profile',
		  'description': 'Jawhm jooto profile',
		},
        controller: 'MeController',
        templateUrl: f.views + 'me/view.html',
        resolve:g(["assetsMe"])
    }).state('app.board', {
        url: '/b/{id:[0-9]+}{optionParams:(?:/[a-zA-Z0-9一-龠ぁ-ゔァ-ヴー々〆〤０-９Ａ-ｚァ-ンｧ-ﾝﾞﾟ\-]*)?}',
        controller: 'BoardController',
		meta: {
		  'title': 'Board',
		  'description': 'Jawhm jooto Board',
		},
        templateUrl: f.views + 'board/view.html',
        resolve: g(["assetsBoards"])
    }).state('app.export', { // create route load report page
        url: '/export',
        controller: 'ExportController',
        meta: {
          'title': 'Export',
          'description': 'Export Jawhm jsc'
        },
        templateUrl: f.views + 'report/view.html',
        resolve: g(["assetsExport"])
    }).state('app.search', {
        url: '/search',
        controller: 'SearchController',
		meta: {
		  'title': 'Search',
		  'description': 'Jawhm jooto Search',
		},
        templateUrl: f.views + 'search/view.html',
        resolve: g(["assetsSearch"])
    }).state('app.board.card', {
        url: '/cs/{id:[0-9]+}{option:(?:/[a-zA-Z0-9一-龠ぁ-ゔァ-ヴー々〆〤０-９Ａ-ｚァ-ンｧ-ﾝﾞﾟ\-]*)?}',
        controller: 'BoardController',
        templateUrl: f.views + 'board/view.html',
        resolve: g([f.controllers + "BoardController.js", f.assets + "css/chanel-detail.css"])
    }).state('login', {
        url: '/site/login',
		meta: {
		  'title': 'Login',
		  'description': 'Jawhm jooto login',
		},
        controller: 'SiteController',
        templateUrl: f.views + 'site/login.html',
        resolve: g([f.controllers + "SiteController.js"])
    }).state('forgot', {
        url: '/site/forgot',
		meta: {
		  'title': 'Forgot',
		  'description': 'Jawhm jooto Forgot',
		},
        controller: 'ForgotController',
        templateUrl: f.views + 'site/forgot.html',
        resolve: g([f.controllers + "ForgotController.js"])
    }).state('app.error', {
        url: '/site/error',
        templateUrl: f.views + 'site/error.html'
    }).state('404', {
        url: '/404.html',
        controller: 'SiteController',
        templateUrl: f.views + 'site/404.html',
        resolve: g([f.controllers + "SiteController.js", f.assets + "css/404.css"])
    });
	
    c.html5Mode(false);
}]).config(['$httpProvider', function(a) {
    a.defaults.useXDomain = true;
    delete a.defaults.headers.common['X-Requested-With'];
}]).config(['$httpProvider', function(a) {
    a.interceptors.push('InterceptorApi');
}]).config(['$websocketProvider', function(a) {
    a.$setup({
        lazy: false,
        reconnect: true,
        reconnectInterval: 3000,
        mock: false,
        enqueue: false
    });
}]).run(['$rootScope', '$location', 'UserIdentityService', 'HTTP_EXCEPTION', 'toaster', function(a, b, c, d, e) {
    a.$on(d.Unauthorized, function(event) {
        c.removeIdentity();
		if(b.path() !== '/site/forgot'){
			
			return a.$state.go('login');
		}
    });
	
    // a.$on(d.Forbidden, function(event, errors) {
        // e.clear();
        // e.pop({
            // type: 'error',
          // title: errors.message,
            // showCloseButton: true
        // });
        // return a.$state.go('app.error');
    // });
    
    // a.$on(d.NotFound, function(event) {
       // a.$state.go('404');
    // });
	
    a.$on('$locationChangeSuccess', function (event, next, current) {
        // redirect to login page if not logged in and trying to access a restricted page
        var restrictedPage = $.inArray(b.path(), ['/site/login', 'site/forgot']) == -1;
       
		if (restrictedPage && c.isGuest()) {
			if (b.path() === '/site/forgot') {
				a.forgotParam = a.$location.search();
				if(typeof(a.forgotParam.token) == 'undefined'){
					a.$state.go('login');
				}
			} else {
				a.$state.go('login');
			}
        } else if (!restrictedPage && !c.isGuest()) {
            a.$state.go('app.dashboard');
        } else if (!restrictedPage && c.isGuest()) {
            event.preventDefault();
        }
    });
}]);