/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
(function(window, angular, undefined) { 'use strict';
   angular.module('app.factorys', [])
       .factory('Board', function() {

       })
})(window, window.angular);

function Lists(list)
{
    list = angular.merge(list, {card: []});
    return list;
}