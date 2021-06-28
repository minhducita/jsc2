<?php
return [
    'components' => [
        /* 'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=192.168.0.170;dbname=jooto',
            'username' => 'root',
            'password' => 'bluecloud123456',
            'charset' => 'utf8',
        ],*/
		'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'mysql:host=localhost;dbname=jsc',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
			'enableSchemaCache' => false,
            'schemaCacheDuration' => 3600,
            'schemaCache' => 'cache',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            'viewPath' => '@common/mail',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
    ],
];
