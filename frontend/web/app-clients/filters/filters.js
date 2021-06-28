/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
(function(window, angular, undefined) {
    'use strict';
    angular.module('app.filters', [])
        .filter('filterBoard', function() {
            return function(boards, arrayFilter, element) {

            }
        })
		.filter('formatDate',function() {
			return function(input) {
				if(angular.isDefined(input) && input != 0 && input != "") {
					var date = new Date(input);
					return date.getFullYear()+"/"+(date.getMonth() + 1)+"/"+date.getDate() +" "+date.getHours()+":"+date.getMinutes();
				} else {
					return ;
				}
			}
		})
        .filter('memberNotIn', function() {
            return function(members, arrayMembers) {
                if (angular.isDefined(members)) {
                    return _.filter(members, function(member) {
                        var idMembers = _.pluck(arrayMembers, 'id');
                        return !_.contains(idMembers, member.id);
                    });
                }
            };
        })
        .filter('inArray', function($filter) {
            return function(list, arrayFilter, element) {
                if (arrayFilter) {
                    return $filter("filter")(list, function(listItem) {
                        return arrayFilter.indexOf(listItem[element]) != -1;
                    });
                }
            };
        }).filter('filterCard',function() {
			var accessFilterArray = function(filterData,filtervalue){
				var idFilter = _.pluck(filterData, 'id');			
				return _.find(idFilter, function(id) {
					return _.contains(filtervalue, id);
				});
			}
			var accessFilterDate = function ($type) {
				var datenow = new Date();
				if($type == 'day') {
					datenow.setUTCDate(datenow.getUTCDate() + 1);
					var dateStar = new  Date(datenow.getUTCFullYear(),datenow.getUTCMonth(),datenow.getUTCDate());
					var dateEnd  = new  Date(datenow.getUTCFullYear(),datenow.getUTCMonth(),datenow.getUTCDate(),23,59,59);
				} 
				else if($type == 'week') {
					var week = new Date(datenow.getTime() + ( 7 * 24 *60 *60 *1000 ) );
					var dateStar = new Date(datenow.getUTCFullYear(),datenow.getUTCMonth(),datenow.getUTCDate(),0,0,0);
					var dateEnd  = new Date(week.getUTCFullYear(),week.getUTCMonth(),week.getUTCDate(),23,59,59);
				} else if($type == 'month') {
					datenow.setUTCMonth(datenow.getUTCMonth() + 1);
					datenow.setUTCDate(1); // get first day
					var dateStar = new Date(datenow.getUTCFullYear(),datenow.getUTCMonth(),datenow.getUTCDate(),0,0,0);
					datenow.setUTCDate(0);// get last day
					var dateEnd  = new Date(datenow.getUTCFullYear(),datenow.getUTCMonth(),datenow.getUTCDate(),23,59,59);
				} 
				return {dateStar:dateStar,dateEnd:dateEnd};
			}
			return function(cards, filterCard) {
				var results = cards;
				var active = 0; 
				angular.forEach(filterCard,function($v,$k) {	
					if (active  !== 1 && $v.length  === 0) { // check value fitercard not value 
						active = 0;
					}
					else if($v.length  > 0) {
						switch($k) { // write filter here
							case 'label': // filter label
								results = _.filter (results, function(card) {
									return accessFilterArray(card.labels,$v);
								});
								break;
							case 'member': // filter member
								results = _.filter (results, function(card) {
									return  accessFilterArray(card.members,$v);
								});
								break;
							case 'date':
								var datefilter = accessFilterDate($v);
								results = _.filter (results, function(card) {
									return card.due >= (datefilter.dateStar.getTime()/1000) && card.due <= (datefilter.dateEnd.getTime()/1000)
								});
								break;
							case 'relation' :
								results = _.filter(results, function(card) {
									return $v.indexOf(card.id) > -1;
								});
							case 'id':
								results = _.filter(results, function(card){
									return _.contains($v, parseInt(card.id));
								});
							default: active = 0;
						}
						active  = 1;
					}
				});
				if(active == 0) {
					return cards;
				}
				return results;
			}
		})
        .filter('unsafe', function($sce) {
			return function(val) {
				return $sce.trustAsHtml(val);
			};
		})
        .filter('memberAvatar', function($sce) {
            return function(val) {
                if (angular.isUndefined(val)) {
                    return;
                } else if(val != null) {
					var typeimg = val.split("_");
					if(typeimg.length > 1)
						val =  '/assets/img/profiles/'+typeimg[0]+'/50.'+typeimg[1];
					else
						val =  '/assets/img/profiles/'+val+'/50.jpg';
                }
                return $sce.trustAsHtml(val);
            };
        })
		.filter('memberAvatarBig', function($sce) {
            return function(val) {
                if (angular.isUndefined(val) || val == null) {
                    return;
                } else {
					var typeimg = val.split("_");
					if(typeimg.length > 1)
						val =  '/assets/img/profiles/'+typeimg[0]+'/170.'+typeimg[1];
					else
						val =  '/assets/img/profiles/'+val+'/170.jpg';
                }
                return $sce.trustAsHtml(val);
            };
        })
        .filter('memberRoles', ['MEMBER_ROLES', function(a) {
            return function(input, memberShips) {
                var ms = _.find(memberShips, {idMember: input});
                return ms ? a[ms.memberType] : a['normal'];
            }
        }])
		.filter('newline', function () {
			return function( val ) {
				if( angular.isDefined( val ) ) {
					val = val.split("<br/>").join("\n");
					val = val.split("<br>").join("\n");
					return val;
				}
			}
		})
		.filter('newlineHtml', function () {
			return function( val ) {
				if( angular.isDefined( val ) ) {
					val = val.split("\n").join("<br/>");
					val = val.split("/n").join("<br/>")
					return val;
				}
			}
		})
		.filter('notnewline', function () {
			return function( val ) {
				if(angular.isDefined(val)) {
					val = val.split("<br/>").join(" ");
					val = val.split("<br>").join(" ");
					return val
				}
			}
		})
		.filter('convertbr', function() {
			return function( val ) {
				if( angular.isDefined( val ) ) {
					return val.split("/n").join("<br/>");
				}
			}
		})
		.filter('searchCardRelation', function() {
			return function($card, $filter) {
				return _.filter($card, function($v) {
					if($v.id == $filter || $v.displayName.indexOf($filter) > -1 ) {
						return $v;
					}
					return false;
				})
			}
		})
		.filter('messengernotify',['$rootScope', '$filter', function(a, b) {
			return function ($models) {
				var $areaName = function ($type) {
					if(($type >= 1 && $type <= 3) || $type == 25){
						return 'Team';
					} else if($type >= 4 && $type <= 7){
						return 'Board';
					} else if(($type >= 8 && $type <= 14) || $type == 18) {
						return 'Card';
					} else if($type >= 15 && $type <= 17) {
						return 'List';
					} else if($type >= 19 && $type <= 21) {
						return 'Checklist';
					} else if($type >= 22 && $type <= 24) {
						return 'Checklist item';
					}
				}
				var getBoard = function($modelobject) {
					var $type = $modelobject.type;
					if(($type >= 1 && $type <= 3) || $type == 25) {
						return  {data:$modelobject.data,link:"#/o/"+$modelobject.data.name+"/members"};
					} else if($type >= 4 && $type <= 7) {
						return  {data:$modelobject.data,link:"#/b/"+$modelobject.data.id+"/"+$modelobject.data.name};
					} else if (( $type >= 8 && $type <= 14 ) || $type == 18) {
						return  { data:$modelobject.data.dataBoard, linkCard: "#/b/" + $modelobject.data.dataBoard.id + "/"+$modelobject.data.dataBoard.name+"?card=" + $modelobject.data.id,link:"#/b/" + $modelobject.data.dataBoard.id + "/" + $modelobject.data.dataBoard.name };
					} else if (( $type >= 19 && $type <= 24 )) {
						return  { data:$modelobject.data.dataBoard, linkCard: "#/b/" + $modelobject.data.dataBoard.id + "/"+$modelobject.data.dataBoard.name+"?card=" + $modelobject.data.cardId,link:"#/b/" + $modelobject.data.dataBoard.id + "/" + $modelobject.data.dataBoard.name };
					} else if ($type != 0 && angular.isDefined( $modelobject.data.dataBoard )){
						return  {data:$modelobject.data.dataBoard,link:"#/b/"+$modelobject.data.dataBoard.id+"/"+$modelobject.data.dataBoard.name};
					}
					return;
				}
				angular.forEach($models, function($model, $key) {
					var temp = ""; var $link = "";
					var $getBoard  = getBoard($model);
					if($getBoard) {
						var $board 		= $getBoard.data;
						var $link 		= $getBoard.link;
						var $linkcard 	= (angular.isDefined($getBoard.linkCard)) ? $getBoard.linkCard : $getBoard.link;
						var $MemberName = (angular.isDefined($model.effectMember) && $model.effectMember.id != a.appAssets.id)?$model.effectMember.displayName:"you";
						var $type = $model.type;
						var $displayName = "";
						// filter remove tag in Name <br/>
						if(angular.isDefined($model.data.displayName)) {
							$displayName = b("notnewline")($model.data.displayName);
						} else if(angular.isDefined($model.cardDisplayName)) {
							$displayName = b("notnewline")($model.data.cardDisplayName);
						} else {
							$displayName = b("notnewline")($model.data.name);
						}
						switch($model.type) {
							case 0:	break;
							
							case 1: case 4: case 8: // team, board ,card add member 
								temp = $model.members.displayName + " added <span> " + $MemberName + " </span> to the  " + $areaName($model.type) + " <a  href='" + $linkcard + "'><span> " + $displayName + "</span></a>";
							break;
							
							case 2: case 5: case 9: // team, board, card remove member 	
								temp = $model.members.displayName + " removed <span>" + $MemberName + "</span> from the " + $areaName($model.type) + " <a href='" + $link + "'><span>" + $displayName + "</span></a>";
							break;
							case 3: case 6:  // team, board has role member 
								temp = $model.members.displayName + " made <span>" + $MemberName + "</span> an <span>" + $model.data.datapost.type + "</span> of the " + $areaName($model.type) + " <a href='" + $linkcard + "'><span>" + $displayName + "</span></a>";
							break;
							case 7: // board close open 			
								var $statusBoard = ($model.data.datapost.closed == 1) ? "closed" : "open";
								temp = $model.members.displayName + " " + $statusBoard + " the " + $areaName($model.type) + " <a href='" + $link + "'><span>" + $displayName + "</span></a>";
							break;
							case 10: 
								temp = $model.members.displayName + "  commented on the card <a  href='" + $linkcard + "'><span>" + $displayName + "</span></a> on <a href='" + $link + "'><span>" + $displayName + "</span></a> <div class='notifi-item-comment'>" + $model.data.datapost.content + "</div>";
							break;
							case 11: // close open  card
								var $statusBoard = ($model.data.datapost.closed == 1) ? "closed" : "open";
								temp = $model.members.displayName + " " + $statusBoard + " the " + $areaName($model.type) + " <a  href='" + $linkcard + "'><span>"+ $displayName + "</span></a> in board <a href='" + $link + "'><span>" + $board.displayName + "</span></a>";
							break;
							
							case 12: // create card
								temp = $model.members.displayName + " created " + $areaName($model.type) + " <a href='" + $linkcard + "'><span>" + $displayName + "</span></a> in board <a href='" + $link + "'><span>" + $board.displayName + "</span></a>";
							break;
							
							case 13: // remove card
								temp = $model.members.displayName + " deleted " + $areaName($model.type) + " <a href='" + $link + "'><span>" + $displayName + "</span></a> in board <a href='" + $link + "'><span>" + $board.displayName + "</span></a> now";
							break;
							
							case 14: // sort card 
								var $dataparams = $model.data.dataParams;
								if(angular.isUndefined($dataparams.listNew.length)) {
									temp = $model.members.displayName + " moved " + $areaName($model.type) + " <a href='"+$linkcard+"'> <span> " + $displayName + " </span></a> from list <a href='" + $link + "'><span>" + $dataparams.listOld.name + "</span></a>  to list <a href='" + $link + "'><span>" + $dataparams.listNew.name + "</span></a> in board <a href='" + $link + "'> <span> " + $board.displayName + "</span> </a>";
								} else {
									temp = $model.members.displayName + " changed position " + $areaName($model.type) + " <a href='" + $linkcard + "'>  <span> " + $displayName + " </span></a> from position <span>" + $dataparams.listOld.pos + "</span>  to position <span>" + $model.data.pos + "</span> of list <span>" + $dataparams.listOld.name + "</span> in board <a href='" + $link + "'> <span> "+ $board.displayName + "</span></a>";
								}
							break;
							case 15: // create list
								temp = $model.members.displayName + " created " + $areaName($model.type) + "  <a href='" + $linkcard + "'><span>" + $model.data.name + "</span></a> in board <a href='" + $link + "'><span>" + $board.displayName + "</span></a> ";
							break;
							
							case 16: // close or open  list
								var $statusBoard = ($model.data.datapost.closed == 1)?"closed":"open";
								temp = $model.members.displayName + " " + $statusBoard + " the " + $areaName($model.type) + " <a href='" + $link + "'><span>" + $model.data.name + "</span></a> in board <a href='" + $link + "'><span>" + $board.displayName + "</span></a>";
							break;
							
							case 17: // sort list
								var $dataparams = $model.data.dataParams;
								temp = $model.members.displayName +" changed position "+ $areaName($model.type) + " <span> " + $model.data.name + " </span> from position <span>" + ($dataparams.listOld.pos+1) + "</span>  to position <span>" + ($model.data.pos+1) + "</span> in board <a href='" + $link + "'> <span> " + $board.displayName + "</span></a>";
							break;
							
							case 18: // add or changed due
								var $dataparams = $model.data.dataParams;
								var $due = b('date')($model.data.datapost.due,'dd/MM/yyyy H:mm:ss');
								temp = $model.members.displayName + " " + $dataparams.action + " due ("+$due+") " + $areaName($model.type) + " <a href='"+$linkcard + "'><span>" + $displayName + "</span></a> in board <a href='" + $link + "'><span>" + $board.displayName + "</span></a>";
							break;
							
							case 19: case 20: case 21: case 22: case 23: case 24: // created and edit and delete 19 -> 21 checklist   22->24 checklist item-comment
								var $dataparams = $model.data.dataParams; 
								temp = $model.members.displayName + " " + $dataparams.action + " " + $areaName($model.type) + " <span> " + $model.data.name + "</span> of card <a  href='" + $linkcard + "'> <span> " + $displayName + "</span>" + "</a> in board <a href='" + $link + "'><span>" + $board.displayName + "</span></a>"
							break;
							
							case 25: // delete organization
								temp = $model.members.displayName + " deleted organization  <span>" + $displayName + "</span>";
							break;
						}
					}
					$models[$key].temp = temp;
				})
				return $models;
			}
		}]);		
})(window, window.angular);