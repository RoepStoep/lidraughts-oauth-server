<?php

namespace App;

use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use PDO;

return [
    'authenticate_url' => 'https://lidraughts.org/login?referrer=%s',
    'authenticate_cookie' => 'lidraughts2',
    'check_authentication_url' => 'https://lidraughts.org/account/info',
    'private_key_path' => __DIR__ . '/../../data/private.key',
    'encryption_key' => '',

    'grant_enabled_auth_code' => true,
    'grant_enabled_client_credentials' => false,
    'grant_enabled_refresh_token' => true,

    // Based on https://en.wikipedia.org/wiki/ISO_8601#Durations
    'ttl_access_token' => 'P20Y',
    'ttl_auth_code' => 'PT10M',
    'ttl_refresh_token' => 'P20Y',

    'pdo' => [
        'dsn' => '',
        'username' => '',
        'password' => '',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ],
    ],

    'mongodb' => [
        'uri' => 'mongodb://127.0.0.1/',
        'uriOptions' => [],
        'driverOptions' => [],
        'database' => 'lidraughts',
        'collections' => [
            'access_token' => 'oauth_access_token',
            'authorization_code' => 'oauth_authorization_code',
            'client' => 'oauth_client',
            'refresh_token' => 'oauth_refresh_token',
        ],
    ],

    'dependencies' => [
        'factories' => [
            AuthCodeRepositoryInterface::class => OAuth\Repository\Mongo\Factory\AuthCodeFactory::class,
            AccessTokenRepositoryInterface::class => OAuth\Repository\Mongo\Factory\AccessTokenFactory::class,
            ClientRepositoryInterface::class => OAuth\Repository\Mongo\Factory\ClientFactory::class,
            RefreshTokenRepositoryInterface::class => OAuth\Repository\Mongo\Factory\RefreshTokenFactory::class,
            ScopeRepositoryInterface::class => OAuth\Repository\Mongo\Factory\ScopeFactory::class,
        ],
    ],
];
