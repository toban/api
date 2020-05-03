<?php

/** @var Router $router */
use Laravel\Lumen\Routing\Router;

// GET
$router->get('wiki/count', ['uses' => 'WikisController@count']);
// POST
$router->post('auth/login', ['uses' => 'Auth\LoginController@login']);
// TODO actually use logout route in VUE app..
$router->post('auth/logout', ['uses' => 'Auth\LoginController@logout']);
$router->post('user/register', ['uses' => 'Auth\RegisterController@register']);
// TODO finish converting for laravel below here
$router->post('user/verifyEmail', ['uses' => 'UserVerificationTokenController@verify']);
$router->post('user/sendVerifyEmail', ['uses' => 'UserVerificationTokenController@createAndSendForUser']);
$router->post('user/forgotPassword', ['uses' => 'Auth\ForgotPasswordController@sendResetLinkEmail']);
$router->post('user/resetPassword', ['uses' => 'Auth\ResetPasswordController@reset']);

$router->post('interest/register', ['uses' => 'InterestController@create']);

// Authed
$router->group(['middleware' => ['auth:api']], function () use ($router) {
    // user
    $router->group(['prefix' => 'user'], function () use ($router) {
        $router->post('self', ['uses' => 'UserController@getSelf']);
    });
    // wiki
    $router->group(['prefix' => 'wiki'], function () use ($router) {
        // TODO wiki id should probably be in the path of most of these routes...
        $router->post('create', ['uses' => 'WikiController@create']);
        $router->post('delete', ['uses' => 'WikiController@delete']);
        $router->post('mine', ['uses' => 'WikisController@getWikisOwnedByCurrentUser']);
        $router->post('details', ['uses' => 'WikiController@getWikiDetailsForIdForOwner']);
        $router->post('logo/update', ['uses' => 'WikiLogoController@update']);
        $router->post('setting/{setting}/update', ['uses' => 'WikiSettingController@update']);
        // TODO should wiki managers really be here?
        $router->post('managers/list', ['uses' => 'WikiManagersController@getManagersOfWiki']);
    });
    // admin
    $router->group(['prefix' => 'admin', 'namespace' => 'Admin', 'middleware' => ['admin']], function () use ($router) {
        // invitation
        $router->group(['prefix' => 'invitation'], function () use ($router) {
            $router->post('list', ['uses' => 'InvitationsController@get']);
            $router->post('create', ['uses' => 'InvitationController@create']);
            $router->post('delete', ['uses' => 'InvitationController@delete']);
        });
        // interest
        $router->group(['prefix' => 'interest'], function () use ($router) {
            $router->post('list', ['uses' => 'InterestsController@get']);
            // $router->post('create', ['uses' => 'InvitationController@create']);
        // $router->post('delete', ['uses' => 'InvitationController@delete']);
        });
    });
});
