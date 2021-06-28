<?php

namespace api\modules\v1\controllers;

use api\modules\v1\models\PasswordForm;
use yii\filters\ContentNegotiator;
use yii\rest\Controller;
use Yii;

class TestController extends Controller
{
    public function actionShow()
    {
        $request = Yii::$app->request;

        Yii::warning("=============show");
        $dialog = [
            'callback_id' => 'sendMessage',
            'title' => 'Create Card',
            'submit_label' => 'Create',
            'elements' => [
                [
                    'type' => 'select',
                    'label' => 'Lists',
                    'name' => 'lists',
                    "options"=>[
                        [
                            "label"=>"card",
                            "value"=>"1"
                        ],
                        [
                            "label"=>"Todo",
                            "value"=>"2"
                        ],
                        [
                            "label"=>"Doing",
                            "value"=>"3"
                        ],
                        [
                            "label"=>"Done",
                            "value"=>"4"
                        ]
                    ]
                ],
                [
                    'type' => 'text',
                    'label' => 'Card name',
                    'name' => 'card_name'
                ],
                [
                    'type' => 'select',
                    'label' => 'Due Date',
                    'name' => 'due_date',
                    "options"=>[
                        [
                            "label"=>"One Week",
                            "value"=>"1"
                        ],
                        [
                            "label"=>"Two Weeks",
                            "value"=>"2"
                        ],
                        [
                            "label"=>"Three Weeks",
                            "value"=>"3"
                        ],
                        [
                            "label"=>"Four",
                            "value"=>"4"
                        ]
                    ]
                ],
                [
                    'type' => 'textarea',
                    'label' => 'Description',
                    'name' => 'desc'
                ]
            ]
        ];
        
        // get trigger ID from incoming slash request
        $trigger = $request->post('trigger_id');
        
        // define POST query parameters
        $query = [
            'token' => 'xoxp-529317125619-528818928305-559627296563-e857ee51b1b123367d36f34a61cf9da3',
            'dialog' => json_encode($dialog),
            'trigger_id' => $trigger
        ];
        
        // define the curl request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://slack.com/api/dialog.open');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        // set the POST query parameters
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($query));
        
        // execute curl request
        $response = curl_exec($ch);
        
        // close
        curl_close($ch);
        
        var_export($response);

    }

    public function actionTest()
    {
        $request = Yii::$app->request;

        Yii::warning("=============test");

        return [];
    }
    public function actionNew()
    {
        echo"chào bạn ánh";
    }
}