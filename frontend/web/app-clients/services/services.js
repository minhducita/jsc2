//services.js
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
(function(window, angular, undefined) {
    'use strict';
    angular.module('app.services', [])
        .service('UserBehaviorsService', ['$q', '$http', 'UserIdentityService', 'API_CONFIG', function(a, b, c, d) {
            var service = this;
            service.signin = function(scopeData) {
                return b.post(d.to('site/signin'), scopeData);
            };

            service.login = function(scopeData) {
                var defer = a.defer();
                var req = {
                    method: 'POST',
                    url: d.to('site/login'),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    data: $.param(scopeData)
                };
                b(req).then(
                    function(response) {
                        c.setIdentity(response.data.token);
                        defer.resolve(response.data.token);
                    },function(error) {
                        defer.reject(error.data);
                    });
                return defer.promise;
            };
            service.register = function(scopeData) {
                var defer = a.defer();
                var req = {
                    method: 'POST',
                    url: d.to('site/register'),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    data: $.param(scopeData)
                };
                b(req).then(
                    function(response) {
                        c.setIdentity(response.data.token);
                        defer.resolve(response.data.token);
                    },function(error) {
                        defer.reject(error.data);
                    });
                return defer.promise;
            };
            service.forgot = function(scopeData){
                var defer = a.defer();
                var req = {
                    method: 'POST',
                    url: d.to('site/forgot'),
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    data: $.param(scopeData)
                };
                b(req).then(
                    function(response) {
                        defer.resolve(response.data);
                    },function(error) {
                        defer.reject(error.data);
                    });
                return defer.promise;
            }
            service.logout = function(destroySession) {
                destroySession = destroySession || true;
                if (destroySession) {
                    c.removeIdentity('identity');
                }
                return b.post(d.to('site/logout'));
            };
        }])
        .service('UserIdentityService', ['store', function(a) {
            var service   = this,
                _identity = null;
            service.isGuest = function() {
                return service.getIdentity() === null;
            };

            service.setIdentity = function(identity) {
                _identity = identity;
                a.set('identity', identity);
            };

            service.getIdentity = function() {
                if (!_identity) {
                    _identity = a.get('identity');
                }
                return _identity;
            };

            service.removeIdentity = function() {
                _identity = null;
                return a.remove('identity');
            }
        }])
        .service('InterceptorApi', ['$q', '$rootScope', 'UserIdentityService', 'HTTP_EXCEPTION', function(a, b, c, d) {
            var service = this;
            service.request = function(request) {
                var identity = c.getIdentity(),
                    token = identity ? identity : null;
                if (token) {
                    if (request.url.match(/^http([s]?):\/\/.*/)) {
                        request.params = request.params || {};
                        request.params['access-token'] =  token;
                    }
                }
                return request || a.when(request);
            };

            service.responseError = function(response) {
                b.$broadcast({
                    400: d.Badrequest,
                    401: d.Unauthorized,
                    403: d.Forbidden,
                    404: d.NotFound,
                    405: d.MethodNotAllowed,
                    500: d.ServerError
                }[response.status], response.data);
                return a.reject(response);
            };
        }])
        .service('AppService', ['$rootScope', '$q', '$http', 'Socket', 'API_CONFIG', 'ACTION_ACTIVITY', 'toaster', '$timeout', 'Upload', function(a, b, c, d, e, f, g, h, i) {
            var service        = this;
            service.data   = [];
            var queryAssets = {};
            queryAssets.memberMiniFields 		= ['id', 'username', 'displayName', 'initialsName', 'avatarHash', 'bio', 'url', 'status','typeimg'],
                queryAssets.organizationMiniFields 	= ['id', 'displayName', 'name', 'sourceHash'],
                queryAssets.boardMiniFields  		= ['id', 'displayName', 'name', 'desc', 'prefs', 'labelNames', 'closed', 'idOrganization','idMember','slackChanel'],
                queryAssets.cardMiniFields  		= ['id', 'displayName', 'name', 'desc', 'idAttachmentCover', 'pos', 'closed', 'addedDate', 'due', 'startDate', 'badges', 'idMember', 'idMembers', 'idLabels', 'idChecklists', 'idList', 'idBoard', 'lastUpdated', 'parentId', 'important', 'urgent'],
                queryAssets.cardActions 			= ["addAttachmentToCard", "addChecklistToCard", "addMemberToCard", "commentCard", "copyCommentCard", "convertToCardFromCheckItem", "createCard", "copyCard", "deleteAttachmentFromCard", "emailCard", "moveCardFromBoard", "moveCardToBoard", "removeChecklistFromCard", "removeMemberFromCard", "updateCard:idList", "updateCard:closed", "updateCard:due", "updateCheckItemStateOnCard"],
                queryAssets.boardActions 			= [queryAssets.cardActions.join(','), "addMemberToBoard", "addToOrganizationBoard", "copyBoard", "createBoard", "createList", "deleteCard", "disablePlugin", "disablePowerUp", "enablePlugin", "enablePowerUp", "makeAdminOfBoard", "makeNormalMemberOfBoard", "makeObserverOfBoard", "moveListFromBoard", "moveListToBoard", "removeFromOrganizationBoard", "unconfirmedBoardInvitation", "unconfirmedOrganizationInvitation", "updateBoard", "updateList:closed"],
                queryAssets.expandNotifications 	= 'notifications(id.data.type.name|members.notifyMember.effectMember)';

            function filterPath(url) {
                if (angular.isString(url)) {
                    return e.to(url);
                } else if (angular.isArray(url) || angular.isObject(url)) {
                    var path = [];
                    var parseUrl = function(url) {
                        angular.forEach(url, function (value, key) {
                            if (angular.isArray(value) || angular.isObject(value)) {
                                parseUrl(value);
                            } else {
                                path.push(value);
                            }
                        });
                    };
                    parseUrl(url);
                    return e.to(path.join('/'));
                }
            }
            service.load = function(forceReload) {
                return b(function(resolve, reject) {
                    if (service.data > 0 && !forceReload) {
                        resolve(service.data);
                    } else {
                        var params = {
                            fields: queryAssets.memberMiniFields.join(','),
                            expand: ['organizations('+ queryAssets.organizationMiniFields.join('.')+'|memberShips)', 'boards('+[queryAssets.boardMiniFields.join('.'), 'prefs', 'starsCount'].join('.')+'|organizations.labels.memberShips.listCount)', 'boardStars', 'boardCloses', queryAssets.expandNotifications].join(','),
                            board_closed: 0
                        };
                        c.get(e.to('me'), {
                            headers: {
                                'Content-Type': undefined
                            },
                            params: params
                        }).success(function(response) {
                            service.data = response;
                            a.$broadcast('E:APP_ASSETS', response);
                            resolve(service.data);
                        });
                    }
                });
            };
            service.organizations = {
                model: null,
                getModel: function(id, model) {
                    return b(function(resolve, reject) {
                        c.get(filterPath(['organization', id]), {
                            headers: {
                                'Content-Type': undefined
                            },
                            params: model
                        }).success(function(response) {
                            resolve(response);
                        }).error(function(errors) {
                            reject(errors);
                        });
                    });
                },
                getOrganization: function() {
                    return b(function(resolve, reject) {
                        var setOptionParams = function(option) {
                            if (!angular.isString(option)) {
                                return {};
                            }
                            option = (option == '') ? '' : (option[0] === '/' ? option.substring(1) : option);
                            switch(option) {
                                case '':
                                    option = 'boards('+[queryAssets.boardMiniFields.join('.'), 'starsCount'].join('.')+')';
                                    return {expand: [option, 'members('+[queryAssets.memberMiniFields.join('.')].join('.')+')', 'memberShips'].join(',')};
                                    break;
                                case 'members':
                                    option = 'members('+[queryAssets.memberMiniFields.join('.')].join('.')+')';
                                    break;
                                case 'account':
                                    option = 'account';
                                    break;
                            }
                            return {expand: [option, 'memberShips'].join(',')};
                        };
                        var params = {
                            board_closed: 0
                        };
                        params = angular.extend(params, setOptionParams(a.$stateParams.optionParams), {});
                        c.get(filterPath(['organization', a.$stateParams]), {
                            headers: {
                                'Content-Type': undefined
                            },
                            params: params
                        }).success(function(response) {
                            a.$broadcast('E:ORGANIZATION_GETTED', response);
                            service.organizations.model = response;
                            resolve(response);
                        }).error(function(errors) {
                            reject(errors);
                        });
                    });
                },
                create: function(model) {
                    return b(function(resolve, reject) {
                        c.post(filterPath('organization'), model).success(function(response) {
                            a.$broadcast('E:ORGANIZATION_CREATED', response);
                            resolve(response);
                        }).error(function(errors) {
                            reject(errors);
                        });
                    });
                },
                update: function($id,model) {
                    return b(function(resolve, reject) {
                        c.put(e.to('organization/'+$id),model).success(function(response) {
                            a.$broadcast('E:ORGANIZATION_UPDATE', response);
                            d.emit('WE:ORGANIZATION_UPDATE', response);
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                delete: function(id) {
                    return b(function(resolve, reject) {
                        c.delete(e.to('organization/'+id)).success(function(response) {
                            d.emit('WE:ORGANIZATION_DELETED', response);
                            resolve(response);
                        }).error(function(errors) {
                            reject(errors);
                        });
                    });
                },
                addMemberToOrganization: function(member,organization) {
                    var params = {
                        type: "normal"
                    };
                    return b(function(resolve, reject) {
                        service.organizations.changeRole(member.id, 'normal').then(function (response) {
                            d.emit('WE:ADDED_MEMBER_TO_ORGANIZATION', response);
                            // if (angular.isDefined(organization)) {
                            d.emit('WE:SEND_ORGANIZATION_TO_MEMBER', [organization, member.id]);
                            //}
                            resolve(response);
                        });
                    });
                },
                removeMemberOfOrganization: function(idMember) {
                    return b(function(resolve, reject) {
                        c.delete(e.to('organization/'+ service.organizations.model.id + '/members/' + idMember)).success(function(response) {
                            d.emit('WE:REMOVED_MEMBER_OF_ORGANIZATION', idMember);
                            d.emit('WE:REMOVED_ORGANIZATION_OF_MEMBER', [service.organizations.model.id, idMember]);
                            resolve(idMember);
                        });
                    });
                },
                hasMemberInOrganization: function(idMember) {
                    if (angular.isUndefined(service.organizations.model.members)) {
                        return false;
                    } else {
                        var idMemberList = _.pluck(service.organizations.model.members, 'id');
                        return _.contains(idMemberList, idMember);
                    }
                },
                changeRole: function(idMember, role) {
                    return b(function(resolve, reject) {
                        c.put(e.to('organization/'+ service.organizations.model.id + '/members/' + idMember), {type: role}).success(function(response) {
                            d.emit("WE:CHANGE_ROLE_ORGANIZATION",response);
                            resolve(response);
                        });
                    });
                },
                searchMembers: function(query) {
                    return b(function(resolve, reject) {
                        c.get(e.to('search/members'), {
                            headers: {
                                'Content-Type': undefined
                            },
                            params: query
                        }).success(function(response) {
                            d.emit('E:SEARCH_MEMBERS', response);
                            resolve(response);
                        });
                    });
                },
                isOwner: function(idMember) {
                    return idMember && idMember === service.organizations.model.idMember;
                },
                uploadimage: function(files, errFiles) {
                    return b(function(resolve, reject) {
                        if(files.length > 0) {
                            _.defer(function() {
                                $(document.body).find('.uploading').show();
                            });
                        }
                        angular.forEach(files, function(file) {
                            file.upload = i.upload({
                                url: e.to('organization/'+service.organizations.model.id+'/attachments/'),
                                data: {file: file}
                            });

                            file.upload.then(function (response) {
                                h(function () {
                                    file.result = response.data;
                                    _.defer(function() {
                                        $(document.body).find('.uploading').hide();
                                    });
                                    d.emit('WE:ORGANIZATION_CHANGE_LOGO',response.data);
                                    resolve(response.data);
                                });
                            }, function (response) {
                                if (response.status > 0) {
                                    g.pop('error', 'Failed to upload file to server.');
                                }
                            }, function (evt) {
                                file.progress = Math.min(100, parseInt(100.0 *
                                    evt.loaded / evt.total));
                            });
                        });
                    });
                },
                deletelogo: function () {
                    return b(function(resolve, reject) {
                        c.delete(e.to("organization/" + service.organizations.model.id + "/deletelogo"))
                            .success( function(response) {
                                d.emit("WE:ORGANIZATION_DELETE_LOGO", response);
                                resolve(response);
                            })
                            .error( function(error) {
                                reject(error);
                            })
                    })
                }
            };

            service.boards = {
                model: null,
                myPrefNames: ["showSidebar", "showSidebarMembers", "showSidebarBoardActions", "showSidebarActivity", "emailKey", "idEmailList", "emailPosition", "calendarKey", "fullEmail"],
                addReport: function(hour, member, cardId, boardId, date) {
                    return b(function(resolve, reject) {
                        c.post(e.to('report/create-report/'), { "hours": hour, "idMember": member,"idCard": cardId, "idBoard" : boardId,  "created_at": date })
                         .success(response => resolve(response))
                         .error(error => reject(error));
                    })
                },
                getHour: function(idCard) {
                    return b(function(resolve, reject) {
                        c.post(e.to('report/show-sum-hours/'), { "idCard": idCard })
                         .success(response => resolve(response))
                         .error(error => reject(error));
                    })
                },
                getListBoardReport: function() {
                    return b(function(resolve, reject) {
                        c.post(e.to('export/get-board/'))
                         .success(response => resolve(response))
                         .error(error => reject(error));
                    })
                },
                getBoard: function() {
                    return b(function(resolve, reject) {
                        c.get(filterPath(['board', a.$stateParams]), {
                            headers: {
                                'Content-Type': undefined
                            },
                            params: {
                                fields: queryAssets.boardMiniFields.join(',')+",starsCount",
                                expand: [
                                    'lists',
                                    'cards('+queryAssets.cardMiniFields.join('.')+'|members.attachments)',
                                    'members('+queryAssets.memberMiniFields.join('.')+')',
                                    'organizations('+queryAssets.organizationMiniFields.join('.')+'|members)',
                                    'memberShips',
                                    'myPrefs',
                                    'labels',

                                    queryAssets.expandNotifications
                                ].join(','),
                                //card_expands: 'attachments',
                                board_closed: 0,
                                lists_closed: 0,
                                card_closed: 0,
                                notify_limit:5,
                            }
                        }).success(function (response) {
                            a.$broadcast('E:BOARD_GETTED', response);
                            resolve(response);
                        }).error(function (errors) {
                            reject(errors);
                        });
                    });
                },
                /* add board start*/
                starBoard: function(id) {
                    var pos;
                    return service.boards.getBoardStar(id) ? e.pop('error', "already starred") : pos = service.data.boardStars.length + 1, b(function(resolve, reject) {
                        c.post(e.to('board/' + id + '/boardStars'), {
                            idBoard: id,
                            pos: pos
                        }).success(function(response) {
                            d.emit('WE:BOARD_STARED', response);
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                unStarBoard: function(id) {
                    return b(function(resolve, reject) {
                        c.delete(e.to('board/' + id + '/boardStars')).success(function(response) {
                            d.emit('WE:BOARD_STAR_DELETED', id);
                            resolve(id);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                getBoardStar: function(id) {
                    return _.find(service.data.boardStars, function(boardStar) {
                        return boardStar.idBoard == id;
                    });
                },
                getBoardActions: function() {
                    return b(function(resolve, reject) {
                        c.get(filterPath(['board', a.$stateParams]), {
                            headers: {
                                'Content-Type': undefined
                            },
                            params: {
                                fields: '',
                                actions: queryAssets.boardActions.join(','),
                                actions_display: !0,
                                actions_limit: 50,
                                //action_memberCreator_fields: queryAssets.memberMiniFields.join(','),
                                checklists: 'none',
                                card_closed: !0,
                                //card_fields: '',
                                card_checklists: 'all',
                                labels: 'all',
                                labels_limit: 1000,
                                expand: ['members('+queryAssets.memberMiniFields.join('.')+')', 'checklists'].join(',')
                            }
                        }).success(function (response) {
                            a.$broadcast('E:BOARD_ACTION_GETTED', response);
                            resolve(response);
                        }).error(function (errors) {
                            reject(errors);
                        });

                    });
                },
                create: function(model) {
                    return b(function(resolve, reject) {
                        c.post(e.to('board'), model,{
                            params: {
                                fields:[queryAssets.boardMiniFields.join(','), 'prefs', 'starsCount'].join('.')+",idMember",
                                expand:'organizations,labels,memberShips'
                            }
                        }).success(function(response) {
                            d.emit('WE:BOARD_CREATED', response);
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                search: function(condition) {

                },
                update: function(id, model) {
                    return b(function(resolve, reject) {
                        c.put(e.to('board/'+id), model,{
                            params: {
                                expand:'memberShips'
                            }
                        }).success(function(response) {
                            d.emit('WE:BOARD_UPDATED', response);
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                addMemberToBoard: function(member) {
                    return b(function(resolve, reject) {
                        service.boards.changeRole(member.id, 'normal').then(function (response) {
                            d.emit('WE:ADDED_MEMBER_TO_BOARD', {response: response, idBoard: service.boards.model.id});
                            var board = _.find(a.appAssets.boards, {id: service.boards.model.id});
                            if (angular.isDefined(board)) {
                                d.emit('WE:SEND_BOARD_TO_MEMBER', [board, member.id]);
                            }
                            resolve(response);
                        });
                    });
                },
                removeMemberOfBoard: function(idMember) {
                    return b(function(resolve, reject) {
                        c.delete(e.to('board/'+ service.boards.model.id + '/members/' + idMember)).success(function(response) {
                            d.emit('WE:REMOVED_MEMBER_OF_BOARD', {idMember: idMember, idBoard: service.boards.model.id});
                            d.emit('WE:REMOVED_BOARD_OF_MEMBER', [service.boards.model.id, idMember]);
                            resolve(idMember);
                        });
                    });
                },
                hasMemberInBoard: function(idMember) {
                    if (angular.isUndefined(service.boards.model.members)) {
                        return false;
                    } else {
                        var idMemberList = _.pluck(service.boards.model.members, 'id');
                        return _.contains(idMemberList, idMember);
                    }
                },
                changeRole: function(idMember, role) {
                    return b(function(resolve, reject) {
                        c.put(e.to('board/'+ service.boards.model.id + '/members/' + idMember), {type: role}).success(function(response) {
                            resolve(response);
                        });
                    });
                },
                searchMembers: function(query) {
                    return b(function(resolve, reject) {
                        c.get(e.to('search/members'), {
                            headers: {
                                'Content-Type': undefined
                            },
                            params: query
                        }).success(function(response) {
                            d.emit('E:SEARCH_MEMBERS', response);
                            resolve(response);
                        });
                    });
                },
                getMembersInOrganization: function(idOrganization) {
                    return b(function(resolve, reject) {
                        c.get(filterPath(['organization', idOrganization]), {
                            params: {
                                expand: 'members'
                            }
                        }).success(function (response) {
                            resolve(response);
                        }).error(function (errors) {
                            reject(errors);
                        });

                    });
                }
            };

            service.lists = {
                getLists: function($id) {
                    return b(function(resolve, reject) {
                        c.get(e.to('lists/'+$id+"/board"),{
                            params: {
                                expand: 'cards'
                            }
                        }).success(function (response) {
                            resolve(response);
                        });
                    })
                },
                create: function(model) {
                    return b(function(resolve, reject) {
                        c.post(e.to('lists'), model,{
                            params: {
                                expand: 'cards('+queryAssets.cardMiniFields.join('.')+'|members.attachments)',
                            }
                        }).success(function(response) {
                            d.emit('WE:LISTS_CREATED', response);
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                update: function(id, model) {
                    return b(function(resolve, reject) {
                        c.put(e.to('lists/'+id), model).success(function(response) {
                            d.emit('WE:LISTS_UPDATED', response);
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                move: function(id, model, listdetail) {
                    return b(function(resolve, reject) {
                        c.put(e.to('lists/'+id), model).success(function(response) {
                            d.emit('WE:LISTS_MOVE', {data:response,listdetail:listdetail});
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                sort: function(sortList) {
                    return b(function(resolve, reject) {
                        c.put(e.to('lists/sort'), sortList).success(function(response) {
                            d.emit('WE:LISTS_SORTED', sortList);
                            resolve(sortList);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                }
            };

            service.cards = {
                model: null,
                getListCardIdBoard: function(id, startDate, endDate) {
                    return b(function (resolve, reject) {
                        if (id == '-1') {
                            c.post(e.to('export/get-all-card/'), {"startDate": startDate, "endDate": endDate})
                             .success(response => resolve(response))
                             .error(error => reject(error));
                        } else {
                            c.post(e.to('export/get-cards/'), {"idBoard": id, "startDate": startDate, "endDate": endDate})
                             .success(response => resolve(response))
                             .error(error => reject(error));
                        }
                    });
                },
                getCard: function(id) {
                    return b(function(resolve, reject) {
                        c.get(filterPath(['card', id]), {
                            headers: {
                                'Content-Type': undefined
                            },
                            params: {
                                fields: queryAssets.cardMiniFields.join(','),
                                card_closed: 0,
                                expand: [
                                    'lists(id.name)',
                                    'members('+queryAssets.memberMiniFields.join('.')+')',
                                    'checklists',
                                    'labels',
                                    'attachments',
                                    'comments',
                                    queryAssets.expandNotifications
                                ].join(',')
                            }
                        }).success(function (response) {
                            a.$broadcast('E:CARD_GETTED', response);
                            resolve(response);
                        }).error(function (errors) {
                            reject(errors);
                        });
                    });
                },
                create: function(model) {
                    return b(function(resolve, reject) {
                        c.post(e.to('card'), model,{
                            params: {
                                fields: queryAssets.cardMiniFields.join(','),
                                card_closed: 0,
                                expand: [
                                    'lists(id.name)',
                                    'members('+queryAssets.memberMiniFields.join('.')+')',
                                    'checklists',
                                    'labels',
                                    'attachments',
                                    'comments',
                                ].join(',')
                            }
                        }).success(function(response) {
                            d.emit('WE:CARD_CREATED', response);
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                update: function(id, model) {
                    return b(function(resolve, reject) {
                        c.put(e.to('card/'+id), model).success(function(response) {
                            d.emit('WE:CARD_UPDATED', [id, response]);
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                delete: function(id){
                    return b(function(resolve,reject){
                        c.delete(e.to('card/'+id)).success(function(response) {
                            d.emit('WE:CARD_DELETE', [id, response]);
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                copy: function($id, $params) {
                    return b(function(resolve, reject) {
                        c.post(e.to('card/' + $id + '/copyCard'),$params,{
                            params: {
                                fields: queryAssets.cardMiniFields.join(','),
                                card_closed: 0,
                                expand: [
                                    'lists(id.name)',
                                    'members('+queryAssets.memberMiniFields.join('.')+')',
                                    'checklists',
                                    'labels',
                                    'attachments',
                                    'comments',
                                ].join(',')
                            }
                        }).success(function(response) {
                            resolve(response);
                            d.emit('WE:COPY_CARD', response);
                        }).error(function(error) {
                            reject(error);
                        });
                    })
                },
                moveCard: function(id, model) {
                    return b(function(resolve, reject) {
                        c.put(e.to('card/'+id), model).success(function(response) {

                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                updateCardAll: function($id,model) {
                    return b(function(resolve,reject) {
                        c.put(e.to("card/"+$id+"/moveall"),model).success(function(response){
                            d.emit('WE:UPDATE_CARD_ALL',response);
                            resolve(response);
                        }).error(function(error){
                            reject(error);
                        });
                    });
                },
                sort: function(sortList,paramsSort) {
                    return b(function(resolve, reject) {
                        c.put(e.to('card/sort'), sortList).success(function(response) {
                            d.emit('WE:CARD_SORTED', {data:sortList,paramsSort:paramsSort});
                            resolve(sortList);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                createLabel: function(model) {
                    return b(function(resolve, reject) {
                        c.post(e.to('card/' + service.cards.model.id + '/labels'), model).success(function(response) {
                            d.emit('WE:CARD_LABELS_CREATED', [service.cards.model.id, response]);
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                updateLabel: function(id, model) {
                    model.idcard = service.cards.model.id;
                    return b(function(resolve, reject) {
                        c.put(e.to('labels/' + id), model).success(function(response) {
                            d.emit('WE:LABELS_UPDATED', response);
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                deleteLabel: function(id) {
                    return b(function(resolve, reject) {
                        c.put(e.to('labels/' + id + '/idCards/' + service.cards.model.id)).success(function (response) {
                            d.emit('WE:LABELS_DELETED', id);
                            resolve(id);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                hasLabel: function(label) {
                    if (service.cards.model === null || !angular.isObject(service.cards.model)) {
                        //console.log('Model is null');
                        return false;
                    }
                    if (angular.isObject(label)) {
                        return _.contains(service.cards.model.labels, label);
                    }
                    if (!angular.isNumber(label)) {
                        return false;
                    }
                    var idLabels = _.pluck(service.cards.model.labels, 'id');
                    return _.contains(idLabels, label);
                },
                toggleLabel: function(label) {
                    var t, r;
                    return t = !service.cards.hasLabel(label), r = label.used + (t ? 1: -1), label.used = r, (t ? b(function(resolve, reject) {
                        c.post(e.to('card/' + service.cards.model.id + '/idLabels'), {value: label.id}).success(function(response) {
                            d.emit('WE:CARD_IDLABELS_ADDED', [service.cards.model.id, label.id]);
                            resolve(label.id);
                        });
                    }) : b(function(resolve, reject) {
                        c.delete(e.to('card/' + service.cards.model.id + '/idLabels/'+ label.id)).success(function(response) {
                            d.emit('WE:CARD_IDLABELS_DELETED', [service.cards.model.id, label.id]);
                            resolve(label.id);
                        });
                    }));
                },
                toggleMember: function(member) {
                    var t;
                    return t = !service.cards.hasMember(member.id), t ? service.cards.addMember(member) : service.cards.removeMember(member.id);
                },
                addMember: function(member) {
                    return b(function(resolve, reject) {
                        c.post(e.to('card/' + service.cards.model.id + '/idMembers'), {idMember: member.id}).success(function(response) {
                            d.emit('WE:ADDED_MEMBER_TO_CARD', [service.cards.model.id, response]);
                            resolve(response);
                        });
                    });
                },
                removeMember: function(idMember) {
                    return b(function(resolve, reject) {
                        c.delete(e.to('card/'+ service.cards.model.id + '/idMembers/' + idMember)).success(function(response) {
                            d.emit('WE:REMOVED_MEMBER_OF_CARD', [service.cards.model.id, idMember]);
                            resolve(idMember);
                        });
                    });
                },
                hasMember: function(idMember) {
                    if (angular.isUndefined(service.cards.model.members)) {
                        return false;
                    } else {
                        var idMemberList = _.pluck(service.cards.model.members, 'id');
                        return _.contains(idMemberList, idMember);
                    }
                },
                upload: function(files, errFiles) {
                    return b(function(resolve, reject) {
                        if(files.length > 0) {
                            _.defer(function() {
                                $(document.body).find('.uploading').show();
                            });
                        }
                        angular.forEach(files, function(file) {
                            file.upload = i.upload({
                                url: e.to('card/'+ service.cards.model.id + '/attachments'),
                                data: {file: file}
                            });

                            file.upload.then(function (response) {
                                h(function () {
                                    file.result = response.data;
                                    d.emit('WE:UPLOADED_FILE_TO_CARD', [service.cards.model.id, response.data]);
                                    _.defer(function() {
                                        $(document.body).find('.uploading').hide();
                                    });
                                    resolve(response.data);
                                });
                            }, function (response) {
                                if (response.status > 0) {
                                    g.pop('error', 'Failed to upload file to server.');
                                }
                            }, function (evt) {
                                file.progress = Math.min(100, parseInt(100.0 *
                                    evt.loaded / evt.total));
                            });
                        });
                    });
                },
                deleteAttachments: function($idAttachment) {
                    return b(function(resolve,reject){
                        c.delete(e.to('card/'+service.cards.model.id +'/idAttachment/'+$idAttachment)).success(function(response) {
                            d.emit('WE:DELETED_CART_ATTACHMENT',response);
                            resolve(response);
                        });
                    })
                },
                addComments: function(text) {
                    return b(function(resolve, reject) {
                        c.post(e.to('card/' + service.cards.model.id + '/comments'), {
                            content: text
                        }).success(function(response) {
                            d.emit('WE:ADDED_COMMENTS_TO_CARD', [service.cards.model.id, response]);
                            resolve(response);
                        });
                    });
                },
                updateComments: function(id, text) {
                    return b(function(resolve, reject) {
                        c.put(e.to('card/' + service.cards.model.id + '/comments/' + id), {
                            content: text
                        }).success(function(response) {
                            d.emit('WE:UPDATED_COMMENTS_OF_CARD', [service.cards.model.id, id, response]);
                            resolve(response);
                        });
                    });
                },
                deleteComments: function(id) {
                    return b(function(resolve, reject) {
                        c.delete(e.to('card/' + service.cards.model.id + '/comments/' + id)).success(function(response) {
                            d.emit('WE:DELETED_COMMENTS_OF_CARD', [service.cards.model.id, id]);
                            resolve(id);
                        });
                    });
                },
                addChecklists: function(name) {
                    return b(function(resolve, reject) {
                        c.post(e.to('card/' + service.cards.model.id + '/checklists'), {
                            name: name,
                            idBoard: service.cards.model.idBoard,
                            pos: service.cards.model.checklists.length
                        }).success(function(response) {
                            d.emit('WE:ADDED_CHECKLISTS_TO_CARD', [service.cards.model.id, response]);
                            resolve(response);
                        });
                    });
                },
                updateChecklists: function(id, model) {
                    return b(function(resolve, reject) {
                        c.put(e.to('card/' + service.cards.model.id + '/checklists/' + id), model).success(function(response) {
                            d.emit('WE:UPDATED_CHECKLISTS_OF_CARD', [service.cards.model.id, id, response]);
                            resolve(response);
                        });
                    });
                },
                deleteChecklists: function(id) {
                    return b(function(resolve, reject) {
                        c.delete(e.to('card/' + service.cards.model.id + '/checklists/' + id)).success(function(response) {
                            d.emit('WE:DELETED_CHECKLISTS_OF_CARD', [service.cards.model.id, id]);
                            resolve(id);
                        });
                    });
                },
                addChecklistItems: function(idChecklist, checklistItem) {
                    return b(function(resolve, reject) {
                        var currentChecklist = _.find(service.cards.model.checklists, {id: idChecklist});
                        c.post(e.to('card/' + service.cards.model.id + '/checklists/' + idChecklist + '/checkItems'), {
                            name: checklistItem.name,
                            due: checklistItem.due,
                            idChecklist: idChecklist,
                            pos: currentChecklist ? currentChecklist.checkItems.length : 0
                        }).success(function(response) {
                            d.emit('WE:ADDED_CHECKLISTS_ITEMS_TO_CARD', [service.cards.model.id, idChecklist, response]);
                            resolve([response,service.cards.model.id]);
                        });
                    });
                },
                updateChecklistItems: function(id, model) {
                    return b(function(resolve, reject) {
                        var currentChecklist = _.find(service.cards.model.checklists, function(checklist) {
                            return angular.isDefined(_.find(checklist.checkItems, {id: id}));
                        });
                        if (angular.isDefined(currentChecklist)) {
                            c.put(e.to('card/' + service.cards.model.id + '/checklists/' + currentChecklist.id + '/checkItems/' + id), model).success(function(response) {
                                d.emit('WE:UPDATED_CHECKLISTS_ITEMS_OF_CARD', [ service.cards.model.id, id, response, model ]);
                                resolve([response,service.cards.model.id]);
                            });
                        } else {
                            g.pop('error', 'Have error. Please try again');
                        }
                    });
                },
                deleteChecklistItems: function(id) {
                    return b(function(resolve, reject) {
                        var currentChecklist = _.find(service.cards.model.checklists, function(checklist) {
                            return angular.isDefined(_.find(checklist.checkItems, {id: id}));
                        });
                        if (angular.isDefined(currentChecklist)) {
                            c.delete(e.to('card/' + service.cards.model.id + '/checklists/' + currentChecklist.id + '/checkItems/' + id)).success(function(response) {
                                d.emit('WE:DELETED_CHECKLISTS_ITEMS_OF_CARD', [ service.cards.model.id, currentChecklist.id, id]);
                                resolve([ id , service.cards.model.id ]);
                            });
                        } else {
                            g.pop('error', 'Have error. Please try again');
                        }
                    });
                },
                /*--TA--*/
                updateCardSelected: function ($id, model, chooseCardId) {
                    return b(function (resolve, reject) {
                        c.put(e.to("card/" + $id + "/moveselected"), [model, chooseCardId]).success(function (response) {
                            d.emit('WE:UPDATE_CARD_SELECTED', response);
                            resolve(response);
                        }).error(function (error) {
                            reject(error);
                        });
                    });
                },
                /*--TA--*/
                updateCardAnotherBoard: function ($id, model, listdetail) {
                    return b(function (resolve, reject) {
                        c.put(e.to("card/" + $id + "/moveanotherboard"), model).success(function (response) {
                            d.emit('WE:UPDATE_CARD_ANOTHER_BOARD', {
                                response: response,
                                data: model,
                                listdetail: listdetail
                            });
                            resolve(response);
                        }).error(function (error) {
                            reject(error);
                        });
                    });
                }
            };

            service.export = {
                exportExcel: function(startDate, endDate, selectedBoard, selectedCard, selectedMember, type) {
                    return b(function(resolve, reject) {
                        c.post(e.to('export/export-file-excel/'), {startDate: startDate, endDate: endDate, idBoard: selectedBoard, idCards: selectedCard, idMembers: selectedMember, type: type})
                         .success( response => {
                                if (response.status == true) {
                                    if (type =='card') {
                                        URL = location.protocol  + (location.port && (location.port != config[location.protocol]) ? ':' + location.port : '');
                                        window.open(URL + "/export/ExportCard.xlsx");
                                    } else if (type =='member') {
                                        URL = location.protocol + (location.port && (location.port != config[location.protocol]) ? ':' + location.port : '');
                                        window.open(URL + "/export/ExportMember.xlsx");
                                        
                                    } else {
                                        console.log(response.dataExport);
                                    }
                                } else {
                                     alert(response.description);
                                }
                         })
                         .error( error => reject(error));
                    });
                }
            };

            service.members = {
                getListMemberIdBoard: function(id, startDate, endDate) {
                    return b(function(resolve, reject) {
                        if (id == '-1') {
                            c.post(e.to('export/get-all-member/'), {"startDate": startDate, "endDate": endDate})
                             .success(response => resolve(response))
                             .error(error => reject(error));
                        } else {
                            c.post(e.to('export/get-members/'), {"idBoard": id, "startDate": startDate, "endDate": endDate})
                             .success(response => resolve(response))
                             .error(error => reject(error));
                        }
                    });
                },
                coverAvatar: function(source) {
                    if (angular.isUndefined(source)) {
                        return;
                    } else {
                        return '/assets/img/profiles/'+source+'/50.jpg';
                    }
                },
                changePassword:function(model) {
                    return b(function(resolve,reject){
                        c.put(e.to('me/changepassword'), model).success(function(response) {
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                updateProfile:function(model) {
                    return b(function(resolve,reject){
                        c.put(e.to('me/updateprofile'), model).success(function(response) {
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },

                uploadAvatar: function(files, errFiles) {
                    return b(function(resolve, reject) {
                        if(files.length > 0) {
                            _.defer(function() {
                                $(document.body).find('.uploading').show();
                            });
                        }
                        angular.forEach(files, function(file) {
                            file.upload = i.upload({
                                url: e.to('me/changeAvatar'),
                                data: {file: file}
                            });

                            file.upload.then(function (response) {
                                h(function () {
                                    file.result = response.data;
                                    _.defer(function() {
                                        $(document.body).find('.uploading').hide();
                                    });
                                    resolve(response.data);
                                });
                            }, function (response) {
                                if (response.status > 0) {
                                    g.pop('error', 'Failed to upload file to server.');
                                }
                            }, function (evt) {
                                file.progress = Math.min(100, parseInt(100.0 *
                                    evt.loaded / evt.total));
                            });
                        });
                    });
                },
            };

            service.helpers = {
                StringHelper: {
                    trim: String.prototype.trim
                },
                arrayPushUnique: function(array, value, compareField) {
                    if (angular.isString(value)) {
                        var index = array.indexOf(value);
                        if (index < 0) {
                            array.push(value);
                        }
                    } else if(angular.isObject(value)) {
                        if (angular.isDefined(compareField)) {
                            var push = false;
                            angular.forEach(array, function(v, key) {
                                if (v.hasOwnProperty(compareField) && (v[compareField] !== value[compareField])) {
                                    push = true;
                                } else {
                                    push = false;
                                }
                            });
                            if (push) {
                                array.push(value);
                            }
                        } else {
                            var push = false;
                            angular.forEach(array, function(v, key) {
                                v = angular.fromJson(angular.toJson(v));
                                value = angular.fromJson(angular.toJson(value));
                                if (JSON.stringify(v) !== JSON.stringify(value)) {
                                    push = true;
                                } else {
                                    push = false;
                                }
                            });
                            if (push) {
                                array.push(value);
                            }
                        }
                    }
                },
                arrayRemove: function(array, value, compareField) {
                    if (angular.isString(value)) {
                        var index = array.indexOf(value);
                        if (index >= 0) {
                            array.splice(index, 1);
                        }
                    } else if(angular.isObject(value)) {
                        if (angular.isDefined(compareField)) {
                            angular.forEach(array, function(v, key) {
                                if (v.hasOwnProperty(compareField) && (v[compareField] === value[compareField])) {
                                    array.splice(key, 1);
                                }
                            });
                        } else {
                            angular.forEach(array, function(v, key) {
                                v = angular.fromJson(angular.toJson(v));
                                value = angular.fromJson(angular.toJson(value));
                                if (JSON.stringify(v) === JSON.stringify(value)) {
                                    array.splice(key, 1);
                                }
                            });
                        }
                    }
                },
                inArray: function(value, array, compareField) {
                    if (angular.isString(value)) {
                        return value.indexOf(value) >= 0;
                    } else if(angular.isObject(value)) {
                        if (angular.isDefined(compareField)) {
                            var exists = false;
                            angular.forEach(array, function(v, key) {
                                if (v.hasOwnProperty(compareField) && (v[compareField] === value[compareField])) {
                                    exists = true;
                                }
                            });
                            return exists;
                        } else {
                            var exists = false;
                            angular.forEach(array, function(v, key) {
                                v = angular.fromJson(angular.toJson(v));
                                value = angular.fromJson(angular.toJson(value));
                                if (JSON.stringify(v) === JSON.stringify(value)) {
                                    exists = true;
                                }
                            });
                            return exists;
                        }
                    }
                }
            };
            service.activity = {
                addMemberToBoard: function(idBoard) {
                    return b(function(resolve, reject) {
                        var params = {
                            idBoard: idBoard,
                            type: 'addMember',
                            idMember: a.appAssets.id
                        };
                        c.post(e.to('activity'), params).success(function(response) {
                            d.emit('WE:ADDED_MEMBER_TO_BOARD', {response: response, idBoard: idBoard});
                            resolve(response);
                        }).error(function(error) {
                            reject(error);
                        });
                    });
                },
                changeBackground: function(member) {
                    var mes = f.action_changed_board_background;
                    mes = mes.replace(/\{member\}/, '')
                    d.emit('WE:BOARD_BACKGROUND_CHANGED', 123);
                }
            };
            service.search = {
                searchAll : function($params) {
                    return b(function(resolve, reject) {

                        c.get(e.to('search'),{
                            params: $params
                        }).success(function(response) {
                            resolve(response)
                        }).error(function(error) {
                            reject(error);
                        })
                    })
                }
            };
            return service;
        }])
        .service('Notification',['$rootScope', '$q', '$http', 'Socket', 'API_CONFIG', 'ACTION_ACTIVITY', 'toaster', '$timeout', '$filter',function(a, b, c, d, e, f, g, h, i){
            var service        = this;
            var queryAssets = {};
            queryAssets.expandNotifications = 'notifications(id.data.type.name|members.notifyMember.effectMember)';

            service.readAll = function($id) {
                return b(function(resolve, reject) {
                    c.put(e.to('notifications/readall')).success(function(response) {
                        a.$broadcast('E:NOTIFICATIONS_UPDATE', response);
                        resolve(response);
                    }).error(function(error) {
                        reject(error);
                    });
                });
            };

            service.getNotification = function() {
                return b(function(resolve, reject) {
                    c.get(e.to('notifications'), {
                        params: {
                            expand:'members,'+queryAssets.expandNotifications,
                        }
                    }).success(function(response) {
                        a.$broadcast('E:NOTIFICATIONS_GET', response);
                        resolve(response);
                    }).error(function(error) {
                        reject(error);
                    });
                });
            };

            service.getNotificationBoard = function(idBoard,type) {
                return b(function(resolve, reject) {
                    c.get(e.to('notifications'), {
                        params: {
                            idBoard: idBoard,
                            type:type,
                            expand:'members,'+queryAssets.expandNotifications
                        }
                    }).success(function(response) {
                        resolve(response);
                    }).error(function(error) {
                        reject(error);
                    });
                });
            };

            service.getNotificationlimit = function(limit,perpage,type,paramObject) {
                return b(function(resolve, reject) {
                    //set param
                    var $params = {
                        per_page:perpage,
                        limit:limit,
                        type:type,
                        expand:'members,'+queryAssets.expandNotifications
                    }
                    if(angular.isObject(paramObject))
                        $params  = angular.extend($params, paramObject);

                    c.get(e.to('notifications'), {
                        params: $params
                    }).success(function(response) {
                        a.$broadcast('E:NOTIFICATIONS_GET', response);
                        resolve(response);
                    }).error(function(error) {
                        reject(error);
                    });
                });
            };
            return service;
        }]);
})(window, window.angular);