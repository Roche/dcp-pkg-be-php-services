<?php

return [
    'UserQuery'             => [
        'method' => 'GET',
        'url'    => 'core/v2/user/query',
        'params' => ['UnixId?', 'UserId?', 'SiteId?', 'Email?', 'Permission?', 'Module?'],
        'type'   => 'UserData[]'
    ],
    'UserInfo' => [
        'method' => 'GET',
        'url'    => 'core/v2/user/info',
        'type'   => 'UserInfoData'
    ],
    'ModuleConfiguration'             => [
        'method' => 'GET',
        'url'    => 'core/v2/configuration/module/query',
        'params' => ['SiteId?', 'Module?', 'Resource?', 'IncludeDisabledModules?'],
        'type'   => 'ModuleConfigurationData[]'
    ],
    'DatasourceConfiguration'        => [
        'method' => 'GET',
        'url'    => 'core/v2/configuration/datasource/query',
        'params' => ['SiteId?', 'DataType?', 'Module?'],
        'type'   => 'DataSourceData[]'
    ],
    'AuditTrailAdminActionCreate' => [
        'method' => 'POST',
        'url'    => 'core/v2/audit-trail/admin-actions/entry/create',
        'params' => [
            'UserId',
            'Action',
            'Timestamp',
            'ActionConfiguration',
            'Reason',
            'Module',
            'Exception',
            'IsGxp'
        ],
    ],
    'SiteQuery' => [
        'method' => 'GET',
        'url'    => 'core/v2/site/query',
        'params' => ['SiteId?', 'SiteName?', 'ShortName?'],
        'type'   => 'SiteData[]'
    ],
];
