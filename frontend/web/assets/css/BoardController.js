/**
 * Author: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
app.controller('BoardController', ['$rootScope', '$scope', 'AppService', 'Socket', 'toaster', 'activitys', '$filter', 'MEMBER_ROLES', 'Upload', '$timeout', 'ngMeta', '$location', 'orderByFilter', 'Notification',function(a, b, c, d, e, f, g, h, i, k, m, n, o, l) {

    b.boardPageAssets = [];
    b.prefsBackground  = [
		{background: 'black', backgroundColor: '#333'},
		{background: 'white', backgroundColor: '#fff'},
        {background: 'blue', backgroundColor: '#0079BF'},
        {background: 'yellow', backgroundColor: '#D29034'},
        {background: 'green', backgroundColor: '#519839'},
        {background: 'red', backgroundColor: '#B04632'},
        {background: 'purple', backgroundColor: '#89609E'},
        {background: 'pink', backgroundColor: '#CD5A91'},
        {background: 'lime', backgroundColor: '#4BBF6B'},
        {background: 'sky', backgroundColor: '#00AECC'},
        {background: 'grey', backgroundColor: '#838C91'}
    ];
    b.MEMBER_ROLES = h;
	c.boards.getBoard();
	/**
	 * update date lastUpdated of CARD
	 */
	var setLastUpdated = function($idCard) {
		if(angular.isDefined(b.cardDetail) && b.cardDetail.id == $idCard) {
			var d = new Date();
			
			var $day = d.getDate();
			$day  = ($day.toString().length > 1)? $day : "0" + $day.toString();
			
			var $month = d.getMonth()+1;
			$month  = ($month.toString().length > 1)? $month :"0" + $month.toString();
			
			var $hours = d.getHours();
			$hours  = ($hours.toString().length > 1)? $hours : "0" + $hours.toString();
			
			var $minutes = d.getMinutes();
			$minutes  = ($minutes.toString().length > 1)? $minutes : "0" + $minutes.toString();
			
			b.cardDetail.lastUpdated = $month + "/" + $day + "/" +  d.getFullYear() + " " + $hours + ":" + $minutes
		}
	}
	
	a.$on("reloadData",function() {
		/*check start Count*/
		var $idBoardStar = _.map(a.appAssets.boardStars,function(item) {
			return parseInt(item.idBoard,10);
		});
		b.boardPageAssets.starsCount = 0;
		if($idBoardStar) {
			angular.forEach($idBoardStar,function($idBoard) {
				if(b.boardPageAssets.id == $idBoard) {
					b.boardPageAssets.starsCount = 1;
				}
			});
		}
	})	
	
    b.input = {};
	/** open close board **/
	a.$on("E:OPEN_OR_CLOSE_BOARD", function(event, data) {
		if(data.id == b.boardPageAssets.id) {
			b.boardPageAssets = angular.extend(data, b.boardPageAssets);
		}
	})
	/**
	 * update badges card
	 */
	var accessBadgest  = function ($cardDetail) {
		if($cardDetail.idBoard  == b.boardPageAssets.id) {
			var $badges = {
				attachments: 0,
				checkItems: 0,
				checkItemsChecked: 0,
				comments: 0,
				description: 0,
				due: 0,
				subscribed: 0,
				viewingMemberVoted:0 ,
				votes:0,
			};
			var $countBy = 0;
			angular.forEach(b.boardPageAssets.cards, function($card,$keyCard) {
				if( $card.id == $cardDetail.id ) {
					/* update badges item*/
					// count checkeditem and count checkitem 
					angular.forEach( $cardDetail.checklists, function(checklist) {
						$badges.checkItems += checklist.checkItems.length;
						$countBy = _.countBy(checklist.checkItems,function($checkitem) {
							return $checkitem.state == 1?'checked':'nochecked'
						});
						$badges.checkItemsChecked += ($countBy.checked)?$countBy.checked:0;
					});
					$badges.attachments += $cardDetail.attachments.length; 
					$badges.comments    += $cardDetail.comments.length; 
					angular.extend( b.boardPageAssets.cards[$keyCard].badges , $badges);
				}
			});
		}
	}	
	var setCardBadgest = function ($cardDetail) {
		accessBadgest($cardDetail);
		var $WSCard = {
			id			: $cardDetail.id,
			idBoard		: $cardDetail.idBoard,
			checklists	: $cardDetail.checklists,
			attachments	: $cardDetail.attachments,
			comments	: $cardDetail.comments,
		}
		d.emit('WE:UPDATE_CARD_BADGES', $WSCard);
	}
	
	/** 
	   Table Notification Comments compare data with Table Comments to retrieve data. 
	   This will be displayed in card detail 
	 */
	var reloadCommentCard = function() {
		if(angular.isDefined(b.cardDetail)) {
			b.activitiesCard = b.cardDetail.notifications;
			angular.forEach(b.activitiesCard,function($v,$k){
				if($v.type == 10 && $v.name == 'card') { // add card,
					b.activitiesCard[$k].comment = _.find(b.cardDetail.comments,{id:parseInt($v.data.idComment)});
				}
			});
		}
	};
	
	/*socket badget*/
	d.on('WE:UPDATE_CARD_BADGES', function($cardDetail) {
		accessBadgest($cardDetail);
	});
	
	
	/**
	 * Get notificaiton
	 */
	var getNotification = function (){
		var listMember = _.pluck(b.boardPageAssets.memberShips,'idMember');
		/* get notification socket*/
		d.emit('WE:GET_NOTIFICATIONS',{listMember:listMember});			
		/* get notification board no socket */
		l.getNotificationBoard(b.boardPageAssets.id, 'board').then( function(response) {
			/* add notification in board now*/
			var board = [];
			var idNotifications = _.pluck(b.boardPageAssets.notifications,'id');
			angular.forEach(response, function($notify,$k) {
				if(idNotifications.indexOf($notify.id) == -1) {
					b.boardPageAssets.notifications.push($notify);
				}
			});

			b.boardPageAssets.notifications = b.boardPageAssets.notifications.sort(function(a,b) {
				return b.id - a.id;
			});
			/* addc notification in card */
			if(angular.isDefined(b.cardDetail)) {
				var listNotificationsCard = _.filter(response , function($notify) {
					return (($notify.name != 'board') && angular.isDefined($notify.data.id) && parseInt($notify.data.id) == b.cardDetail.id);
				});

				var idNotificationsCards = _.pluck(b.cardDetail.notifications,'id');
				
				angular.forEach(listNotificationsCard , function($notify) {
					if(idNotificationsCards.indexOf($notify.id) == -1) {
						b.cardDetail.notifications.push($notify);
					}
				});
				
				b.cardDetail.notifications = b.cardDetail.notifications.sort(function(a,b) {
					return b.id  - a.id;
				});
				
			}
			reloadCommentCard();
		});
	}
	/** close Board **/
	a.$on('E:OPEN_CLOSE_BOARD', function(event, data) {
		b.boardPageAssets = angular.extend(b.boardPageAssets,data);
		getNotification();
	})
    /**
     * BOARD EVENT
     */
    a.$on('E:BOARD_GETTED', function(event, data) {
        b.boardPageAssets = data;
		/*meta tags*/
		m.setTitle(data.displayName);
		/*end meta tags*/
        a.configs = {
            backgroundColor: data.prefs['backgroundColor'],
            bodyClass: 'body-board-view'
        };
        angular.forEach(b.boardPageAssets.cards, function(card) {
            card.labels = _.filter(b.boardPageAssets.labels, function(labelItem,$key) {
                return card.idLabels.indexOf(labelItem.id) !== -1;
            });
            card.attachmentSelected = _.find(card.attachments, {id: card.idAttachmentCover});
        });
		
        angular.forEach(b.boardPageAssets.lists, function(list) {
            list.cards = _.where(b.boardPageAssets.cards, {idList: list.id});
            if (angular.isUndefined(list.cards)) {
                list.cards = [];
            }
        });
		
		/*get star count */
		var $idBoardStar = _.map(a.appAssets.boardStars,function(item) {
			return parseInt(item.idBoard,10);
		});
		b.boardPageAssets.starsCount = 0;
		if($idBoardStar) {
			angular.forEach($idBoardStar,function($idBoard) {
				if(b.boardPageAssets.id == $idBoard) {
					b.boardPageAssets.starsCount = 1;
				}
			});
		}
		/* count notfication */
		b.notifycount = b.boardPageAssets.notifications.length;
		reloadCommentCard();
		// list board 
		b.boardList		= b.boardPageAssets.lists;
		//calenderboard
		getEventSources();
    });	
	var getEventSources = function() { //reload event card calender
		b.eventSources = _.filter(b.boardPageAssets.cards, function($card) {
			$card.start =  new Date($card.due);
			return $card.due != null && $card.due != "" && $card.due != 0 && $card.due > 0 && $card.closed == 0;
		});
		
		a.$broadcast("reploadCalendar",b.eventSources);
		b.eventSources = [b.eventSources];
		_.defer(function() {$(".addCardCalendar").hide()});
	}
	a.$on('E:GET_NOTIFICATIONS_BOARD_AND_CARD', function(event,data) {
		// get nofication by socket*/
		if(angular.isDefined(b.cardDetail)) 
		{
			var $listNotificationData = _.filter (data, function($v,$k) {
				return (($v.name == 'card' || $v.name == 'checklist' || $v.name == 'checklistitem') && parseInt($v.data.id) == b.cardDetail.id);
			});
				if($listNotificationData.length > 0)
				{
					var $listIdNotifyNotIn = _.pluck(b.cardDetail.notifications,'id');
					
					var $listNotificationCardDetail =  b.cardDetail.notifications;
					angular.forEach($listNotificationData, function($notification){
						if($listIdNotifyNotIn.indexOf(parseInt($notification.id)) == -1)
							$listNotificationCardDetail.push($notification);
					});
					
					b.cardDetail.notifications = $listNotificationCardDetail.sort(function(a,b)
					{
						return b.id - a.id;
					});
				}
				reloadCommentCard();
			}
			var notificationBoard = [];
			if(angular.isDefined(data)) {
				notificationBoard =  _.filter (data, function($v,$k) {
					return (($v.name == 'board' || $v.name == 'card' || $v.name == 'checklist' || $v.name == 'checklistitem') && angular.isDefined($v.data.dataBoard) && parseInt($v.data.dataBoard.id) == b.boardPageAssets.id);
				});
			}
			if(notificationBoard.length > 0) {

				var $listIdNotification = _.pluck(b.boardPageAssets.notifications,'id');
				
				angular.forEach(notificationBoard,function($v,$k){
					if($listIdNotification.indexOf($v.id) == -1) {
						b.boardPageAssets.notifications.push($v);
					}
				});
				
				b.boardPageAssets.notifications = b.boardPageAssets.notifications.sort(function(a,b) {
					return  b.id - a.id
				});
			}
	});
	
	d.on('WE:BOARD_UPDATED',function(data) {
		if(b.boardPageAssets.id == data.id){
			b.boardPageAssets = angular.extend(b.boardPageAssets,data);
		}

		angular.forEach(a.appAssets.boards,function($v,$k){
			if($v.id == data.id){
				a.appAssets.boards[$k] = angular.extend(a.appAssets.boards[$k],data);
			}
		});
		
	});
	
    d.on('WE:BOARD_BACKGROUND_CHANGED', function(data) {
        var prefs = data[1];
        if (angular.isDefined(prefs)) {
            a.configs = {
                backgroundColor: prefs.backgroundColor,
                bodyClass: 'body-board-view'
            };
        }
        var board = _.find(b.boardPageAssets.boards, {id: data[0]});
        if (angular.isDefined(board)) {
            board.prefs = angular.extend(board.prefs, data[1]);
        }
        //b.boardChangedBackgroundTime = (new Date()).toISOString();
        //b.member = {
        //    id: a.appAssets.id,
        //    username: a.appAssets.username,
        //    displayName: a.appAssets.displayName,
        //    initialsName: a.appAssets.initialsName,
        //    avatarHash: a.appAssets.avatarHash
        //};
        //var temp = g(f.members(b.member))(b);
        //$('body').find('#activity').append(temp);
    });
	
    d.on('WE:ADDED_MEMBER_TO_BOARD', function(member) {
        if (angular.isDefined(b.boardPageAssets.members)) {
            b.boardPageAssets.members.push(member);
        }
        if (angular.isDefined(b.boardPageAssets.memberShips)) {
            b.boardPageAssets.memberShips.push({
                idMember: member.id,
                idBoard: b.boardPageAssets.id,
                memberType: 'normal'
            });
        }
    });
	
	d.on('WE:DELETED_CART_ATTACHMENT',function(data){
		b.boardPageAssets.cards = _.filter(b.boardPageAssets.cards, function($card){
			$card.attachments = _.filter($card.attachments,function($attachment){
				return $attachment.id != data.idAttachment;
			});
			return $card;
		});
		angular.forEach(b.boardPageAssets.lists,function($vlist,$klist){
			angular.forEach($vlist.cards,function($vcard,$kcard){
				if(b.boardPageAssets.lists[$klist].cards[$kcard].attachments){
					if(b.boardPageAssets.lists[$klist].cards[$kcard].attachments.length !== 1){
						b.boardPageAssets.lists[$klist].cards[$kcard].attachments = _.filter(b.boardPageAssets.lists[$klist].cards[$kcard].attachments, function(attachment) {
							return attachment.id !== data.idAttachment;
						});
					} else if(b.boardPageAssets.lists[$klist].cards[$kcard].attachments[0].id == data.idAttachment) {
						b.boardPageAssets.lists[$klist].cards[$kcard].attachments = [];
					}
				}
				if(!angular.isUndefined($vcard.attachmentSelected) && $vcard.attachmentSelected.id == data.idAttachment){
					b.boardPageAssets.lists[$klist].cards[$kcard].attachmentSelected = undefined;
				}
			});
		});
	});
	
    d.on('WE:REMOVED_MEMBER_OF_BOARD', function(idMember) {
        if (angular.isDefined(b.boardPageAssets.members)) {
            b.boardPageAssets.members = _.filter(b.boardPageAssets.members, function(member) {
                return member.id !== idMember;
            });
        }
        if (angular.isDefined(b.boardPageAssets.memberShips)) {
            b.boardPageAssets.memberShips = _.filter(b.boardPageAssets.memberShips, function(memberShips) {
                return memberShips.idMember !== idMember;
            });
        }
    });
	
    d.on('WE:BOARD_MEMBER_ROLE_CHANGED', function(data) {
        var memberShips = _.find(b.boardPageAssets.memberShips, {idMember: data[0]});
        if (angular.isDefined(memberShips)) {
            memberShips.memberType = data[1];
        }
    });
	
    /**
     * LISTS EVENT
     */
    d.on('WE:LISTS_CREATED', function(data) {
		if(data.idBoard === b.boardPageAssets.id){
			angular.forEach(data.cards,function($v,$k){
				$v = data.cards[$k] = setLabelsCard($v); // get label and selected 
				b.boardPageAssets.cards.push($v);
			});
            b.boardPageAssets.lists.push(data);
			
		}
		a.appAssets.boards = _.filter(a.appAssets.boards,function($board){ // listCount root + 1
			if($board.id == data.idBoard)
				$board.listCount = parseInt($board.listCount) + 1;
			return $board;
		});
    });

	d.on('WE:LISTS_MOVE',function(param){
		var data = param.data;
		var listdetail = param.listdetail;	
		angular.forEach(b.boardPageAssets.lists, function(list, key) {
			if (list.id == data.id) {
				b.boardPageAssets.lists[key] = angular.extend(b.boardPageAssets.lists[key], data);
			}
		});
		a.appAssets.boards = _.filter(a.appAssets.boards,function(board){
			if(data.idBoard == board.id) {
				board.listCount = parseInt(board.listCount)+1;
			} else if (listdetail.idBoard == board.id) {
				board.listCount = (parseInt(board.listCount) == 1)?0:parseInt(board.listCount)-1;
			}
			return board;
		});
		if(data.idBoard != b.boardPageAssets.id) { // filter when list not in board
			b.boardPageAssets.lists = _.filter(b.boardPageAssets.lists,function(list){
				return list.idBoard == b.boardPageAssets.id;
			});
		} else if (data.idBoard == b.boardPageAssets.id) {
			angular.forEach(listdetail.cards,function($v){
				b.boardPageAssets.cards.push($v);
			});
			listdetail = angular.extend(listdetail,data);
			b.boardPageAssets.lists.push(listdetail);
		}
	});
	
    d.on('WE:LISTS_UPDATED', function(data) {
		angular.forEach(b.boardPageAssets.lists, function(list, key) {
			if (list.id == data.id) {
				b.boardPageAssets.lists[key] = angular.extend(b.boardPageAssets.lists[key], data);
			}
		});
    });

    d.on('WE:LISTS_SORTED', function(data) {
        var sort = data.sort;
        angular.forEach(b.boardPageAssets.lists, function(list, key) {
            angular.forEach(sort, function(listId, pos) {
                 if (listId == list.id) {
                     b.boardPageAssets.lists[key].pos = pos;
                 }
            });
        });
		b.boardPageAssets.lists = o(b.boardPageAssets.lists,"pos",false);
    });
	
    d.on('WE:CARD_SORTED', function(response) {
		var data = response.data;
        var sort = data.sort;
		var paramsSort = response.paramsSort;
		if(paramsSort.idListold == paramsSort.idListnew) { // sort in list
			angular.forEach(b.boardPageAssets.lists, function(list, key) {
				if (data.idList == list.id) {
					if (angular.isUndefined(b.boardPageAssets.lists[key].cards)) {
						return;
					}
					var cardsOfCurrentList = b.boardPageAssets.lists[key].cards;
					angular.forEach(cardsOfCurrentList, function(card, keyCard) {
						b.boardPageAssets.lists[key].cards[keyCard].pos = sort.indexOf(card.id);
					});
					b.boardPageAssets.lists[key].cards = o(b.boardPageAssets.lists[key].cards,"pos",false);
				}
			});
		} else {
			var $listOld = _.find(b.boardPageAssets.lists,{id:parseInt(paramsSort.idListold)});
			if(angular.isDefined($listOld) && $listOld) {
				var $cardOld = _.find($listOld.cards,{id:parseInt(paramsSort.idCard)});
				if(angular.isDefined($cardOld) && $cardOld) {
					angular.forEach(b.boardPageAssets.lists,function($list,$key){
						if($list.id == $listOld.id) {
							b.boardPageAssets.lists[$key].cards = _.filter($list.cards,function($card){
								return $card.id != $cardOld.id;
							});
						} else if($list.id == paramsSort.idListnew) {
							b.boardPageAssets.lists[$key].cards.push($cardOld);
							angular.forEach(b.boardPageAssets.lists[$key].cards, function(card, keyCard) {
								b.boardPageAssets.lists[$key].cards[keyCard].pos = sort.indexOf(card.id);
							});
							b.boardPageAssets.lists[$key].cards = o(b.boardPageAssets.lists[$key].cards,"pos",false);
						}
					});
				}
			}			
		}
    });
	
	d.on('WE:UPDATE_CARD_ALL',function(response){
		getUpdateCard(response);
		getEventSources();
	});
	
    /**
     * CARD EVENT
     */
    d.on('WE:CARD_CREATED', function(data) {
        var listsOfCard = _.find(b.boardPageAssets.lists, {id: data.idList});
		if (angular.isUndefined(listsOfCard)) {
			listsOfCard = {};
            listsOfCard.cards = [];
		} else if ( angular.isUndefined(listsOfCard.cards) ) {
			listsOfCard.cards = [];
		}
        listsOfCard.cards.push(data);
        b.boardPageAssets.cards.push(data);
		if(data.idBoard == b.boardPageAssets.id) {
			getEventSources();
		}
    });

    d.on('WE:CARD_UPDATED', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
		if(angular.isDefined(cardCurrent)) {
			cardCurrent = angular.extend(cardCurrent, data[1]);
			/*parent CARD*/
			if(angular.isDefined(b.cardDetail)) {
				angular.extend(b.cardDetail, data);
				b.cardDetail.parentCard = _.filter(b.boardPageAssets.cards, function($card) {
					return b.cardDetail.parentId.indexOf($card.id) > -1;
				});
			}
			getEventSources();
		}
		setLastUpdated(data[0]);		
    });
	
	d.on('WE:CARD_DELETE', function(data) {
		b.boardPageAssets.cards = _.filter(b.boardPageAssets.cards, function($card){
			return $card.id != data.id;
		});
		getEventSources();
	});
	
	d.on('WE:COPY_CARD', function(data) {
		if(b.boardPageAssets.id == data.idBoard) {
			data = setLabelsCard(data);
			b.boardPageAssets.cards.push(data);
			var $listCards = _.find(b.boardPageAssets.lists,{id: data.idList});
			if(angular.isDefined($listCards)) {
				$listCards.cards.push(data);
				$listCards.cards.sort(function(a, b) {
					return a.pos - b.pos;
				})
			}
		}
	});
	
	d.on('WE:CARD_MOVE', function(data) {
		var $card = _.find(b.boardPageAssets.cards, {id: parseInt(data.id)});
		if(angular.isDefined($card)) {
			$card = angular.extend($card, data);
			getUpdatelist();
		} else if(b.boardPageAssets.id == data.idBoard){
			b.boardPageAssets.cards.push(data);
			getUpdatelist();
		}		
	});
	
    d.on('WE:CARD_LABELS_CREATED', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
            cardCurrent.idLabels.push(data[1].id);
            cardCurrent.labels.push(data[1]);
        }
        b.boardPageAssets.labels.push(data[1]);
		setLastUpdated(data[0]);
    });


    d.on('WE:CARD_IDLABELS_ADDED', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        var label = _.find(b.boardPageAssets.labels, {id: data[1]});
        if (angular.isDefined(cardCurrent) && angular.isDefined(label)) {
            cardCurrent.idLabels.push(data[1]);
			if(angular.isUndefined(cardCurrent.labels)) {
				cardCurrent.labels = [];
			}
            cardCurrent.labels.push(label);
        }
		setLastUpdated(data[0]);
		getEventSources();
    });
    d.on('WE:CARD_IDLABELS_DELETED', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
            cardCurrent.idLabels = _.filter(cardCurrent.idLabels, function(idLabel) {
                return idLabel !== data[1];
            });
            cardCurrent.labels = _.filter(cardCurrent.labels, function(label) {
                return label.id !== data[1];
            });
        }
		setLastUpdated(data[0]);
    });
	
    d.on('WE:ADDED_MEMBER_TO_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent.members)) {
            cardCurrent.members.push(data[1]);
        }
		setLastUpdated(data[0]);
    });
	
    d.on('WE:REMOVED_MEMBER_OF_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        cardCurrent.members = _.filter(cardCurrent.members, function(member) {
            return member.id !== data[1];
        });
		setLastUpdated(data[0]);
    });

    d.on('WE:ADDED_CHECKLISTS_TO_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
			if(angular.isUndefined( cardCurrent.checklists)) {
				 cardCurrent.checklists = [];
			}
            cardCurrent.checklists.push(data[1]);
        }
		setLastUpdated(data[0]);
    });

    d.on('WE:UPDATED_CHECKLISTS_OF_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
            var currentChecklist = _.find(cardCurrent.checklists, {id: data[1]});
            if (angular.isDefined(currentChecklist)) {
                currentChecklist = angular.extend(currentChecklist, data[2]);
            }
        }
		setLastUpdated(data[0]);
    });
	
    d.on('WE:DELETED_CHECKLISTS_OF_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
            cardCurrent.checklists = _.filter(cardCurrent.checklists, function(checklist) {
                return checklist.id !== data[1];
            });
        }
		setLastUpdated(data[0]);
    });

    d.on('WE:ADDED_CHECKLISTS_ITEMS_TO_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
			if(angular.isUndefined(cardCurrent.checklists)) {
				cardCurrent.checklists  = [];
			}
            cardCurrent.checklists.push(data[1]);
        }
        var currentChecklist = _.find(cardCurrent.checklists, {id: data[1]});
        if (angular.isDefined(currentChecklist)) {
			if(angular.isUndefined(currentChecklist.checkItems)) {
				currentChecklist.checkItems = [];
			}
            currentChecklist.checkItems.push(data[2]);
        }
		setLastUpdated(data[0]);
		/*update count checkitem 
		angular.forEach(b.boardPageAssets.cards,function(card,$key) {
			if(card.id == data[0]) {
				b.boardPageAssets.cards[$key].badges.checkItems += 1;
			}
		});*/
    });
	
    d.on('WE:UPDATED_CHECKLISTS_ITEMS_OF_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
            var currentChecklist = _.find(cardCurrent.checklists, function(checklist) {
                return angular.isDefined(_.find(checklist.checkItems, {id: data[1]}));
            });
            if (angular.isDefined(currentChecklist)) {
                var currentChecklistItem = _.find(currentChecklist.checkItems, {id: data[1]});
                if (angular.isDefined(currentChecklistItem)) {
                    currentChecklistItem = angular.extend(currentChecklistItem, data[2]);
                }
            }
        }
		setLastUpdated(data[0]);
    });
	
    d.on('WE:DELETED_CHECKLISTS_ITEMS_OF_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
            var currentChecklist = _.find(cardCurrent.checklists, {id: data[1]});
            if (angular.isDefined(currentChecklist)) {
                currentChecklist.checkItems = _.filter(currentChecklist.checkItems, function(checklistItem) {
                    return checklistItem.id !== data[2];
                });
            }
        }
		setLastUpdated(data[0]);
    });
	
    d.on('WE:ADDED_COMMENTS_TO_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
			if(angular.isUndefined( cardCurrent.comments)) {
				 cardCurrent.comments = [];
			}
            cardCurrent.comments.push(data[1]);
            cardCurrent.comments = _.sortBy(cardCurrent.comments, function(comment) {
                var date = new Date(comment.addedDate).getTime();
                return - date;
            });
			reloadCommentCard();
        }
    });
	
    d.on('WE:UPDATED_COMMENTS_OF_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
            var commentCurrent = _.find(cardCurrent.comments, {id: data[1]});
            if (angular.isDefined(commentCurrent)) {
                commentCurrent = angular.extend(commentCurrent, data[2]);
            }
        }
    });
	
    d.on('WE:DELETED_COMMENTS_OF_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
            cardCurrent.comments = _.filter(cardCurrent.comments, function(comment) {
                return comment.id !== data[1];
            });
			reloadCommentCard();
        }
    });
	
    d.on('WE:UPLOADED_FILE_TO_CARD', function(data) {
        var cardCurrent = _.find(b.boardPageAssets.cards, {id: data[0]});
        if (angular.isDefined(cardCurrent)) {
			cardCurrent.attachments.push(data[1]);
			var arrayselected = ['image/jpeg','image/jpg','image/png','image/bmp','image/gif'];
			if(arrayselected.indexOf(data[1].mimeType) > -1) {
				cardCurrent.attachmentSelected = data[1];
			}
            setLastUpdated(data[0]);
        }
    });
	d.on('WE:LIST_BACKGROUND_CHANGED',function (idList, data) {
		list  = _.find(b.boardPageAssets.lists,{id:idList});
		if(list) {
			list = angular.extend(list, data);
		}
	});
    /**
     *
     * LABELS EVENT
     */
	 
    d.on('WE:LABELS_UPDATED', function(data) {
        var label = _.find(b.boardPageAssets.labels, {id: data.id});
        label = angular.extend(label, data);
		setLastUpdated(data.idCard);
    });

    d.on('WE:LABELS_DELETED', function(id) {
        angular.forEach(b.boardPageAssets.cards, function(card) {
            card.idLabels = _.filter(card.idLabels, function(idLabel) {
                return idLabel !== id;
            });
            card.labels = _.filter(card.labels, function(label) {
                return label.id !== id;
            });
        });
        b.boardPageAssets.labels = _.filter(b.boardPageAssets.labels, function(label) {
            return label.id !== id;
        });
    });
	/* CARD_IMAGE_COVER */
	d.on('WE:CARD_IMAGE_COVER', function(data) {
		var $cardCover = _.find(b.boardPageAssets.cards, {id: data.idCard});
		$cardCover.attachmentSelected = data.attachmentSelected;
		setLastUpdated(data.idCard);
	});
	
    var alertError = function(errors) {
            e.clear();
            if (angular.isUndefined(errors.status)) {
                angular.forEach(errors, function (error) {
                    e.pop('error', "Field: " + error.field, error.message);
                });
            }
    };
    /**
     * Create new board
     * @param model
     */
    b.createBoard = function(model) {
        c.boards.create(model).then(function(response) {
			a.appAssets.push(response);
			b.boardPageAssets.push(response);
            a.$state.go(response._links.href);
        });
    };
    /**
     * Update new board
     * @param model
     */
    b.updateBoard = function(model) {
        var defaults = {

        };
        c.boards.update(b.boardPageAssets.id, model).then(function(response) {
			if(model.backgroundColor) {
				a.configs = {
					backgroundColor: model.backgroundColor,
					bodyClass: 'body-board-view'
				};
			} else {
				b.boardPageAssets = angular.extend(b.boardPageAssets,response);
				angular.forEach(a.appAssets.boards,function($v,$k){
					if($v.id == response.id){
						a.appAssets.boards[$k] = angular.extend(a.appAssets.boards[$k],response);
					}
				});
			}
			_.defer(function(){
				$('.modal-close').trigger("click");
			});
        });
    };
    /**
     * Change board background
     * @param model
     */
    b.changeBackground = function(model) {
      var defaults = {
            idMember: a.appAssets.id,
            type: 'prefs/background'
      };
      model = angular.extend(defaults, model, {});
       return !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.boards.update(b.boardPageAssets.id, model).then(function(response) {
           a.configs = {
                backgroundColor: model.backgroundColor,
                bodyClass: 'body-board-view'
           };
           d.emit('WE:BOARD_BACKGROUND_CHANGED', [b.boardPageAssets.id, model]);
           var board = _.find(a.appAssets.boards, {id: b.boardPageAssets.id});
           if (angular.isDefined(board)) {
               board.prefs = angular.extend(board.prefs, model);
           }
       });
    };
    /**
     * @desc add Member to board
     * @param member
     */
    b.addMemberToBoard = function(member) {
        return c.boards.model = b.boardPageAssets, c.boards.hasMemberInBoard(member.id) ? void 0 : c.boards.addMemberToBoard(member).then(function(response) {
            b.boardPageAssets.members.push(response);
            if (angular.isDefined(b.boardPageAssets.memberShips)) {
                b.boardPageAssets.memberShips.push({
                    idMember: member.id,
                    idBoard: b.boardPageAssets.id,
                    memberType: 'normal'
                });
            }
            if (angular.isDefined(b.organizationMembers)) {
                b.organizationMembers.members = _.filter(b.organizationMembers.members, function(members) {
                    return members.id !== member.id;
                });
                b.organizationMembers.memberShips = _.filter(response.memberShips, function(memberShips) {
                    return memberShips.idMember !== member.id;
                });
            }
			getNotification();
        });
    };

    /**
     * @desc remove member of board
     * @param idMember
     */
    b.removeMemberOfBoard = function(idMember) {
        var r, ms;
        function removeMember(idMember, redirect) {
            return c.boards.removeMemberOfBoard(idMember).then(function(response) {
                if (angular.isDefined(b.boardPageAssets.members)) {
                    b.boardPageAssets.members = _.filter(b.boardPageAssets.members, function(member) {
                        return member.id !== idMember;
                    });
                }
                if (angular.isDefined(b.boardPageAssets.memberShips)) {
                    b.boardPageAssets.memberShips = _.filter(b.boardPageAssets.memberShips, function(memberShips) {
                        return memberShips.idMember !== idMember;
                    });
                }
				/*notification*/
				var listMember = _.pluck(b.boardPageAssets.memberShips,'idMember');
				listMember.push(idMember);
				d.emit('WE:GET_NOTIFICATIONS',{listMember:listMember});
                if (redirect && a.appAssets.id == idMember) {
                    window.location.href = '/';
                }
            });
        };
        return c.boards.model = b.boardPageAssets, r = g('memberRoles')(a.appAssets.id, b.boardPageAssets.memberShips), ms = b.countByRoleMemberShips(), r === 'Admin' && angular.isDefined(ms.admin) && ms.admin < 2 && a.appAssets.id === idMember  ? e.pop('error', 'You can not change this because they must have at least one person as administrator.') : (r === 'Admin' ? removeMember(idMember) : (r === 'Normal' &&  g('memberRoles')(idMember, b.boardPageAssets.memberShips) === 'Normal' ? removeMember(idMember) : void 0));
    };

    b.addAllMemberToBoad = function(members)
    {
        if (angular.isArray(members)) {
            angular.forEach(members, function(member) {
                b.addMemberToBoard(member);
            });
        }
    };

    b.changeMemberRole = function(idMember, role) {
        var r, ms, mr;
        return c.boards.model = b.boardPageAssets, ms = b.countByRoleMemberShips(), r = g('memberRoles')(a.appAssets.id, b.boardPageAssets.memberShips), r === 'Admin' && angular.isDefined(ms.admin) && ms.admin < 2 && idMember == a.appAssets.id ? void 0 : (r === 'Admin' ? mr = angular.lowercase(g('memberRoles')(idMember, b.boardPageAssets.memberShips)) !== role ? c.boards.changeRole(idMember, role).then(function(response) {
                var memberShips = _.find(b.boardPageAssets.memberShips, {idMember: idMember});
                if (angular.isDefined(memberShips)) {
                    memberShips.memberType = role;
                }
                d.emit('WE:BOARD_MEMBER_ROLE_CHANGED', [idMember, role]);
				/*notification*/
				getNotification();
            }): void 0: void 0);
    };

    b.hasMemberInBoard = function(idMember) {
        return c.boards.model = b.boardPageAssets, c.boards.hasMemberInBoard(idMember);
    };

    b.countByRoleMemberShips = function() {
        return _.countBy(b.boardPageAssets.memberShips, function(memberShips) {return memberShips.memberType === 'admin' ? 'admin' : 'normal';});
    };

    b.getOrganizationMembers = function(idOrganization) {
        b.organizationMembers = [];
        c.organizations.getModel(idOrganization, {
            fields: 'id',
            expand: ['members(id.username.displayName.initialsName.avatarHash.bio.url.status.typeimg)', 'memberShips'].join(',')
        }).then(function(response) {
            var boardCurrentMemberList = _.pluck(b.boardPageAssets.members, 'id');
            b.organizationMembers.members = _.filter(response.members, function(member) {
               return !_.contains(boardCurrentMemberList, member.id);
            });
            b.organizationMembers.memberShips = _.filter(response.memberShips, function(memberShips) {
                return !_.contains(boardCurrentMemberList, memberShips.idMember);
            });
        });
    };
	
    /*
     *@params string query
     *@desc search member list by keyword
     */
	 
    b.searchMembers = function(query) {
        var params = {
            type: 'organizations',
            query: query
        };
        c.organizations.searchMembers(params).then(function(response) {
            b.organizationSeachMembers = response;
        });
    };
    /**
     * create a new list
     */
    b.createList = function(model) {
        model.idBoard = b.boardPageAssets.id;
        model.pos = b.boardPageAssets.lists.length;
        return !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.lists.create(model).then(function(response) {
            if (angular.isUndefined(response.cards)) {
                response.cards = [];
            }
			
            b.boardPageAssets.lists.push(response);
			
			a.appAssets.boards = _.filter(a.appAssets.boards,function($board){ // listCount root + 1
				if($board.id == response.idBoard)
					$board.listCount = parseInt($board.listCount) + 1;
				return $board;
			});
			
            b.input = {};
			_.defer(function(){
				$('.modal-close').trigger('click');
			});
			
			getNotification();
        }, alertError);
    };
	/**
     * copy a new list
     */
    b.CopyList = function($idList,model) {
        model.idBoard 	= b.boardPageAssets.id;
        model.pos 		= b.boardPageAssets.lists.length;
		model.type 		= 'copy';
		model.idList	= $idList;
        return !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.lists.create(model).then(function(response) {
            if (angular.isUndefined(response.cards)) {
                response.cards = [];
            }
			angular.forEach(response.cards,function($v,$k){
				$v = response.cards[$k] = setLabelsCard($v); // get label and selected 
				b.boardPageAssets.cards.push($v);
			});
            b.boardPageAssets.lists.push(response);
            b.input = {};
			_.defer(function(){
				$('.modal-close').trigger('click');
			});
        }, alertError);
    };
    /**
     * update a list
     */
    b.updateList = function(idList, model) {
        return !b.hasMemberInBoard(a.appAssets.id) || model.name === '' ? void 0 : c.lists.update(idList, model).then(function(response) {
            var curentList = _.find(b.boardPageAssets.lists, {id: idList});
            curentList = angular.extend(curentList, response);
			
			angular.forEach(b.boardPageAssets.lists, function(list, key) {
				if (list.id == response.id) {
					b.boardPageAssets.lists[key] = angular.extend(b.boardPageAssets.lists[key], response);
				}
			});

			b.boardPageAssets.lists = _.filter(b.boardPageAssets.lists,function(list) {
				return list.idBoard == b.boardPageAssets.id;
			});
			
            $(document.body).find('a.js-cancel').trigger('click');
			
			if(angular.isDefined(model.closed)) {
				getNotification();
			}
			_.defer(function() {
				$('.modal-close').trigger('click');
			});
        }, alertError);
    };
	 /**
     * Move a list to a Board
     */
    b.moveList = function(idList, model, listdetail) {
        return !b.hasMemberInBoard(a.appAssets.id) || model.name === '' ? void 0 : c.lists.move(idList, model, listdetail).then(function(response) {
            var curentList = _.find(b.boardPageAssets.lists, {id: idList});
			
            curentList = angular.extend(curentList, response);
			
			angular.forEach(b.boardPageAssets.lists, function(list, key) {
				if (list.id == response.id) {
					b.boardPageAssets.lists[key] = angular.extend(b.boardPageAssets.lists[key], response);
				}
			});
			
			b.boardPageAssets.lists = _.filter(b.boardPageAssets.lists,function(list) {
				return list.idBoard == b.boardPageAssets.id;
			});
			
			a.appAssets.boards = _.filter(a.appAssets.boards,function(board){
				if(response.idBoard == board.id) {
					board.listCount = parseInt(board.listCount)+1;
				} else if(listdetail.idBoard == board.id) {
					board.listCount = (parseInt(board.listCount) == 1)?0:parseInt(board.listCount)-1;
				}
				return board;
			});
			
            $(document.body).find('a.js-cancel').trigger('click');
			
        }, alertError);
    };
    /**
     * Search member of card from organization
     */
    b.cardSearchMembers = function() {
        if (!angular.isDefined(b.cardSeachMembers)) {
            c.boards.getMembersInOrganization(b.boardPageAssets.organizations.id).then(function(response) {
                b.cardSeachMembers = _.filter(response.members, function(member) {
                    var idMembers = _.pluck(b.boardPageAssets.members, 'id');
                    return !_.contains(idMembers, member.id);
                });
            });
        } else {
            return b.cardSeachMembers;
        }
    };

    /**
     * create a card
     */
    b.createCard = function(model) {
        if ((typeof model.displayName === 'undefined') ||  model.displayName.trim() === '') {
            return false;
        }
		if(model.displayName) {
			model.displayName  = model.displayName.replace(/^\s+|\s+$/g, '').split("\n").join("<br/>");
		}

        return !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.cards.create({
            closed: !1,
            dateLastActivity: new Date().getTime(),
            idBoard: parseInt(b.boardPageAssets.id),
            idList: parseInt(model.idList),
            idLabels: angular.toJson([]),
            displayName: model.displayName,
			due: model.due,
            pos: _.where(b.boardPageAssets.cards, {idList: model.idList}).length
        }).then(function(response) {
            var currentList = _.find(b.boardPageAssets.lists, {id: model.idList});
			currentList.cards.push(response);
            b.boardPageAssets.cards.push(response);
            b.input = {};
			getNotification();
            $('body').find('a.js-cancel').trigger('click');
			getEventSources();
        });
    };
	
    /**
     * update a card
     */
	b.updateCard = function(idCard, model) {
		if(model.desc){
			model.desc = model.desc.split("\n").join("<br/>");
		}
		if(model.displayName) {
			model.displayName = model.displayName.split("\n").join("<br/>");
		}
		
        c.cards.update(idCard, model).then(function(response) {
			response.parentId = _.map(response.parentId, function(value, key) { return parseInt(value); });
            var curentCard = _.find(b.boardPageAssets.cards, {id: idCard});
            curentCard = angular.extend(curentCard, response);
			if(angular.isDefined(model.closed) || angular.isDefined(model.due)) {
				getNotification();
			}
			
			/*parent CARD*/
			if(angular.isDefined(b.cardDetail)) {
				angular.extend(b.cardDetail, response);
				if(angular.isUndefined(b.cardDetail.parentId)) {
					b.cardDetail.parentId = [];
				}
				b.cardDetail.parentCard = _.filter(b.boardPageAssets.cards, function($card) {
					var parentId = b.cardDetail.parentId;
					
					if(angular.isDefined(parentId)) {
						return parentId.indexOf($card.id) > -1;
					}
				});
			}
			
			_.defer(function(){
				$('.modal-close').trigger('click');
				$('.js-cancel').trigger('click');
				$('.flaticon-button18').trigger('click');
			});
			
			
			
			setLastUpdated(idCard);
			getEventSources();
		});
	};
	/**
	 * card DELETED 
	**/
	b.deleteCard = function($idCard) {
		c.cards.delete($idCard).then(function(response) {
			_.defer(function(){
                $('body').find('button.close').trigger('click');
            });
			b.boardPageAssets.cards = _.filter(b.boardPageAssets.cards,function(card){
				return card.id != response.id
			});
			getNotification();
			getEventSources(); 
		});
	}
    /**
     * card detail
    **/
    b.getCardDetail = function(card) {
        c.cards.getCard(card.id).then(function(response) {
            card = angular.extend(card, response);
            card.attachmentSelected = _.find(card.attachments, {id: card.idAttachmentCover});
            b.cardDetail  = card;
            c.cards.model = card;
			b.displayNameCard = card.displayName.split("<br/>").join("\n");
			b.idMemberActiveCard = _.pluck(b.cardDetail.members,'id');
			b.activitiesCard = response.notifications;
			
			b.cardDetail.parentId = _.map(b.cardDetail.parentId, function(value, key) { return parseInt(value); });
			
			b.cardDetail.parentCard = _.filter(b.boardPageAssets.cards, function($card) {
				return b.cardDetail.parentId.indexOf($card.id) > -1;
			});
			
			angular.forEach(b.activitiesCard,function($v,$k){
				if($v.type == 10 && $v.name == 'card') { // add card,
					b.activitiesCard[$k].comment = _.find(response.comments,{id:parseInt($v.data.idComment)});
				}
			});
        });
    };

    /**
     * create new Label for card
     */
    b.cardCreateLabel = function(name, color) {
        var l;
        (l = _.find(b.boardPageAssets.labels, function(l) {
            return l.name === name && l.color === color;
        })) ? void(c.cards.hasLabel(l) || b.cardToggleLabel(l)) : c.cards.createLabel({
            name: name,
            color: color ? color : 'default',
            idBoard: b.boardPageAssets.id,
            used: 1
        }).then(function(response) {
            c.cards.model.idLabels.push(response.id);
            c.cards.model.labels.push(response);
            b.boardPageAssets.labels.push(response);
			setLastUpdated(response.id);
        });
    };
    /**
     * update card label
     * @param label
     */
    b.cardUpdateLabel = function(label) {
        c.cards.updateLabel(label.id, {name: label.name, color: label.color}).then(function(response) {
            label = angular.extend(label, response);
			setLastUpdated(b.cardDetail.id);
        });
    };
    /**
     * delete label
     * @param id
     */
    b.cardDeleteLabel = function(id) {
        c.cards.deleteLabel(id).then(function(id) {
            b.boardPageAssets.labels = _.filter(b.boardPageAssets.labels, function(label) {
                return label.id !== id;
            });
            angular.forEach(b.boardPageAssets.cards, function(card) {
                card.idLabels = _.filter(card.idLabels, function(idLabel) {
                    return idLabel !== id;
                });
                card.labels = _.filter(card.labels, function(label) {
                    return label.id !== id;
                });
            });
			setLastUpdated(b.cardDetail.id);
        });
    };

    b.cardToggleLabel = function(label) {
        var r = label;
        c.cards.toggleLabel(label).then(function(response) {
            var cardCurrent = _.find(b.boardPageAssets.cards, {id: c.cards.model.id});
            if (angular.isDefined(cardCurrent)) {
                if (c.cards.hasLabel(r)) {
                    cardCurrent.idLabels = _.filter(cardCurrent.idLabels, function(idLabel) {
                        return idLabel !== response;
                    });
                    cardCurrent.labels = _.filter(cardCurrent.labels, function(label) {
                        return label.id !== response;
                    });
                } else {
                    cardCurrent.idLabels.push(response);
                    var label = _.find(b.boardPageAssets.labels, function(label) {
                       return label.id === response;
                    });
                    if (angular.isUndefined(cardCurrent.labels)) {
                        cardCurrent.labels = [];
                    }
                    cardCurrent.labels.push(label);
                }
				setLastUpdated(b.cardDetail.id);
				getEventSources();
            }
        });
    };

    b.cardToggleMember = function(member) {
        var r = member;
        return !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.cards.toggleMember(member).then(function(response) {
            var cardCurrent = _.find(b.boardPageAssets.cards, {id: c.cards.model.id});
            if (angular.isDefined(cardCurrent)) {
                if (c.cards.hasMember(r.id)) {
					b.idMemberActiveCard  = _.filter(b.idMemberActiveCard,function(id){ // check in popup card-member
						return id !== r.id;
					});
                    cardCurrent.members = _.filter(cardCurrent.members, function(member) {
                        return member.id !== response;
                    });
                } else {
					b.idMemberActiveCard.push(r.id);// check in popup card-member
                    cardCurrent.members.push(response);
                }
				setLastUpdated(b.cardDetail.id);
				getNotification();	
            }
        }); 
    };
	/**
	 * edit label
	**/
	b.cardDetailLabels = function(label) {
		if (label){
			b.isNewRecord = false;
			b.model = label;
		} else {
			b.isNewRecord = true;
			b.model = {};
		}
	};
	/**
	 *	Images delete  in card detail
	 */
    b.cardAttachments = function(files, errFiles) {
        b.files = files;
        b.errFiles = errFiles;
		if(errFiles.length > 0) {
			angular.forEach(errFiles, function(error) {
				if(error.$error == "maxSize") {
					e.pop ('error','File can not be larger than '+error.$errorParam);
				} else if (error.$error == "pattern") {
					e.pop ('error',"Incorrect file format");
				} else if (error.$error == "maxHeight") {
					e.pop ('error',"file height must be less than "+ error.$errorParam);
				} else if (error.$error == "maxWidth") {
					e.pop ('error',"file width must be less than "+ error.$errorParam);
				}
			});
		} else {
			return !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.cards.upload(b.files, errFiles).then(function(response) {
				b.cardDetail.attachments.push(response);
				var arrayselected = ['image/jpeg','image/jpg','image/png','image/bmp','image/gif'];
				if(arrayselected.indexOf(response.mimeType) > -1) {
					b.cardDetail.attachmentSelected = response;
				}
				e.pop('success','Successful upload files');
				setCardBadgest(b.cardDetail);
				_.defer( function() {
					$('.modal-close').trigger('click');
				});
				setLastUpdated(b.cardDetail.id);
			});
		}
    };
	b.cardDeleteAttachments = function($idAttachment){
		c.cards.deleteAttachments($idAttachment).then( function(data) {
			b.boardPageAssets.cards = _.filter(b.boardPageAssets.cards,function($card){
				$card.attachments = _.filter($card.attachments,function($attachment){
					return $attachment.id != data.idAttachment;
				});
				return $card;
			});
			angular.forEach(b.boardPageAssets.lists,function($vlist,$klist){
				angular.forEach($vlist.cards,function($vcard,$kcard){
					if(b.boardPageAssets.lists[$klist].cards[$kcard].attachments){
						if(b.boardPageAssets.lists[$klist].cards[$kcard].attachments.length !== 1){
							b.boardPageAssets.lists[$klist].cards[$kcard].attachments = _.filter(b.boardPageAssets.lists[$klist].cards[$kcard].attachments, function(attachment) {
								return attachment.id !== data.idAttachment;
							});
						} else if(b.boardPageAssets.lists[$klist].cards[$kcard].attachments[0].id == data.idAttachment) {
							b.boardPageAssets.lists[$klist].cards[$kcard].attachments = [];
						}
					}
					if(!angular.isUndefined($vcard.attachmentSelected) && $vcard.attachmentSelected.id == data.idAttachment){
						b.boardPageAssets.lists[$klist].cards[$kcard].attachmentSelected = undefined;
					}
				});
			});
			setLastUpdated(b.cardDetail.id);
			setCardBadgest(b.cardDetail);
		});
	}

    /**
     * create a comment
     */
    b.cardComments = function(comment) {
		
		if(comment){
			comment = comment.split("\n").join("<br/>");
		}
       return !b.hasMemberInBoard(a.appAssets.id) ? void 0 :  c.cards.addComments(comment).then(function(response) {
			b.cardDetail.comments.push(response);
			b.cardDetail.comments = _.sortBy(b.cardDetail.comments, function(comment) {
              var date = new Date(comment.addedDate).getTime();
              return - date;
			});
			var $model = response;
			getNotification();
			b.input = {};
			setCardBadgest(b.cardDetail);
        });
    };
    /**
     * Update comments
     * @param id
     * @param comment
     * @returns {*}
     */
    b.cardUpdateComments = function(id, comment) {
		if(angular.isDefined(comment) || comment != null) {
			comment = comment.split("\n").join("<br/>");
		}
        var currentComment = _.find(b.cardDetail.comments, {id: id});
        return comment === '' || angular.isUndefined(currentComment) || !b.hasMemberInBoard(a.appAssets.id) || (a.appAssets.id !== currentComment.idMember) ? void 0 : c.cards.updateComments(id, comment).then(function(response) {
            currentComment = angular.extend(currentComment, response);
            b.input = {};
            _.defer(function(){
                $(document.body).find('a.js-cancel').trigger('click');
            });
        });
    };
    /**
     * Delete comments
     * @param id
     * @returns {*}
     */
    b.cardDeleteComments = function(id) {
        var currentComment = _.find(b.cardDetail.comments, {id: id});
        return angular.isUndefined(currentComment) || !b.hasMemberInBoard(a.appAssets.id) || (a.appAssets.id !== currentComment.idMember) ? void 0 : c.cards.deleteComments(id).then(function(response) {
            b.cardDetail.comments = _.filter(b.cardDetail.comments, function(comment) {
                return comment.id !== id;
            });
			reloadCommentCard();
			setCardBadgest(b.cardDetail);
        });
    };
    /**
     * Add checklist
     * @param name
     * @returns {*}
     */
    b.cardAddChecklists = function(name) {
        return name === '' || !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.cards.addChecklists(name).then(function(response) {
            b.cardDetail.checklists.push(response);
            b.input = {};
			getNotification();
			_.defer(function(){
				$('.modal-close').trigger('click');
			});
			setLastUpdated(b.cardDetail.id);
        });
    };
    /**
     * Update checklist
     * @param idChecklist
     * @param model
     * @returns {*}
     */
    b.cardUpdateChecklists = function(idChecklist, model) {
        return !model || !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.cards.updateChecklists(idChecklist, model).then(function(response) {
            var currentChecklist = _.find(b.cardDetail.checklists, {id: idChecklist});
            if (angular.isDefined(currentChecklist)) {
                currentChecklist = angular.extend(currentChecklist, response);
            }
			_.defer(function(){
				 $(document.body).find('a.js-cancel').trigger('click');
			});
			getNotification();
            b.input = {};
			setLastUpdated(b.cardDetail.id);
        });
    };
    /**
     * Delete checklist
     * @param idChecklist
     * @returns {*}
     */
    b.cardDeleteChecklists = function(idChecklist) {
        return !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.cards.deleteChecklists(idChecklist).then(function(response) {
            var currentChecklist = _.find(b.cardDetail.checklists, {id: idChecklist});
            b.cardDetail.checklists = _.filter(b.cardDetail.checklists, function(checklist) {
                return checklist.id !== idChecklist;
            });
			getNotification();
			setLastUpdated(b.cardDetail.id);
        });
    };
    /**
     * Add checklist Item
     * @param idChecklist
     * @param name
     * @returns {*}
     */
    b.cardAddChecklistItems = function(idChecklist, name) {
        return name === '' || !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.cards.addChecklistItems(idChecklist, name).then(function(response) {
            var currentChecklist = _.find(b.cardDetail.checklists, {id: idChecklist});
            if (angular.isDefined(currentChecklist)) {
                currentChecklist.checkItems.push(response[0]);
            }
			_.defer(function(){
				 $(document.body).find('a.js-cancel').trigger('click');
			});
			
			/*update count checkitem 
			angular.forEach(b.boardPageAssets.cards,function(card,$key) {
				if(card.id == response[1]) {
					b.boardPageAssets.cards[$key].badges.checkItems += 1;
				}
			});		*/	
			
            b.input = {};
			getNotification();
			setCardBadgest(b.cardDetail);
			setLastUpdated(b.cardDetail.id);
        });
    };
    /**
     * Update checklist Item
     * @param idChecklistItem
     * @param model
     * @returns {*}
     */
    b.cardUpdateChecklistItems = function(idChecklistItem, model) {
        return !model || !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.cards.updateChecklistItems(idChecklistItem, model).then(function(response) {
            var currentChecklist = _.find(b.cardDetail.checklists, function(checklist) {
                return angular.isDefined(_.find(checklist.checkItems, {id: idChecklistItem}));
            });
            if (angular.isDefined(currentChecklist)) {
                var currentChecklistItem = _.find(currentChecklist.checkItems, {id: idChecklistItem});
                if (angular.isDefined(currentChecklistItem)) {
                    currentChecklistItem = angular.extend(currentChecklistItem, response[0]);
                }
            }			
			getNotification();
            b.input = {};
			setCardBadgest(b.cardDetail);
			setLastUpdated(b.cardDetail.id);
        });
    };
    /**
     * Delete checklist Item
     * @param idChecklistItem
     * @returns {*}
     */
    b.cardDeleteChecklistItems = function(idChecklistItem) {
        return !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.cards.deleteChecklistItems(idChecklistItem).then(function(response) {
            var currentChecklist = _.find(b.cardDetail.checklists, function(checklist) {
                return angular.isDefined(_.find(checklist.checkItems, {id: idChecklistItem}));
            });
            if (angular.isDefined(currentChecklist)) {
                currentChecklist.checkItems = _.filter(currentChecklist.checkItems, function(checkItem) {
                   return checkItem.id !== idChecklistItem;
                });
            }
			setLastUpdated(b.cardDetail.id);
			/*update count checkitem 
			angular.forEach(b.boardPageAssets.cards,function(card,$key) {
				if(card.id == response[1] && card.badges.checkItems > 0) {
					b.boardPageAssets.cards[$key].badges.checkItems -= 1;
				}
			});*/
			setCardBadgest(b.cardDetail);
			getNotification();
        });
    };
	/**
	 * access checklist item percent
	 */
    b.getChecklistProgress = function(checklist) {
        if (angular.isUndefined(checklist) || !angular.isObject(checklist)) {
            return 0;
        }
        var checkItemsCount = checklist.checkItems.length;
        var checkItemsCheckedCount = _.filter(checklist.checkItems, function(checkItem) {
            return checkItem.state == 1;
        }).length || 0;
        return checkItemsCount != 0 ? Math.ceil((checkItemsCheckedCount * 100) / checkItemsCount) : 0;
    };
	/**
	 * config drag ang drop list and card. Access save list and card
	 */ 
	var idListold = 0;
    var sortableOptions = {
       lists: createOptions({
           placeholder: 'list-column list-box placeholder',
           connectWith: '.list-column',
           handle: '.list-box-header',
           tolerance: "pointer",
           items: ".list-column",
           delay: 200,
		   start: function(e, ui){
				ui.placeholder.height(ui.item.find(".list-box").height());
		   },
           beforeStop: function(e, ui) {
			   var idList = ui.item.attr('listid');
               var sortList = $('#boardfix').sortable('toArray', {attribute: 'listId'});
               c.lists.sort({idList:idList,sort: sortList}).then(function(data) {
                   var sort = data.sort;
                   angular.forEach(b.boardPageAssets.lists, function(list, key) {
                       angular.forEach(sort, function(listId, pos) {
                           if (listId == list.id) {
                               b.boardPageAssets.lists[key].pos = pos;
                           }
                       });
                   });
				   getNotification();
               })
           }
       }),
       cards: createOptions({
           placeholder: 'card-item placeholder',
           connectWith: '.list-box-content',
           tolerance: "pointer",
           items: '.card-item',
           delay: 200,
		   start: function(e, ui){
				ui.placeholder.height(ui.item.find(".list-box").height());
				idListold = ui.item.closest('.list-column').attr('listId');
				ui.item.attr('style',ui.item.attr('style')+' pointer-events:none;');
		   },
           stop: function(e, ui) {
				if(angular.isDefined(ui.item.sortable.droptarget)){
					var idList = ui.item.sortable.droptarget.closest('.list-column').attr('listId');					
					var idCard = ui.item.attr('cardid');
					var arrayId = [];
					if (angular.isArray(ui.item.sortable.droptargetModel)) {
						angular.forEach(ui.item.sortable.droptargetModel, function(value, key) {
							arrayId.push(value.id);
						});
					}
					c.cards.sort({idList: idList,idCard:idCard, sort: arrayId},{idListold:idListold,idListnew:idList,idCard:idCard}).then(function(data) {
						var sort = data.sort;
						angular.forEach(b.boardPageAssets.lists, function(list, key) {
							if (data.idList == list.id) {
								if (angular.isUndefined(b.boardPageAssets.lists[key].cards)) {
									return;
								}
								var cardsOfCurrentList = b.boardPageAssets.lists[key].cards;
								angular.forEach(cardsOfCurrentList, function(card, keyCard) {
									if (sort.indexOf(card.id)) {
										b.boardPageAssets.lists[key].cards[keyCard].pos = sort.indexOf(card.id);
									}
								});
							}
						});
						getNotification();
					});
				}
				//pointer events  
				ui.item.attr('style','pointer-events:');
           }
       })
    };
    function createOptions(options) {
        var options = options || {};
        a.$on('E:BOARD_GETTED', function(event, data) {
            options = angular.extend(options, {disabled: !b.hasMemberInBoard(a.appAssets.id)});
        });
        return options;
    };
    b.sortableOptions = sortableOptions;
	
	/**
	 * access check filter card 
	 */
	b.filterCard = {};
	b.removeFilterCard = function(){
		b.filterCard = {};
	}
	b.filterCheckCard = function(id, typeFilter) {
		if (angular.isUndefined(b.filterCard[typeFilter])) {
			b.filterCard[typeFilter] = [];
		}
		if(typeFilter == 'date'){
			if(id == b.filterCard[typeFilter]){
				b.filterCard[typeFilter] = "";
			}else{
				b.filterCard[typeFilter] = id;	
			}
		} else {
			if (_.contains(b.filterCard[typeFilter], id)) {
				b.filterCard[typeFilter] = _.filter(b.filterCard[typeFilter], function(idFilter) {
					return idFilter !== id;
				});
			} else {
				b.filterCard[typeFilter].push(id);
			}
		}
	};
    b.$watch('boardPageAssets.members.length', function() {
        b.sortableOptions.lists = angular.extend(b.sortableOptions.lists, {disabled: !b.hasMemberInBoard(a.appAssets.id)});
        b.sortableOptions.cards = angular.extend(b.sortableOptions.cards, {disabled: !b.hasMemberInBoard(a.appAssets.id)});
    });
	
	/* toggle board start*/
	b.toggleBoardStar = function(id) {
        return c.data = a.appAssets, c.boards.getBoardStar(id) ? c.boards.unStarBoard(id).then(function(response) {
            a.appAssets.boardStars = _.filter(a.appAssets.boardStars, function(boardStar) {
               return boardStar.idBoard != id;
            });
            b.boardPageAssets.starsCount = 0;            
        }) : c.boards.starBoard(id).then(function(response) {
            a.appAssets.boardStars.push(response);
            b.boardPageAssets.starsCount = 1; 
        });
	};
	/**
	 * Move all card old to card new
	 */
	b.updateCardAll = function($idList,model) {
        return !b.hasMemberInBoard(a.appAssets.id) ? void 0 : c.cards.updateCardAll($idList,model).then(function(response) {
			getUpdateCard(response);
			_.defer(function(){
				$('.modal-close').trigger('click');
			});
        });
	}
	//*** Link board ***//
	b.LinkBoard = n.absUrl();
	
	/** 
	 * filter my board 
	 */
	b.myBoard = function($board) {
		return $board.idOrganization === 0 || $board.idOrganization === null || $board.idOrganization === '0';
	}
	b.filterMyBoard = function () {
		var idOrganizationList = _.pluck(a.appAssets.organizations, 'id');
		return _.filter(a.appAssets.boards, function(board) {
			return !_.contains(idOrganizationList, parseInt(board.idOrganization,10));
        });
	}
	
	/** 
	 * update labels and attachmentSelected for cards 
	 */
	var setLabelsCard = function(card) {
		card.labels = _.filter(b.boardPageAssets.labels, function(labelItem,$key) {
			return card.idLabels.indexOf(labelItem.id) !== -1;
		});
		card.attachmentSelected = _.find(card.attachments, {id: card.idAttachmentCover});
		return card;
    };
	
	/** 
	 * update card of angular
	 * params: response (object);
	 */
	var getUpdateCard  = function(response) {
		angular.forEach(b.boardPageAssets.cards,function($vcard,$kcard){
			angular.forEach(response,function($v){
				if($vcard.id == $v.id) {
					angular.extend(b.boardPageAssets.cards[$kcard],$v) ;
				}
			});
		});
		getUpdatelist();
	};
	
	/** 
	 * update all list in a Board
	 */
	var getUpdatelist = function(){
		 angular.forEach(b.boardPageAssets.lists, function(list) {
            list.cards = _.where(b.boardPageAssets.cards, {idList: list.id});
            if (angular.isUndefined(list.cards)) {
                list.cards = [];
            }
			list.cards.sort(function(a,b){
				return a.pos - b.pos;
			})
        });
	};
	
	/**
     * return nums of position LIST 
	 * param: $idBoard(num);
	 * return array();
	 */
	b.listpos = function($idBoard) {
		var $board = [];
		var listposition = [];
		angular.forEach(a.appAssets.boards,function($v) {
			if($v.id == $idBoard) {
				$board = $v;
			}
		});
		
		if($board && !angular.isUndefined($board.listCount) && $board.listCount != 0) {
			for (var $i=1;$i <= $board.listCount;$i++) {
				listposition.push($i);
			}
			listposition.push($i);
		} else {
			listposition.push(1);
		}
		return listposition;
	};
	
	b.getOrganization  = function($idOrganization,type) {
		var result = _.find(a.appAssets.organizations,{id:parseInt($idOrganization)});
		
		if(b.boardPageAssets.organizations != null && b.boardPageAssets.organizations != $idOrganization) {
			b.boardPageAssets.organizations = angular.extend(b.boardPageAssets.organizations,result);
		} else {
			return "My Boards";
		}
		if(type) {
			$valueType = "";
			angular.forEach(result,function($v,$k){
				if($k == type) {
					$valueType = $v;
				}
			});
			return $valueType
		} else {
			return result;
		}
	};
	
	/*filter group team  and filter not group team*/
	/* check non team  */
	b.NonTeam = function () {
		if(b.boardPageAssets.organizations != null) {
			var checkNonTeam = false;
			$teamIdMeber = _.pluck(b.boardPageAssets.organizations.members,"id");
			angular.forEach(b.boardPageAssets.members,function($member){
				if($teamIdMeber.indexOf($member.id) == -1) {
					checkNonTeam =  true;
				}
			});
			return checkNonTeam;
		}
	};
	
	b.group_team = function($member) {
		if(b.boardPageAssets.organizations != null) {
			
			$groups  = _.pluck(b.boardPageAssets.organizations.members,'id');
			return $groups.indexOf($member.id) > -1;
		}
	};
	
	b.non_group_team = function($member) {
		if(b.boardPageAssets.organizations != null) {
			$groups  = _.pluck(b.boardPageAssets.organizations.members,'id');
			return $groups.indexOf($member.id) == -1;
		}
	};
	
	/* load notificaiton pagination*/
	b.notifyPerpage = 5,
	b.notifyLimit = 5,
	b.loadMoreNotify = function() {
		l.getNotificationlimit(b.notifyLimit,b.notifyPerpage,{idBoard : b.boardPageAssets.id},'board').then(function(response){
			if(response.length > 0) {
				b.notifyPerpage += b.notifyLimit;
				angular.forEach(response,function($res){
					b.boardPageAssets.notifications.push($res);
				});
				b.notifycount = response.length;
			} else {
				b.notifycount = 0;
			}
		});
	} ;
	
	/* access show color background due for card*/
	b.colorDue = function(card, $type) {
		var $date = new Date();
		$date.setDate($date.getDate()+7);
		var $date7day = $date.getTime();
		
		if($type == 'due') {
			$dateCardDeadline  = card.badges.due;
		}
		else if($type=='startDate')
			var $dateCardDeadline  = card.badges.startDate;	
	
		var $dateNow = new Date();
		$dateNow = $dateNow.getTime();
		
		if($dateCardDeadline >=  $dateNow && $dateCardDeadline <= $date7day) {
			return 'timeyellow';
		} else if($dateCardDeadline <= $dateNow) {
			return 'timered';
		} else {
			return 'timeblue';
		}
	};
	
	/* access cover image for card */
	b.checkCover = function ($mimeType) {
		var $listMimeType = ['image/jpeg','image/jpg','image/png','image/bmp','image/gif'];
		if($listMimeType.indexOf($mimeType) > -1) {
			return true;
		}
		return false;
	};	
	b.ActionConver = function ($idCard,$idAttachment,$idAttachmentSelected) {
		var $params = ($idAttachment == $idAttachmentSelected) ? {idAttachmentCover:0} : {idAttachmentCover:$idAttachment};
		c.cards.update($idCard, $params).then(function(response) {
			var $cardCover = _.find(b.boardPageAssets.cards, {id: $idCard});
			if($params.idAttachmentCover == 0) {
				$cardCover.attachmentSelected = undefined;
			} else {
				var attachment = _.find($cardCover.attachments, {id: $params.idAttachmentCover})
				$cardCover.attachmentSelected = attachment;
			}
			setLastUpdated(b.cardDetail.id);
			d.emit('WE:CARD_IMAGE_COVER',{"attachmentSelected" : $cardCover.attachmentSelected, 'idCard' : $idCard})
		});
	};
	
	/* New line text area*/
	b.newlineTextarea = function ($textarea) {
		return g("newline")($textarea);
	}
	
	/* Copy card in detail card*/
	b.CopyCard = function ($model) {
		if(angular.isDefined($model.displayName)) {
			$model.displayName = g('newlineHtml')($model.displayName);
		}
		if(angular.isDefined(b.cardDetail)) {
			return !b.hasMemberInBoard(a.appAssets.id) ? e.pop('error', "you are not permissions !") : c.cards.copy(b.cardDetail.id, $model).then( function(response) {
				if(angular.isDefined(response)) {					
					response = setLabelsCard(response);
					b.boardPageAssets.cards.push(response);
					var $listPush = _.find(b.boardPageAssets.lists,{id:response.idList});
					if(angular.isDefined($listPush)) {
						$listPush.cards.push(response);
						$listPush.cards.sort(function(a, b) {
							return a.pos - b.pos;
						});
					}
					_.defer(function() {
						$('.modal-close').trigger('click');
					})
				}
			});
		}
	}
	
	/* access move card */
	b.MoveCard = function($model) {
		if(angular.isDefined(b.cardDetail)) {
			return !b.hasMemberInBoard(a.appAssets.id) ? e.pop('error', "you are not permissions !") : c.cards.moveCard(b.cardDetail.id, $model).then( function(response) {
					_.defer(function(){
						$('.close').trigger('click');
						var $card = _.find(b.boardPageAssets.cards, {id: parseInt(response.id)});
						if(angular.isDefined($card)) 
							$card = angular.extend($card,response);
						d.emit('WE:CARD_MOVE', $card);
						getUpdatelist();
						
					});
				
			});
		}
	}
	/** 
	 * color picker
	 */
	b.ColorPickeroptions = {
		format: 'hex',
		alpha: false,
		swatch:  false,
		swatchBootstrap: false,
		swatchOnly: false,
		pos: 'bottom',
		inline: true,
	};
	/** 
	 * change by color picker
	 */
	b.changeByColorPicker = function(model) {	
		var params = {
            idMember: a.appAssets.id,
			type: 'prefs/background',
			color: model.fontColor,
			idList: model.id,
			backgroundColor: model.color,
		};	
		c.lists.update(params.idList, params).then(function(response) {
           d.emit('WE:LIST_BACKGROUND_CHANGED', [response.id, model]);
           var list = _.find(b.boardPageAssets.lists,{id:response.id});
		   list = angular.extend(list,response);
		   
		   _.defer(function() {
			   $('.modal-close').trigger('click');
		   });
        });		
    };
	/* check parent card*/
	b.checkparentCard = function($idCard, $cardParent) {
		if($cardParent != null) {
			if($cardParent.indexOf($idCard) > -1) {
				return true;
			}
		}
		return false;
	}
	/* default close calendar card*/
	b.closedCalendar = 1;
	b.setCloseCalendar = function() {
		b.closedCalendar = (b.closedCalendar == 1) ? 0 : 1;
	}
}]);
