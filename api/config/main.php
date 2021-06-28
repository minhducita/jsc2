<?php
$params = array_merge(
    require(__DIR__ . '/params.php'),
    require(__DIR__ . '/../../common/config/params.php'),
    require(__DIR__ . '/../../common/config/params-local.php')
);
return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'TimeZone' => 'Asia/Ho_Chi_Minh',
    'modules' => [
        'v1' => [
            'basePath' => '@api/modules/v1',
            'class' => 'api\modules\v1\Module'
        ]
    ],
    'components' => [
		'slack' => [
			'httpclient' => [
                'class' => 'yii\httpclient\Client',
            ],
            'class' => 'understeam\slack\Client',
            'url' => 'https://hooks.slack.com/services/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',
            'username' => 'Jsc',
        ],
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'dateFormat' => 'php:d-M-Y',
            'datetimeFormat' => 'php:d-M-Y H:i:s',
            'timeFormat' => 'php:H:i:s',
        ],
        'user' => [
            'class' => 'yii\web\User',
            'identityClass' => 'api\common\models\Member',
            'enableAutoLogin' => false,
            'enableSession' => false,
            'loginUrl' => null,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'urlManager' => [
            'class' => 'yii\web\UrlManager',
            'enablePrettyUrl'=>true,
            'showScriptName' =>false,
            'rules' => [
				'PUT <module:\w+>/notifications/readall'     => '<module>/notifications/read-all',
				'PUT <module:\w+>/me/changepassword' 		=> '<module>/me/change-password',
				'PUT <module:\w+>/me/updateprofile' 		=> '<module>/me/update-profile',
				'POST <module:\w+>/me/changeAvatar' => '<module>/me/change-avatar',
                'POST v1/site/login' => 'v1/site/login',
                'OPTIONS <module:\w+>/<params:[a-zA-Z0-9一-龠ぁ-ゔァ-ヴー々〆〤０-９Ａ-ｚァ-ンｧ-ﾝﾞﾟ \-\/]*>' => '<module>/site/options',
                'POST <module:\w+>/board/<id:\d+>/boardStars' => '<module>/board/star-board',// add boardStart
                'DELETE <module:\w+>/board/<id:\d+>/boardStars' => '<module>/board/un-starboard',// add boardStart
                'PUT <module:\w+>/card/<id:\d+>/moveall' => '<module>/card/update-card-all',
				'PUT <module:\w+>/card/<id:\d+>/moveselected' => '<module>/card/update-card-selected',//TA CARD MOVE SELECTED
                'PUT <module:\w+>/card/<id:\d+>/moveanotherboard' => '<module>/card/update-card-another-board',//TA CARD MOVE ANOTHER BOARD
				'POST <module:\w+>/card/<id:\d+>/labels' => '<module>/card/create-label',
                'POST <module:\w+>/card/<id:\d+>/idLabels' => '<module>/card/add-idlabel',
                'DELETE <module:\w+>/card/<id:\d+>/idLabels/<idLabels:\d+>' => '<module>/card/delete-idlabel',
                'POST <module:\w+>/card/<id:\d+>/idMembers' => '<module>/card/create-member',
                'DELETE <module:\w+>/card/<id:\d+>/idMembers/<idMember:\d+>' => '<module>/card/delete-member',
                'POST <module:\w+>/card/<id:\d+>/attachments' => '<module>/card/attachments',
				'DELETE <module:\w+>/card/<id:\d+>/idAttachment/<idAttachment:\d+>' => '<module>/card/delete-attachments',
                'POST <module:\w+>/card/<id:\d+>/checklists' => '<module>/card/create-checklists',
                'PUT <module:\w+>/card/<id:\d+>/checklists/<idChecklist:\d+>' => '<module>/card/update-checklists',
                'DELETE <module:\w+>/card/<id:\d+>/checklists/<idChecklist:\d+>' => '<module>/card/delete-checklists',
                'POST <module:\w+>/card/<id:\d+>/checklists/<idChecklist:\d+>/checkItems' => '<module>/card/create-checkitem',
                'PUT <module:\w+>/card/<id:\d+>/checklists/<idChecklist:\d+>/checkItems/<idCheckitem:\d+>' => '<module>/card/update-checkitem',
                'DELETE <module:\w+>/card/<id:\d+>/checklists/<idChecklist:\d+>/checkItems/<idCheckitem:\d+>' => '<module>/card/delete-checkitem',
                'POST <module:\w+>/card/<id:\d+>/comments' => '<module>/card/create-comment',
                'PUT <module:\w+>/card/<id:\d+>/comments/<idComment:\d+>' => '<module>/card/update-comment',
                'DELETE <module:\w+>/card/<id:\d+>/comments/<idComment:\d+>' => '<module>/card/delete-comment',
                'POST <module:\w+>/card/<id:\d+>/copyCard' => '<module>/card/copy-card',
				'PUT <module:\w+>/<controller:\w+>/sort' => '<module>/<controller>/sort',
                'PUT <module:\w+>/organization/<idOrganization:\d+>/members/<idMember:\d+>' => '<module>/organization/make-members',
                'POST <module:\w+>/organization/<id:\d+>/attachments' => '<module>/organization/attachments',
				'DELETE <module:\w+>/organization/<id:\d+>/deletelogo' => '<module>/organization/delete-logo',
				'DELETE <module:\w+>/organization/<idOrganization:\d+>/members/<idMember:\d+>' => '<module>/organization/delete-members',
                'PUT <module:\w+>/board/<id:\d+>/members/<idMember:\d+>' => '<module>/board/make-members',
                'DELETE <module:\w+>/board/<id:\d+>/members/<idMember:\d+>' => '<module>/board/delete-members',
                'GET,HEAD <module:\w+>/card/updatebadges/'=> '<module>/card/badges-auto',
				'GET,HEAD <module:\w+>/<controller:\w+>' => '<module>/<controller>/index',
				'GET,HEAD <module:\w+>/<controller:\w+>/<id:[0-9]+>/board' => '<module>/<controller>/view-board',
                'GET,HEAD <module:\w+>/<controller:\w+>/<id:[a-zA-Z0-9一-龠ぁ-ゔァ-ヴー々〆〤０-９Ａ-ｚァ-ンｧ-ﾝﾞﾟ\-]+>' => '<module>/<controller>/view',
                'GET,HEAD <module:\w+>/<controller:\w+>/<id:[a-zA-Z0-9一-龠ぁ-ゔァ-ヴー々〆〤０-９Ａ-ｚァ-ンｧ-ﾝﾞﾟ\-]+>/<option:[a-zA-Z0-9一-龠ぁ-ゔァ-ヴー々〆〤０-９Ａ-ｚァ-ンｧ-ﾝﾞﾟ\-]+>' => '<module>/<controller>/view',
				'POST <module:\w+>/<controller:\w+>' => '<module>/<controller>/create',
                'PUT <module:\w+>/<controller:\w+>/<id:\d+>' => '<module>/<controller>/update',
                'DELETE <module:\w+>/<controller:\w+>/<id:\d+>' => '<module>/<controller>/delete',
				'PUT <module:\w+>/labels/<id:\d+>/idCards/<idCard:\d+>' => '<module>/labels/delete-label'
            ],
        ],

    ],
    'params' => $params,
];

