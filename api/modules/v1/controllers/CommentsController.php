<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace api\modules\v1\controllers;

use api\modules\v1\models\Comments;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use api\common\controllers\RestApiController;
use common\helpers\StringHelper;

class CommentsController extends RestApiController
{

    public function actionCreate()
    {
        $model = new Comments([
            'scenario' => Model::SCENARIO_DEFAULT
        ]);
        $model->load(request()->getBodyParams(), '');
        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
        return $model;
    }

	public function actionUpdate($id){
        $model = $this->findModel($id);
        $model->load(request()->getBodyParams(), '');
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
        return $model;
    }

    public function findModel($id)
    {
        $model = Comments::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException;
        }
        return $model;
    }

}