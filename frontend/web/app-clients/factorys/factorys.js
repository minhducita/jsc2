/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
(function(window, angular, undefined) {
'use strict';
angular.module('app.factorys', [])
    .factory('Socket', ['$rootScope', '$websocket', 'WS_CONFIG', function(a, b, c) {
        var ws = b.$new(c.url());
        ws.$on('$open', function () {
            console.log('The ngWebsocket has open!');
        });
        return {
            on: function(eventName, cb) {
                ws.$on(eventName, function () {
                    var args = arguments;
                    a.$apply(function () {
                        cb.apply(ws, args);
                    });
                });
            },
            emit: function(eventName, data, cb) {
                ws.$emit(eventName, data, function () {
                    var args = arguments;
                    a.$apply(function () {
                        if (cb) {
                            cb.apply(ws, args);
                        }
                    });
                });
            }
        };

    }])
    .factory('Elements', function() {
        var Elements = {};
        Elements.createElement = function(tag, config, content) {
            var el  = document.createElement(tag), $el = $(el);
            if (typeof config === 'string') {
                if (config[0] === '#') {
                    $el.attr('id', config.substring(1));
                } else if (config[0] === '.') {
                    $el.attr('class', config.substring(1));
                } else if (config.match(/\s/g)) {
                    $el.attr('class', config);
                }  else {
                    $el.attr('id', config);
                }
            } else if (typeof config === 'object') {
                angular.forEach(config, function(value, attrs) {
                    $el.attr(attrs, value);
                });
            }
            if (angular.isDefined(content)) {
                $el.append(content);
            }
            el = null;
            return $el;
        };
        var ElementsList = ['div', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'a', 'i', 'button', 'ul', 'li', 'img', 'label', 'textarea', 'input', 'span', 'toggle'];
        angular.forEach(ElementsList, function(value, key) {
            Elements[value] = function(config, child) {
                var el = Elements.createElement(value, config, child);
                return el.prop('outerHTML');
            };
        });
        return Elements;

    })
    .factory('popupViews', ['Elements', function(a) {
        return {
            addCardToList: function() {
                var temp = a.div('card-composer add-card-form',
                                a.form({'ng-submit': "createCard({idList: list.id, displayName: input.displayName, important: input.importantInput, urgent: input.urgentInput})"},
                                    a.div('list-card js-composer',
                                        a.div('.u-clearfix',
                                            a.div('list-card-labels u-clearfix js-list-card-composer-labels') +
                                            a.textarea({'ng-model': 'input.displayName', class: 'list-card-composer-textarea js-card-title', rows: 5}) +
                                            a.div('list-card-members js-list-card-composer-members')
                                        )
                                    )
                                    +
                                    a.div('cc-controls u-clearfix',
                                        a.input({type:'submit', value: 'Add', class: 'btn btn-primary btn-mini'}) +
                                        a.toggle({'ng-model': 'input.importantInput', 'onstyle':"btn-success"}) +
										a.toggle({'ng-model': 'input.urgentInput', 'onstyle':"btn-danger"}) +
                                        a.a('icon-lg flaticon-cross-mark1 js-cancel') +
										a.a({'pop-pup':"",'open-type':'popover','template-url':"lists/main.tpl.html",class:'cc-opt icon-sm icon-dropdown-menu dark-hover'})
                                    )
                                )
                            );
                return temp;
            },
            updateListName: function() {
                var temp = a.div('cc-controls u-clearfix update-list-name-form',
                                a.input({type: 'submit', value: 'Save', class: 'btn btn-primary btn-mini'}) +
                                a.a('icon-lg flaticon-cross-mark1 js-cancel')
                           );
                return temp;
            }
        }
    }])
    .factory('activitys', ['Elements', 'ACTION_ACTIVITY', function(a, b) {
        return {
            members: function(member) {
                var temp = a.div('.action-item',
                    a.div('.action-mem-img',
                        a.img({src: "{{member.avatarHash | memberAvatar}}", width: 35})
                    ) +
                    a.div('.action-mem-info',
                        b.action_changed_board_background.replace(/\{memberCreator\}/g, a.span({"style": "font-weight: bold; font-size: 15px; color: #3C5059", width: 35}, "{{member.displayName}}")) +
                        a.p({}, " <time-ago from-time='{{ boardChangedBackgroundTime }}'></time-ago>")
                    )
                );
                return temp;
            }
        }
    }])
})(window, window.angular);