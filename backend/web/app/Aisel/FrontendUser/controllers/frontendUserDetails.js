'use strict';

/**
 * This file is part of the Aisel package.
 *
 * (c) Ivan Proskuryakov
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @name            AiselFrontendUser
 * @description     ...
 */

define(['app'], function (app) {
    app.controller('FrontendUserDetailCtrl', function ($scope, $stateParams, frontendUserService, $state, Environment) {
        $scope.details = {
            id: $stateParams.id,
            name: 'Frontend User'
        };
        var handleSuccess = function (data, status) {
            $scope.item = data;
        };
        frontendUserService.get($scope.details.id).success(handleSuccess);

        $scope.editCancel = function () {
            $state.transitionTo('frontendUsers', {locale: Environment.currentLocale()});
        }
    });
});