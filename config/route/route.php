<?php

/**
 * Array of configuration of routing by url
 * Key is a requested link
 * Value is a controller name:function for execute
 *
 * @var array
 */

return [
	'route' => [
        '/' => 'auth:AuthController',
        'api/test' => 'api:testOAuthController',//Тестовая страница для проверки API и OAuth
        /*'oauth/createtestapp' => 'oauth:CreateTestAppOAuthController',*///Создание приложения для oAuth
        'oauth/authorize_code' => 'oauth:GetAuthorizationCode',//Страница генерации авторизационного кода
        'oauth/gettoken' => 'oauth:GetToken',//Возвращает токен
        'oauth/getaccessapi' => 'oauth:GetAccessAPI',//Получает доступ к API

        'ip_conv_ip_to_binary_32' => 'ip_conv:ip_to_binary_32',

        'api/help' => 'api:helpPage',
        'api/v1/api_sequencer.get'=>'api:getStatAPISequencer',
        'api/v1/api_sequencer.get_user_property'=>'api:getUserProperty',
        'api/v1/utmorder.get' => 'api:getUtmOrder',
        'api/v1/repeate_actions.get' => 'api:getRepeateActions',
        'api/v1/create_segment' => 'api:createSegmentAction',
        'api/v1/find_segment' => 'api:findSegment',

        'api/v2/events' => 'api:eventsProcess',
        'api/v2/campaigns' => 'api:campaignsProcess',
        'api/v2/sequence' => 'api:sequenceProcess',
        'api/v2/bot' => 'api:botProcess',
        'api/v2/historyorder' => '\\api\\APIv2.historyOrder',

        'api/v2/logins' => [
            'handler' => ['oauth'],
            'use' => 'api:getLogin'
        ],
        'api/v2/custom' => 'api:customEventsProcess',
        'api/v2/ptv-test-insert' => 'api:ptvTestInsertAction',
        'api/v2/ptv-test-select-by-event' => 'api:ptvTestSelectDataByEventIdAction',

        'chains' => [
            'handler' => ['auth'],
            'use' => 'chains:show'
        ],
        'chains/get' => 'chains:get_data',

        'sequence/get.domain' => 'misk_sequence:contGetDomain',
        'sequence/get.listcity' => 'misk_sequence:contGetListCity',
        'sequence/get.listips' => 'misk_sequence:contGetListIPS',
        'sequence/get.listipdiap' => 'misk_sequence:contGetListIPDIAP',

        'condition_user_property' => 'misk_sequence:contGetUserPropertyCond',
        'condition_user_property/add' => 'misk_sequence:contChangeUserPropertyCondAdd',
        'condition_user_property/update/[0-9]*' => 'misk_sequence:contChangeUserPropertyCondUpd',
        'condition_user_property/delete/[0-9]*' => 'misk_sequence:contDelUserPropertyCond',

        'maindomain' => 'main:MainByDomainController',
        'mainreferrer' => 'main:MainByReferrerController',
        'logout' => 'auth:AuthLogoutController',
        'fastdomain' => 'main:MainByFastDomainController',
        'fastreferrer' => 'main:MainByFastReferrerController',

        'groupfilter' => 'setting:SettingSetFilterGroupController',
        'groupfilter/add' => 'setting:SettingSetFilterGroupAddController',
        'groupfilter/update/[0-9]*' => 'setting:SettingSetFilterGroupUpdateController',
        'groupfilter/delete/[0-9]*' => 'setting:SettingSetFilterGroupDeleteController',
        'top' => [
            'handler' => ['auth'],
            'use' => 'top:TopController'
        ],
        'sequence' => [
            'handler' => ['auth'],
            'use' => 'sequence:showStartFormController'
        ],
        'events' => 'events:showStartPage',
        'events/add' => 'events:showAddEventForm',
        'events/update/[a-zA-Z0-9{}_%]*' => 'events:showUpdateEventForm',
        'events/delete/[a-zA-Z0-9{}_%]*' => 'events:showDeleteEventForm',
        'partners' => [
            'handler' => ['auth'],
            'use' => 'partners:partners_showList'
        ],
        'partners/add' => [
            'handler' => ['auth'],
            'use' => 'partners:showAddPartnersForm'
        ],
        'partners/update/[0-9]*' => [
            'handler' => ['auth'],
            'use' => 'partners:showUpdatePartnersForm'
        ],
        'partners/delete/[0-9]*' => [
            'handler' => ['auth'],
            'use' => 'partners:showDeletePartnersForm'
        ],
        'eventslist' => [
            'handler' => ['auth'],
            'use' => 'eventslist:showEventsList'
        ],
        'refererdiff' => 'referer_diff:referrerDiff_start',

        'ads' => 'ads:adsStartPage',
        'ads/add' => 'ads:adsAddAd',
        'ads/update/[0-9]*' => 'ads:showUpdateForm',
        'ads/delete/[0-9]*' => 'ads:showDeleteForm',

        'admin/tables' => 'admin:showTablesInfo',
        'top_referers' => [
            'use' => 'top_referers:showTopReferers',
            'handler' => ['auth']
        ],
        'url_actions' => [
            'use' => 'url_actions:showUrlActions',
            'handler' => ['auth']
        ],
        'visitors'  => [
            'use' => 'visitors:showVisitorsList',
            'handler' => ['auth']
        ],
        'userslogin' => [
            'use' => 'UsersLogin\\UsersLogin.showPair',
            'handler' => ['auth']
        ]
    ]
];