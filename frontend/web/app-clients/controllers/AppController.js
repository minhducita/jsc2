app.controller('AppController', ['$rootScope', '$scope', 'UserBehaviorsService', 'HTTP_EXCEPTION', 'AppService', '$filter', 'Socket', 'toaster', 'Notification', "$location", "$timeout", function(a, b, c, d, e, f, g, h, l, m, n) {
	b.menuBoard = a.appAssets = e.load();
    a.$on('E:APP_ASSETS', function(event, data) {
        a.appAssets = data;
		b.menuBoard = data;
		
		angular.forEach(b.menuBoard.boards, function($v, $k) {
			b.menuBoard.boards[$k].href = "/#/b/" + $v.id + "/" + $v.name;
		});
		angular.forEach(b.menuBoard.organizations, function($v, $k) {
			b.menuBoard.organizations[$k].href = '/#/o/'+ $v.name + "/members";
		});
		
        b.notifications = data.notifications;
        var countNotifcation  = _.countBy(data.notifications,function($notification){
			return ($notification.notifyMember.read == 0)?"read":"notread";
        });
		b.countNotifcation = angular.isUndefined(countNotifcation.read)?0:countNotifcation.read;
		a.$broadcast('reloadData');
    });
	
	// it's need service 
	 b.filterBoard = function(type) {
        var data = [];
        switch(type) {
			case 'boardStars':
                var idBoardOfStarList = _.pluck(b.menuBoard.boardStars, 'idBoard');
				var idMemberOfOrganizations  = _.pluck(b.menuBoard.organizations,'idMember');
                idBoardOfStarList = _.map(idBoardOfStarList, function(id) {
                   return parseInt(id, 10);
                });
                data = _.filter(b.menuBoard.boards, function(board) {
                   return _.contains(idBoardOfStarList, parseInt(board.id))
                });
				// data = _.filter(data,function(board){
					// if(board.idMember == a.appAssets.id) {
						// return true;
					// } else {
						// return 	_.contains(idMemberOfOrganizations,board.idMember);
					// }
				// });
				
            break;
            case 'myBoard':
                var idOrganizationList = _.pluck(b.menuBoard.organizations, 'id');				
                data = _.filter(b.menuBoard.boards, function(board) {
					return !_.contains(idOrganizationList, parseInt(board.idOrganization,10));
                });
            break;
        }
        return data;
    };
	
    // it's need service
	b.toggleBoardStar = function(id) {
        return e.data = b.menuBoard, e.boards.getBoardStar(id) ? e.boards.unStarBoard(id).then(function(response) {
            b.menuBoard.boardStars = _.filter(b.menuBoard.boardStars, function(boardStar) {
               return boardStar.idBoard != id;
            });
            var currentBoard = _.find(b.menuBoard.boards, {id: id});
            if (angular.isDefined(currentBoard)) {
                currentBoard.starsCount = 0;
            }
        }) : e.boards.starBoard(id).then(function(response) {
            b.menuBoard.boardStars.push(response);
            var currentBoard = _.find(b.menuBoard.boards, {id: id});
            if (angular.isDefined(currentBoard)) {
                currentBoard.starsCount = 1;
            }
        });
	};
	/**
     * logout
     */
    b.logout = function() {
        c.logout();
        a.$state.go('login');
    };
	/* create board header */
	b.errors = {};
	var errorCallback = function(errors) {
        angular.forEach(errors, function(error) {
            b.errors[error.field] = error.message;
        });
    };
	b.createBoard = function(model) {
        e.boards.create(model).then(function(response) {
            b.menuBoard.boards.push(response);
            a.$location.path(response._links.url.href).replace();
        }, errorCallback);
    };
	/*create team header*/
	var resetValue = function() {
		b.Model = {}, b.errors = {};
	};
	b.Model  = {};
	b.createOrganization = function() {
		e.organizations.create(b.Model).then(function(response) {
			resetValue();
			if (angular.isDefined(a.appAssets.organizations)) {
				response.id = parseInt(response.id);
				a.appAssets.organizations.push(response);
			}
            a.$location.path('c/' + response.name).replace();
			_.defer(function(){
				$('body').trigger('click');
				$("body").animate({ scrollTop: $('html').prop("scrollHeight")}, 1000);
			});
		}, errorCallback);
	};
	
	g.on('WE:BOARD_UPDATED',function(data) { // socket  close board
		if(data.closed === 0){
			checkBoard = _.find(a.appAssets.boards,{id:data.id});
			if(checkBoard) {
				angular.forEach(a.appAssets.boards,function($board,$key){
					if($board.id == data.id) {
						a.appAssets.boards[$key] = angular.extend(a.appAssets.boards[$key],data);						
					}
				});
			} else {
				a.appAssets.boards.push(data);
			}			
		} else {
			
			a.appAssets.boards = _.filter(a.appAssets.boards,function($board){
				return $board.id != data.id;
			});	
			a.appAssets.boardCloses.push(data);
		}
	});
	
    g.on('WE:ORGANIZATION_DELETED', function(data) {
		a.appAssets.organizations = _.filter(a.appAssets.organizations,function($organization){
			return data.id != $organization.id;
		});
		angular.forEach(a.appAssets.boards,function($board,$key){
			if($board.idOrganization == data.id) {
				a.appAssets.boards[$key].idOrganization = 0;
			} 
		});
	});
	
	g.on('WE:ORGANIZATION_UPDATE', function(data) {	
		// update organization appAssets
		var organization = _.find(a.appAssets.organizations,{id:data.id});
		if(angular.isDefined(organization)){
			angular.extend(organization,data);
		}
		
    });
	
	b.ReopenBoard = function(board) {	// open board 
        function CheckCloseBoard(board) {
			var $modelupdate  = {"closed":(board.closed === 0)?1:0};
            return e.boards.update(board.id,$modelupdate).then(function(response) {
				a.$broadcast('E:OPEN_CLOSE_BOARD',response);
				if( $modelupdate.closed === 0) {
					var boardextent = _.find(a.appAssets.boardCloses,{id:response.id});
					boardextent = angular.extend(boardextent,response);	
					
					a.appAssets.boardCloses = _.filter(a.appAssets.boardCloses, function($board) {
						return $board.id !== response.id;
					});
					
					if(boardextent) {
						a.appAssets.boards.push(boardextent);
					}
				} else {
					if(!a.appAssets.boardCloses)
						a.appAssets.boardCloses = [];
					
					var $findboard = _.find(a.appAssets.boards, {id:response.id});
					$findboard = angular.extend($findboard,response);
					
					a.appAssets.boards = _.filter(a.appAssets.boards, function($board) {
						return $board.id !== response.id;
					});
					
					if(angular.isDefined($findboard) && a.appAssets.id == response.idMember) {
						a.appAssets.boardCloses.push($findboard);
					}	
				}
				b.menuBoard = a.appAssets;
            });
        };
            
		if(a.appAssets.id != board.idMember) {
			h.pop('error', 'You can not change this because they must have at least one person as administrator.');
		} else {
			CheckCloseBoard(board);
		}
	};
	
    b.notificationmessenger = function($model) {
        return  l.getMessenger($model);
    };
    
    b.readNotification = function() {
        l.readAll().then(function(){
            a.appAssets.notifications = _.filter(a.appAssets.notifications,function($notification){
                $notification.notifyMember.read = 1;
                return $notification;
            });
            b.countNotifcation = 0;            
        });
    } 
	
    g.on('WE:GET_NOTIFICATIONS',function($param){ //get socket notification
        // add notification by Member
		$getNotification = function() {
			l.getNotification().then(function(response) {
				b.countNotifcation  = 0;
				var notifications = a.appAssets.notifications;                        
				angular.forEach(response,function(notification){
					if(!_.find(notifications,{id:notification.id})){
						notifications.push(notification);
					}
				});                        
				notifications = _.sortBy(notifications,function(notification){
					return !notification.id;
				});
				b.notifications = a.appAssets.notifications = notifications.sort(function(a,b){
					return b.id - a.id;
				});
				angular.forEach(a.appAssets.notifications,function(notification){  //count notification 
					if(notification.notifyMember.read === 0) {		
						b.countNotifcation = b.countNotifcation+1;
					}
				});  
				a.$broadcast('E:GET_NOTIFICATIONS_BOARD_AND_CARD',response);				
			});
		}
		if($param.listMember.indexOf(a.appAssets.id) > -1) {
			 $getNotification();
		}
    });
	
	b.listSearch = {};
	var linkCard = function($card) {
		var $board = _.find(a.appAssets.boards,{id: parseInt($card.idBoard)});
		if($board) {
			return "/#/b/" + $board.id + "/" + $board.name + "?card=" + $card.id;
		}
	}
	var $keydownEnter = 0;// spam enter
	b.searchAll = function ($event, $model, $clickSearch) {
		_.defer(function() {
			$(".header-search input").trigger('click');
		});
		if(($event.which == 13 && $keydownEnter < 3) || $clickSearch == 1) {
			$keydownEnter += 1;
			n(function() { $keydownEnter = 0; },3000); // spam enter
			_.defer(function() {
				$('.loadding').show();
				$('.popup-content-search').hide();
			});
			e.search.searchAll($model).then(function(response) {
				b.search = response;
				
				angular.forEach(b.search.cards, function($v, $k) {
					b.search.cards[$k].selectedAttachment = _.find($v.attachments, {id:$v.idAttachmentCover})
					b.search.cards[$k].href = linkCard($v);
					
					var $board = _.find(a.appAssets.boards,{id: parseInt($v.idBoard)});
					b.search.cards[$k].organizationsHref= "#";
					b.search.cards[$k].organizationName = "Myboard";
					if(angular.isDefined($board)) {
						var $organization = _.find(a.appAssets.organizations, {id: parseInt($board.idOrganization)});
						if(angular.isDefined($organization)) {
							b.search.cards[$k].organizationsHref= "/#/o/"+$organization.name;
							b.search.cards[$k].organizationName = $organization.displayName;
						} else {
							b.search.cards[$k].organizationsHref= "#";
							b.search.cards[$k].organizationName = "no team";
						}
						b.search.cards[$k].boardHref = "/#/b/"+$board.id+"/"+$board.name;
						b.search.cards[$k].boardName = $board.displayName;
					} 
					
					
					/* get label */
					$board = _.find(a.appAssets.boards, {id: parseInt($v.idBoard)});
					b.search.cards[$k].labels = _.filter($board.labels, function($label) {
						return _.contains($v.idLabels,$label.id);
					});
					
				});
				
				angular.forEach(b.search.boards, function($v, $k) {
					b.search.boards[$k].href = "/#/b/" + $v.id + "/" + $v.name;
				});
				angular.forEach(b.search.organizations, function($v, $k) {
					b.search.organizations[$k].href = '/#/o/'+ $v.name + "/members";
				});
				_.defer(function() {
					$('.loadding').hide();
					$('.popup-content-search').show();
				});
			});	
		}			
	}	
	checkSearch = function ($search) {
		if(!$search)
			return 1;
		else if($search.cards.length == 0 && $search.boards.length ==0 && $search.organizations.length == 0)
			return 2;
		return 0;
	}
	
	b.eventClickDetailCard = function (href, id) {
		var hrefsplit = href.split("?");
		var link  =  hrefsplit[0].replace("/#/","");
		_.defer(function(){
			$('.modal-close').trigger('click');
		})
		m.search({card: id});
		m.path(link);
		a.$broadcast('E:EVENT_CLICK_FILTER_CARD',id);
	}
	
    /* End header notification */
}]);
