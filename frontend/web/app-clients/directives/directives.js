/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
(function(window, angular, undefined) {
'use strict';
angular.module('app.directives', [])
    .directive('organizations', function(){
        return {
            restrict: 'A',
            controller: ['$rootScope', '$scope', 'AppService', '$timeout', function(a, b, c, d) {
                var errorCallback = function(errors) {
                    angular.forEach(errors, function(error) {
                        b.errors[error.field] = error.message;
                    });
                };
                var resetValue = function() {
                    b.Model = {}, b.errors = {};
                };
                b.Model  = {},
                b.errors = {},
                b.organizations = c.data.organizations;
                a.$on('E:APP_ASSETS', function(event, data) {
                    b.organizations = data.organizations;
                });

                b.createOrganization = function() {
                    c.organizations.create(b.Model).then(function(response) {
                        resetValue();
                        if (angular.isDefined(a.appAssets.organizations)) {
							response.id = parseInt(response.id);
                            a.appAssets.organizations.push(response);
                        }
                        a.$location.path('c/' + response.name).replace();
						_.defer(function(){
							$('body').trigger('click');
							$("body").animate({ scrollTop: $('html').prop("scrollHeight")}, 1000);}
						);
                    }, errorCallback);
                };
            }]
        }
    })
    .directive('meProfile', ['$rootScope',function(a) {
        return {
            restrict: 'E',
            transclude: true,
            controller: ['$scope', function($scope) {
                $scope.identity = a.appAssets;
                a.$on('E:APP_ASSETS', function(event, data) {
                    $scope.identity = data;
                });
				a.$on('E:UPDATE_ME_PROFILE', function(event, data) {
                    $scope.identity = data;
                });
            }],
            templateUrl: '/app-clients/widgets/me-profile.html'
        }
    }])
    .directive('cards', function() {
        return {
            retrict: 'AC',
            link: function(scope, element, atrrs) {
                $(element).hover(function() {
                    $('.'+ atrrs.class).removeClass('card-active');
                    $(element).addClass('card-active');
                }, function(){
                    $(element).removeClass('card-active');
                });
            }
        }
    })
    .directive('addList', function() {
        return {
            restrict: 'AC',
            link: function(scope, element, attrs) {
                element.on('click', function(e) {
                    $(this).removeClass('is-hidden');
                    e.stopPropagation();
                });
                $(document).on('click', function() {
                    $(element).addClass('is-hidden');
                });
            }
        }
    })
    .directive('updateList', ['$rootScope', '$compile', 'popupViews', function(a, b, c) {
        return {
            restrict: 'AC',
            link: function(scope, element, attrs) {
                var listBoxHeader = element.closest('.list-box-header');
                element.on('click', function(e) {
                    var temp = b(c.updateListName())(scope);
                    $('h4.list-box-header-name').removeClass('hide');
                    $('div.tools').removeClass('hide');
                    $('.edit-title-list-box').addClass('hide');

                    $(listBoxHeader).find('h4.list-box-header-name').addClass('hide');
					$(listBoxHeader).find('i.flaticon-tag25').addClass('hide');
					
                    $(listBoxHeader).find('div.tools').addClass('hide');
                    $(listBoxHeader).find('div.edit-title-list-box').removeClass('hide');
                    $(document.body).find('.update-list-name-form').remove();
                    $(listBoxHeader).find('div.edit-title-list-box').children('form').append(temp);
                    $('.js-cancel').on('click', function() {
                        $(listBoxHeader).find('h4.list-box-header-name').removeClass('hide');
                        $(listBoxHeader).find('div.tools').removeClass('hide');
                        $(listBoxHeader).find('div.edit-title-list-box').addClass('hide');
						$('.flaticon-tag25').removeClass('hide');
                        $(document.body).find('.update-list-name-form').remove();
                    });
                    e.stopPropagation();
                });
                $('.edit-title-list-box').on('click', function(e) {
                    e.stopPropagation();
                });
				listBoxHeader.on('click', function(e) {
                    e.stopPropagation();
                });
                $(document.body).on('click', function() {
                    $(listBoxHeader).find('h4.list-box-header-name').removeClass('hide');
					$(listBoxHeader).find('i.flaticon-tag25').removeClass('hide');
                    $(listBoxHeader).find('div.tools').removeClass('hide');
                    $('div.edit-title-list-box').addClass('hide');
                });
            }
        }
    }])
    .directive('addCard', ['$rootScope', '$compile', 'popupViews', function(a, b, c) {
        return {
            restrict: 'A',
            link: function(scope, element, attrs) {
                var listColumn = element.closest('.list-box');
                element.on('click', function(e) {
                    var temp = b(c.addCardToList())(scope);
                    if ($(listColumn).find('div.add-card-form').length > 0) {
                        $('.add-card-form').removeClass('hide');
                    } else {
						
                        $(document.body).find('.add-card-form').remove();
                        $(listColumn).find('.list-box-content').append(temp);
                    }
                    $('.list-box-footer').children('a').removeClass('hide');
                    $(this).addClass('hide');
                    $(listColumn).children('.list-box-content').scrollTop( 500 );
                    $('.js-cancel').on('click', function() {
                        $('div.add-card-form').addClass('hide');
						listColumn.find("textarea").focus();
                        element.removeClass('hide');
                    });
					$('.scrollbarchange').scrollTop(3000);
					$('.scrollbarchange').perfectScrollbar('update');
                    e.stopPropagation();			
                });
				
                $('.list-box-content').on('click', function(e) {
                    e.stopPropagation();
                });
				
				
                $(document.body).on('click', function(e) {
					if($(e.target).attr("class") != "modal-close" && typeof($(e.target).attr("class")) !== 'undefined') {
						$('div.add-card-form').addClass('hide');
						element.removeClass('hide');
					}
                });
				
            }
        }
    }])
    .directive('stringToTimestamp', function() {
        return {
            require: 'ngModel',
            link: function(scope, element, attrs, ngModel) {
                // view to model
                ngModel.$parsers.push(function(value) {
                    return Date.parse(value);
                });
            }
        }
    })
    .directive('jsSelectBackground', ['$rootScope', 'Socket', 'activitys', '$compile', function(a, b, c, d) {
        return {
            restrict: 'C',
            link: function(scope, element, attrs) {
				var classtype = attrs.classtype;
				var changetype = attrs.changetype; //add @minhquyen to change background "images"
                element.on('click', function(e) {
					if(classtype == 'board') {
						if(changetype == 'color'){
							/*** added @minhquyen to change images background ***/
							var model = scope.prefsBackground[element.index()-1];
							model = angular.extend({backgroundType: 'color'}, model, {});
							
							scope.changeBackground( model );
							//b.emit('WE:BOARD_BACKGROUND_CHANGED', scope.prefsBackground[element.index()-1]);
						}
						else if(changetype == 'picture'){
							/*** added @minhquyen to change images background ***/
							var background = element.find("span").attr("background");
							var model = {
									backgroundType: 'picture',
									backgroundImage: background
								};
								
							scope.changeBackground( model );
							//b.emit('WE:BOARD_BACKGROUND_CHANGED', model);
						}
						
						scope.member = {
						   id: a.appAssets.id,
						   username: a.appAssets.username,
						   displayName: a.appAssets.displayName,
						   initialsName: a.appAssets.initialsName,
						   avatarHash: a.appAssets.avatarHash
						};
						var temp = d(c.members(scope.member))(scope);
						$('body').find('#activity').append(temp);
					} else {
						scope.changeBackgroundsList( scope.list.id, scope.prefsBackground[ element.index() ]);
					}					
               });
            }
        };
    }])
	.directive('showdialog',["$rootScope", "$location", function(a, b) {
		return {
			restrict: "C",
			link: function( scope, element, attrs) {
				var param = b.search();
				var cardid = attrs.cardid;
				if(angular.isDefined(param.card) && param.card == cardid) {
					element.find(".card-item-details").trigger('click');
				}
				
			}
		}
	}])
	/*** ADDED @minhquyen 2018**/
	.directive('popupExtend',['$rootScope', function(a) {
		return{
			restrict:'AC',
			link:function( scope, element, attrs ) {
				
				if(angular.isDefined(attrs.popInit) && angular.isDefined(attrs.listId)){
					var idList = parseInt(attrs.listId);
					
					scope.eventOpenList(idList);
					$(".modal-dialog").css("width", "1300px");
				};
				
				element.on('click',function(){
					
					if( attrs.popShow == "popup-card-detail"){
						
						var cardId = angular.isDefined(attrs.cardId) ? attrs.cardId : 0;
						var cardcurrent = _.find(scope.boardPageAssets.cards, {id: parseInt(cardId)});
						scope.cardData = {};
						scope.eventOpenCard(cardcurrent);
						
						$("."+attrs.popShow).show();
						$(".popup-show-priority").hide();
						$(".popup-show-priority").html("");
						$(".modal-dialog").css("width", "768px");
					};
					if( attrs.popShow == "popup-show-priority"){
						var idList = attrs.listId;
						scope.eventOpenList(idList);
						
						$("."+attrs.popShow).show();
						$(".popup-card-detail").html("");
						$(".popup-card-detail").hide();
						$(".modal-dialog").css("width", "1300px");
					}
					
				});
				   
			}
		}
	}])
	.directive('copycard',['$rootScope', 'AppService', function(a, b) {
		return {
			restrict: "C",
			link: function( scope, element, attrs) {
				scope.model = {
					idBoard 		: scope.boardPageAssets.id,
					idList 			: scope.cardDetail.idList,
					pos 		  	: scope.cardDetail.pos, 
					displayName 	: scope.newlineTextarea(scope.cardDetail.displayName) 
				};
				/*show position list now*/
				var $list = _.find(scope.boardPageAssets.lists, {id: scope.cardDetail.idList});
				scope.cardPosition = $list.cards.length + 1;
				var $boardNow = $list.cards.length + 1;
				/*filter board organization*/
				scope.selectBoards = [];			
				var myBoard = scope.filterMyBoard();
				angular.forEach(myBoard, function($val) {
					$val.groupName = "My Board";
					scope.selectBoards.push($val);
				});					
				angular.forEach(scope.appAssets.organizations, function($organization) {
					angular.forEach(scope.appAssets.boards, function($val) {
						if($val.idOrganization == $organization.id) {
							$val.groupName = $organization.name;
							scope.selectBoards.push($val);
						}
					});	
				});
				
				/* show position card in list */
				scope.cardPositionCopy = function () {
					var position = [];
					for(var $i = 1; $i <= scope.cardPosition; $i++) {
						position.push($i);
					}
					return position;
				};	
				/* change board get list and get position*/
				scope.getBoardLists = function() {
					if(scope.model.idBoard  == scope.boardPageAssets.id) {
						scope.boardList 	=  scope.boardPageAssets.lists;
						scope.model.idList  =  scope.cardDetail.idList;
						scope.model.pos		=  scope.cardDetail.pos;
						scope.cardPosition 	= $boardNow;
						
					} else {
						b.lists.getLists(scope.model.idBoard).then(function(response) {
							scope.boardList 	= response;
							scope.model.idList =  0;
							scope.model.pos  = 1;
							if(angular.isDefined(response) && response.length > 0) {
								scope.cardPosition  =  response[0].cards.length + 1;
								scope.model.idList  =  response[0].id;								
							} else {
								scope.cardPosition = 1;
							}
						});
					}
				}
				/* change list get position */
				scope.getPositionCard = function () {
					var $idListCompare = _.pluck(scope.boardPageAssets.lists,'id');
					var $lists = {};
					var $idList  = scope.model.idList;
					scope.model.pos  = 1;
					if($idListCompare.indexOf($idList) > -1) {
						$lists = _.find(scope.boardPageAssets.lists,{id: parseInt($idList)});
					} else {
						$lists = _.find(scope.boardList,{id: parseInt($idList) });
					}
					
					if(angular.isDefined($lists)) {
						scope.cardPosition = $lists.cards.length + 1;
					}
					
				}	
			}
		}
	}])
	/*--TA--*/
    .directive('anotherBoard',['$rootScope', 'AppService', function(a, b) {
       return {
           restrict: "C",
           link: function( scope, element, attrs) {

               //set ng-model
               scope.modelAnother = {
                   idBoard 		: scope.boardPageAssets.id,
                   idList 		: scope.listPresent.id,
                   pos 		  	: scope.listPresent.pos,
                   displayName 	: scope.boardPageAssets.displayName
               };

               /*show position list now*/
               var $list = _.find(scope.boardPageAssets.lists, {id: scope.listPresent.id});
               scope.cardPosition = $list.cards.length + 1;
               var $boardNow = $list.cards.length + 1;

               /*filter board organization*/
               scope.anotherBoards = [];
               var myBoard = scope.filterMyBoard();
               angular.forEach(myBoard, function($val) {
                   $val.groupName = "My Board";
                   scope.anotherBoards.push($val);
               });
               angular.forEach(scope.appAssets.organizations, function($organization) {
                   angular.forEach(scope.appAssets.boards, function($val) {
                       if($val.idOrganization == $organization.id) {
                           $val.groupName = $organization.name;
                           scope.anotherBoards.push($val);
                       }
                   });
               });

               /* change board get list and get position*/
               scope.getAnotherBoard = function() {
                   if(scope.modelAnother.idBoard  == scope.boardPageAssets.id) {
                       scope.anotherLists 	=  scope.boardPageAssets.lists;
                       scope.modelAnother.idList  =  scope.listPresent.id;
                       scope.modelAnother.pos		=  scope.listPresent.pos;
                       scope.cardPosition 	= $boardNow;

                   } else {
                       b.lists.getLists(scope.modelAnother.idBoard).then(function(response) {
                           scope.anotherLists 	= response;
                           scope.modelAnother.idList =  0;
                           scope.modelAnother.pos  = 1;
                           if(angular.isDefined(response) && response.length > 0) {
                               scope.cardPosition  =  response[0].cards.length + 1;
                               scope.modelAnother.idList  =  response[0].id;
                           } else {
                               scope.cardPosition = 1;
                           }
                       });
                   }
               };

               /* change list get position */
               scope.getAnotherList = function () {
                   var $idListCompare = _.pluck(scope.boardPageAssets.lists,'id');
                   var $lists = {};
                   var $idList  = scope.modelAnother.idList;
                   scope.modelAnother.pos  = 1;
                   if($idListCompare.indexOf($idList) > -1) {
                       $lists = _.find(scope.boardPageAssets.lists,{id: parseInt($idList)});
                   } else {
                       $lists = _.find(scope.anotherLists,{id: parseInt($idList) });
                   }

                   if(angular.isDefined($lists)) {
                       scope.cardPosition = $lists.cards.length + 1;
                   }

               }
           }
	   }
    }])
	.directive('cardTree',['$rootScope', '$filter', function(a, b) {
		return {
			restrict:"C",
			link: function(scope, element, attrs) {
				var menuRootParent = _.filter(scope.boardPageAssets.cards, function($card) {
					
					return $card;
				});
				var tempTree = "<ul class='tree'>";
				var parentTreeCard = function(data) {
					tempTree += "<ul>";
					angular.forEach(data.parentId, function($v, $k) {
						var $card = _.find(scope.boardPageAssets.cards,{ id:$v });
						if(angular.isDefined($card)) {							
							if($card.parentId.length > 0) {
								if(numCheck == 1){
									tempTree += '<li> <input type="checkbox" checked="checked" id="c'+numCheck+' "> ';
								} else {
									tempTree += '<li> <input type="checkbox" id="c'+numCheck+' "> ';
								}
								tempTree += '<label class="tree_label" for="c"'+numCheck+'>'+b('notnewline')($card.displayName)+'</label>';
								numCheck++;
								parentTreeCard($card);
							} else {
								tempTree += "<li> <span class='tree_label'>"+ b('notnewline')($card.displayName) +"</span>";
							}
							tempTree += '</li> ';
						}
					});
					tempTree += "</ul>";
				}
				if(angular.isDefined(menuRootParent)) {
					angular.forEach(menuRootParent, function($val, $key) {
						if($val.parentId.length > 0) {
							if(numCheck == 1){
								tempTree += '<li> <input type="checkbox" checked="checked" id="c'+numCheck+'" >';
							} else {
								tempTree += '<li> <input type="checkbox" id="c'+numCheck+'">';
							}
							tempTree += '<label class="tree_label" for="c'+numCheck+'">'+b('notnewline')($val.displayName)+'</label>';
							numCheck++;
							parentTreeCard($val);
						} else {
							tempTree += "<li> <span class='tree_label'>"+ b('notnewline')($val.displayName) +"</span>";
						}
						tempTree += '</li>';
					});
				}
				tempTree += "</ul>";
				element.append(tempTree);
				return tempTree;
			}
		}
}])
.directive('changeCard', function() {
	return {
		restrict: "A",
		link: function(scope, element, attrs) {
			element.on("click", function() {
				var cardid = attrs.cardid;
				if(angular.isDefined(cardid)) {
					$("button[class='close']").trigger('click');
					$("div.card-item[cardid='"+cardid+"']").find('.card-item-details').trigger('click');
				}
			});
		}
	}
})
.directive('modelCalendar', ['$rootScope', '$popover', '$timeout', 'PATH', '$compile', 'uiCalendarConfig', '$filter', function(a, b, c, d, e, f, g) {
	return {
		restrict: "C",
		link: function(scope, element, attrs) {
			
			scope.alertEventOnClick = function(calEvent, jsEvent, view) {
				$('div[cardid="'+calEvent.id+'"]').find('.card-item-details').trigger('click');
			}	
			
			scope.eventRender  = function(event, element) {
				var tempEvent = document.createElement('div');
				tempEvent.className = "card-item";
				tempEvent.setAttribute('cardId',event.id);
				if(angular.isDefined(event.labels)) {
					angular.forEach(event.labels, function($val) {
						var templabel = document.createElement('span');
						templabel.className = 'card-labels card-label label-color-'+$val.color;
						tempEvent.appendChild(templabel);
					})
				}		
				var tempNameCard = document.createElement('p');
				tempNameCard.textContent = g('notnewline')(event.displayName);
				tempEvent.appendChild(tempNameCard);
				element.find('.fc-content').remove();
				element.append(tempEvent);
			};
			
			scope.dayClick = function(date, jsEvent, view)  {
				if(angular.isDefined(scope.boardPageAssets.lists) && scope.boardPageAssets.lists.length > 0 && scope.hasMemberInBoard(scope.appAssets.id)) {
					var classDate = new Date(date.format());
					var filterListOpen = _.filter(scope.boardPageAssets.lists, function($list) {
						return $list.closed == 0 ;
					})
					filterListOpen  = (angular.isDefined(filterListOpen))? filterListOpen : [];
					scope.model = {
						displayName:"",
						idList: (filterListOpen.length>0) ? filterListOpen[0].id : "",
						due: classDate.getTime()
					}
				
					/* click close, open popup */
					var contentPopover = $('.addCardCalendar').html();
					if(angular.isUndefined(contentPopover)) {
						$("#addCardCalendar").trigger('click');
						/*position popup left*/
						var pageX = jsEvent.pageX - $(".addCardCalendar").width()/2;
						var positionX = jsEvent.pageX + $(".addCardCalendar").width();
						if(positionX > $(window).width()) {
							pageX = jsEvent.pageX - (positionX - $(window).width()) - 20;
						} else if (pageX < 0) {
							pageX = 0;
						}
						
						/* position popup top*/
						var pageY = jsEvent.pageY;

						$(".addCardCalendar  #date").html(date.format());
						$('.addCardCalendar').css({top: pageY, left: pageX});
					}
				}
			};
			
			a.$on("reploadCalendar", function(event, response) {
				f.calendars.calendar.fullCalendar('removeEvents');
				f.calendars.calendar.fullCalendar('addEventSource',response);
			});
			
			scope.alertOnDrop = function (event, delta, revertFunc, jsEvent, ui, view) {
				var classDate = new Date(event.start.format());
				scope.updateCard(event.id,{due:classDate.getTime()});
				$('.fc-day').removeClass('dayhover');
			};
			 
			scope.dayRender = function(date ,cell) {
				var dateNow = new Date();
				dateNow = dateNow.getTime();
				
			};
			
			scope.viewRender = function(view, element) {
				var calHeight = $(window).height()*0.83;
				$('#calendar').fullCalendar('option', 'height', calHeight);
			}
			
			scope.uiConfig = {
				calendar : {
					editable : true,
					header : {
						// left : 'month basicWeek',
						// center : 'title',
						right : 'today prev,next,prevWeek,nextWeek'
					},
					dayClick : scope.dayClick,
					eventClick : scope.alertEventOnClick,
					eventDrop : scope.alertOnDrop,
					firstDay: 1,
					eventRender : scope.eventRender,
					dayRender: scope.dayRender,
					viewRender: scope.viewRender,
					height: $(window).height()*0.83,
					
				}
			};
			
			
		}
	}
	
}])
.directive("getList", ['$rootScope', function(a) {
	return { 	
		restrict: "C",
		link: function(scope, element, attrs) {
			var idList = attrs.listid;
			scope.getlist = _.find(scope.boardPageAssets.lists, {id: parseInt(idList)});
			if (angular.isUndefined(scope.getlist)) {
				scope.getList = {name:undefined}
			}
 		}
	}
}])
/*** ADDED @minhquyen 2018**/
.directive('magnificPopups',['$rootScope' ,function(a){
	return {
		
		restrict: 'AC',
		link: function(scope, element, attrs) {
			
			$(element).css("cursor", "pointer");
			
			$(element).magnificPopup({
				type: 'image',
				closeBtnInside: false,
				fixedContentPos: true,
				mainClass: 'mfp-no-margins mfp-with-zoom',
				image: {
					verticalFit: true
				}
			});
		}
		
	}
}])
;
})(window, window.angular);