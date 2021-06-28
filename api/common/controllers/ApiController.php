<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace api\common\controllers;

use yii\filters\auth\QueryParamAuth;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\auth\HttpBearerAuth;

class ApiController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats'] = [
                'application/json' => Response::FORMAT_JSON,
        ];
        $behaviors['verbFilter']['actions'] = [
                'index'  => ['get'],
                'view'   => ['get'],
                'create' => ['get', 'post'],
                'update' => ['get', 'put', 'post'],
                'delete' => ['post', 'delete'],
        ];
        $behaviors['authenticator'] = [
            'class' => QueryParamAuth::className(),
        ];
        return $behaviors;
    }
}