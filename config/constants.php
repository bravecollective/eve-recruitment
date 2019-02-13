<?php

return [
    // Don't change any of the defaults here. Only add to the list
    'permissions' => [
        'VIEW_CORP_APPS'            => 'view-corp-applications',
        'VIEW_CORP_MEMBERS'         => 'view-corp-members',
        'MANAGE_CORP_AD'            => 'manage-corp-recruitment-ad',
        'MANAGE_CORP_PERMISSIONS'   => 'manage-corp-permissions',
        'MANAGE_GROUP_AD'           => 'create-group-recruitment-ad',
        'MANAGE_GROUP_PERMISSIONS'  => 'manage-group-recruitment-permissions',
        'MANAGE_GLOBAL_PERMISSIONS' => 'manage-global-permissions',
        'MANAGE_ALL_ADS'            => 'manage-global-recruitment-ads'
    ],
    'roles' => [
        'recruiter' => [
            'VIEW_CORP_APPS',
            'VIEW_CORP_MEMBERS'
        ],
        'director' => [
            'VIEW_CORP_APPS',
            'VIEW_CORP_MEMBERS',
            'MANAGE_CORP_AD',
            'MANAGE_CORP_PERMISSIONS',
            'MANAGE_GROUP_AD',
            'MANAGE_GROUP_PERMISSIONS'
        ],
        'admin' => [
            'VIEW_CORP_APPS',
            'VIEW_CORP_MEMBERS',
            'MANAGE_CORP_AD',
            'MANAGE_CORP_PERMISSIONS',
            'MANAGE_GROUP_AD',
            'MANAGE_GROUP_PERMISSIONS',
            'MANAGE_GLOBAL_PERMISSIONS',
            'MANAGE_ALL_ADS'
        ]
    ]
];