/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
(function (window, angular, undefined) {
    'use strict';
    angular.module('app.autocomplete', [])
        .directive('autocomplete', function ($sce, $timeout, AppService) {
            var index = -1;
            return {
                restrict: 'E',
                scope: {
                    searchParam: '=ngModel',
                    suggestions: '=data',
                    onSearch: '=onSearch',
                    onSelect: '=onSelect',
                    autocompleteRequired: '=',
                    disabledMemberExits: '=disabledMemberExits'
                },
                controller: ['$scope', function ($scope) {
                    $scope.selectedIndex = -1;
                    $scope.lock = true;

                    $scope.setIndex = function (i) {
                        $scope.selectedIndex = parseInt(i);
                    };
                    $scope.getIndex = function () {
                        return $scope.selectedIndex;
                    };
                    // for hovering over suggestions
                    this.preSelect = function (suggestion) {

                        watching = false;

                        // this line determines if it is shown
                        // in the input field before it's selected:
                        //$scope.searchParam = suggestion;

                        $scope.$apply();
                        watching = true;

                    };

                    $scope.preSelect = this.preSelect;

                    this.preSelectOff = function () {
                        watching = true;
                    };

                    $scope.preSelectOff = this.preSelectOff;


                    var watching = true;
                    $scope.completing = false;
                    $scope.$watch('searchParam', function (newValue, oldValue) {
                        if (newValue === oldValue || (!oldValue && $scope.lock)) {
                            return;
                        }
                        if (watching && typeof $scope.searchParam !== 'undefined' && $scope.searchParam !== null) {
                            $scope.completing = true;
                            $scope.searchFilter = $scope.searchParam;
                            $scope.selectedIndex = -1;
                        }
                        if ($scope.onSearch && $scope.searchParam.length >= 1) {
                            $timeout(function () {
                                $scope.onSearch($scope.searchParam);
                            }, 500);
                        }
                    });

                    $scope.select = function (suggestions) {
                        if (suggestions) {
                            if (angular.isDefined($scope.disabledMemberExits) && AppService.helpers.inArray(suggestions, $scope.disabledMemberExits, 'id')) {
                                return false;
                            }
                        } else {
                            return false;
                        }
                        $scope.searchParam = '';
                        $scope.searchFilter = suggestions;
                        if ($scope.onSelect) {
                            $scope.onSelect(suggestions);
                        }
                        watching = false;
                        $scope.completing = false;
                        setTimeout(function () {
                            watching = true;
                        }, 1000);
                        $scope.setIndex(-1);
                    };
                    $scope.memberExist = function(member) {
                        return angular.isDefined($scope.disabledMemberExits) && AppService.helpers.inArray(member, $scope.disabledMemberExits, 'id');
                    };


                }],
                link: function (scope, element, attrs) {
                    $timeout(function () {
                        scope.lock = false;
                    }, 250);
                    var attr = '';
                    scope.attrs = {
                        placeholder: 'Searching...',
                        id: '',
                        class: '',
                        inputid: '',
                        inputclass: 'form-control form-control-small'
                    };
                    for (var a in attrs) {
                        attr = a.replace('attr', '').toLowerCase();
                        // add attribute overriding defaults
                        // and preventing duplication
                        if (a.indexOf('attr') === 0) {
                            scope.attrs[attr] = attrs[a];
                        }
                    }
                    if (attrs.clickActivation) {
                        element[0].onclick = function (e) {
                            if (!scope.searchParam) {
                                $timeout(function () {
                                    scope.completing = true;
                                }, 200);
                            }
                        };
                    }

                    var key = {left: 37, up: 38, right: 39, down: 40, enter: 13, esc: 27, tab: 9};

                    document.addEventListener("keydown", function (e) {
                        var keycode = e.keyCode || e.which;

                        switch (keycode) {
                            case key.esc:
                                // disable suggestions on escape
                                scope.select();
                                scope.setIndex(-1);
                                scope.$apply();
                                e.preventDefault();
                        }
                    }, true);

                    document.addEventListener("blur", function (e) {
                        // disable suggestions on blur
                        // we do a timeout to prevent hiding it before a click event is registered
                        setTimeout(function () {
                            scope.select();
                            scope.setIndex(-1);
                            scope.$apply();
                        }, 150);
                    }, true);

                    element[0].addEventListener("keydown", function (e) {
                        var keycode = e.keyCode || e.which;

                        var l = angular.element(this).find('li').length;

                        // this allows submitting forms by pressing Enter in the autocompleted field
                        if (!scope.completing || l == 0) return;

                        // implementation of the up and down movement in the list of suggestions
                        switch (keycode) {
                            case key.up:

                                index = scope.getIndex() - 1;
                                if (index < -1) {
                                    index = l - 1;
                                } else if (index >= l) {
                                    index = -1;
                                    scope.setIndex(index);
                                    scope.preSelectOff();
                                    break;
                                }
                                scope.setIndex(index);

                                if (index !== -1)
                                    scope.preSelect(angular.element(angular.element(this).find('li')[index]).text());

                                scope.$apply();

                                break;
                            case key.down:
                                index = scope.getIndex() + 1;
                                if (index < -1) {
                                    index = l - 1;
                                } else if (index >= l) {
                                    index = -1;
                                    scope.setIndex(index);
                                    scope.preSelectOff();
                                    scope.$apply();
                                    break;
                                }
                                scope.setIndex(index);

                                if (index !== -1)
                                    scope.preSelect(angular.element(angular.element(this).find('li')[index]).text());

                                break;
                            case key.left:
                                break;
                            case key.right:
                            case key.enter:
                            case key.tab:

                                index = scope.getIndex();
                                // scope.preSelectOff();
                                if (index !== -1) {
                                    scope.select(angular.element(angular.element(this).find('li')[index]).text());
                                    if (keycode == key.enter) {
                                        e.preventDefault();
                                    }
                                } else {
                                    if (keycode == key.enter) {
                                        scope.select();
                                    }
                                }
                                scope.setIndex(-1);
                                scope.$apply();

                                break;
                            case key.esc:
                                // disable suggestions on escape
                                scope.select();
                                scope.setIndex(-1);
                                scope.$apply();
                                e.preventDefault();
                                break;
                            default:
                                return;
                        }

                    });
                },
                template: '\
        <div class="angucomplete-holder" id="{{ attrs.id }}">\
          <input\
            type="text"\
            ng-model="searchParam"\
            placeholder="{{ attrs.placeholder }}"\
            class="{{ attrs.inputclass }}"\
            id="{{ attrs.inputid }}"\
            ng-required="{{ autocompleteRequired }}" />\
            <div class="angucomplete-dropdown" ng-show="completing && (suggestions | filter:searchFilter).length > 0">\
                <div class="angucomplete-searching" ng-show="(!suggestions || suggestions.length == 0)">No results found</div>\
                <div class="angucomplete-row" ng-repeat="suggestion in suggestions | filter:searchFilter | orderBy:\'toString()\' track by $index" ng-class="{\'angucomplete-selected-row\': $index == selectedIndex, \'disabled\': memberExist(suggestion) == true }" ng-click="select(suggestion)">\
                    <div ng-if="suggestion.avatarHash || suggestion.initialsName" class="angucomplete-image-holder">\
                        <img ng-if="suggestion.typeimg == 1" ng-src="/assets/img/profiles/{{suggestion.avatarHash}}/30.png" class="angucomplete-image"/>\
                        <div ng-if="suggestion.typeimg == 0 && suggestion.initialsName" class="angucomplete-image-default">{{suggestion.initialsName}}</div>\
                    </div>\
                    <div class="angucomplete-title" ng-if="!matchClass"><b>{{ suggestion.displayName }}</b> <span ng-show="memberExist(suggestion)">(joined)</span></div>\
                    <div class="angucomplete-description"> ({{suggestion.username}})</div>\
                </div>\
            </div>\
        </div>'
            }
        })
        .filter('highlight', ['$sce', function ($sce) {
            return function (input, searchParam) {
                if (typeof input === 'function') return '';
                if (searchParam) {
                    var words = '(' +
                            searchParam.split(/\ /).join(' |') + '|' +
                            searchParam.split(/\ /).join('|') +
                            ')',
                        exp = new RegExp(words, 'gi');
                    if (words.length) {
                        input = input.replace(exp, "<span class=\"highlight\">$1</span>");
                    }
                }
                return $sce.trustAsHtml(input);
            };
        }])

        .directive('suggestion', function () {
            return {
                restrict: 'A',
                require: '^autocomplete', // ^look for controller on parents element
                link: function (scope, element, attrs, autoCtrl) {
                    element.bind('mouseenter', function () {
                        autoCtrl.preSelect(attrs.val);
                        autoCtrl.setIndex(attrs.index);
                    });

                    element.bind('mouseleave', function () {
                        autoCtrl.preSelectOff();
                    });
                }
            };
        })
})(window, window.angular);
