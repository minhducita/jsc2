/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
app.controller('DashBoardController', ['$rootScope', '$scope', 'AppService', 'Socket', function(a, b, c, d) {
    a.configs = {
        backgroundColor: '',
        bodyClass: ''
    };

	/* get StarsCount */
	var getStarsCount = function(){
		var $idBoardStar = _.map(b.dashBoardPageAssets.boardStars,function(item) {
			return parseInt(item.idBoard,10);
		});
		angular.forEach( b.dashBoardPageAssets.boards,function($board,$key){
			$board.starsCount = _.contains($idBoardStar,$board.id) ? 1 : 0;
			b.dashBoardPageAssets.boards[$key] = $board;
		});
	}
	
    b.dashBoardPageAssets = c.data;
	getStarsCount();
	
	a.$on('reloadData', function() {
		b.dashBoardPageAssets = a.appAssets;
		/* Get role member */
		angular.forEach(b.dashBoardPageAssets.organizations, function(organization,$key){
			var $role = _.find(organization.memberShips, {idMember: b.dashBoardPageAssets.id});
			if(angular.isDefined($role)) {
				b.dashBoardPageAssets.organizations[$key].role = $role.memberType;
			}		
		});
		getStarsCount();
	})
	
	a.$on('E:ORGANIZATION_UPDATE', function(event, data) {
	    angular.forEach(b.dashBoardPageAssets.organizations,function($value,$key){
			if($value.id == data.id){
				b.dashBoardPageAssets.organizations[$key] = data;
				b.dashBoardPageAssets.organizations[$key].$$hashKey = $value.$$hashKey;
			}
		});
    });
	/**/
	/**
	**
	**/
	d.on('WE:BOARD_CREATED',function(data){
		if(a.appAssets.id === data.idMember){
			a.appAssets.boards.push(data);
		}
	});
    /**
     *
     * BOARD_STAR EVENT
    
    d.on('WE:BOARD_STARED', function(data) {
        b.dashBoardPageAssets.boardStars.push(data);
        var currentBoard = _.find(b.dashBoardPageAssets.boards, {id: data.idBoard});
        if (angular.isDefined(currentBoard)) {
            currentBoard.starsCount = 1;
			getStarsCount();
        }
    });
	*/
	
    d.on('WE:BOARD_STAR_DELETED', function(id) {
        b.dashBoardPageAssets.boardStars = _.filter(b.dashBoardPageAssets.boardStars, function(boardStar) {
            return boardStar.idBoard != id;
        });
        var currentBoard = _.find(b.dashBoardPageAssets.boards, {id: id});
        if (angular.isDefined(currentBoard)) {
            currentBoard.starsCount = 0;
			getStarsCount();
        }
    });
	
    d.on('WE:BOARD_BACKGROUND_CHANGED', function(data) {
		a.configs = {
			backgroundColor: '',
			bodyClass: ''
		};
        var board = _.find(a.appAssets.boards, {id: data[0]});
        if (angular.isDefined(board)) {
            board.prefs = angular.extend(board.prefs, data[1]);
        }
    });
	/**
	 * ORGANIZATION_CHANGE_LOGO
	 */
	d.on('WE:ORGANIZATION_CHANGE_LOGO', function(data) {
		var organization = _.find(a.appAssets.organizations,{id:data.id});
		organization = angular.extend(organization,data);
	});
    /**
     *
     * ORGANIZATION MEMBER EVENT
     */
    d.on('WE:SEND_ORGANIZATION_TO_MEMBER', function(data) {
        if (!_.find(b.dashBoardPageAssets.organizations, {id: data[0].id}) && a.appAssets.id === data[1]) {
            b.dashBoardPageAssets.organizations.push(data[0]);
        }
    });
	
    d.on('WE:REMOVED_ORGANIZATION_OF_MEMBER', function(data) {
        if (a.appAssets.id === data[1]) {
            b.dashBoardPageAssets.organizations = _.filter(b.dashBoardPageAssets.organizations, function(organization) {
               return organization.id !== data[0];
            });
        }
    });
	
    /**
     *
     * BOARD MEMBER EVENT
     */
    d.on('WE:SEND_BOARD_TO_MEMBER', function(data) {
        if (!_.find(b.dashBoardPageAssets.boards, {id: data[0].id}) && a.appAssets.id === data[1]) {
            b.dashBoardPageAssets.boards.push(data[0]);
        }
    });

    d.on('WE:REMOVED_BOARD_OF_MEMBER', function(data) {
        if (a.appAssets.id === data[1]) {
            b.dashBoardPageAssets.boards = _.filter(b.dashBoardPageAssets.boards, function(board) {
                return board.id !== data[0];
            });
        }
    });
    /**
	 * Organization delete logo
	 */
	d.on("WE:ORGANIZATION_DELETE_LOGO", function(data) {
		var organization = _.find(a.appAssets.organizations, {id : data.id});
		organization = angular.extend(organization, data);
	});
 
    b.errors = {};
    var errorCallback = function(errors) {
        angular.forEach(errors, function(error) {
            b.errors[error.field] = error.message;
        });
    };
	
    b.createBoard = function(model) {
        c.boards.create(model).then(function(response) {
            a.appAssets.boards.push(response);
			b.dashBoardPageAssets.boards = a.appAssets.boards;
			_.defer(function() {
				$(".modal-close i").trigger('click');
				 a.$location.path(response._links.url.href).replace();
			});
        }, errorCallback);
    };

    b.filterBoard = function(type) {
        var data = [];
        switch(type) {
            case 'boardStars':
                var idBoardOfStarList = _.pluck(b.dashBoardPageAssets.boardStars, 'idBoard');
				var idMemberOfOrganizations  = _.pluck(b.dashBoardPageAssets.organizations,'idMember');
                idBoardOfStarList = _.map(idBoardOfStarList, function(id) {
                   return parseInt(id, 10);
                });
                data = _.filter(b.dashBoardPageAssets.boards, function(board) {
                   return _.contains(idBoardOfStarList, parseInt(board.id));
                });
            break;
            case 'myBoard':
                var idOrganizationList = _.pluck(b.dashBoardPageAssets.organizations, 'id');
				
                data = _.filter(b.dashBoardPageAssets.boards, function(board) {
					return !_.contains(idOrganizationList, parseInt(board.idOrganization,10));
                });
				
            break;
        }
        return data;
    };
	
	/* toggle board start*/
	b.toggleBoardStar = function(id) {
        return c.data = b.dashBoardPageAssets, c.boards.getBoardStar(id) ? c.boards.unStarBoard(id).then(function(response) {
            b.dashBoardPageAssets.boardStars = _.filter(b.dashBoardPageAssets.boardStars, function(boardStar) {
               return boardStar.idBoard != id;
            });
            var currentBoard = _.find(b.dashBoardPageAssets.boards, {id: id});
            if (angular.isDefined(currentBoard)) {
                currentBoard.starsCount = 0;
            }
        }) : c.boards.starBoard(id).then(function(response) {
            b.dashBoardPageAssets.boardStars.push(response);
            var currentBoard = _.find(b.dashBoardPageAssets.boards, {id: id});
            if (angular.isDefined(currentBoard)) {
                currentBoard.starsCount = 1;
            }
        });
	};
	
}]);
