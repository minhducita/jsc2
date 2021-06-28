<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace api\modules\v1\controllers;

use api\common\controllers\RestApiController;
use api\modules\v1\models\BoardLabels;
use api\modules\v1\models\Card;
use Yii;
use yii\base\Model;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;

class LabelsController extends RestApiController
{


    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $params = request()->getBodyParams();
		
		$modelCard = Card::findOne($params['idcard']);
		
        $model->load($params, '');
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        };
		
		Card::deleteCache($modelCard->id, $modelCard->idBoard);
        return $model;
    }

    public function actionDeleteLabel($id, $idCard = null)
    {
        $model = $this->findModel($id);
		$modelCard = Card::findOne($idCard);
        if ($model->delete() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        };
		
		Card::deleteCache($modelCard->id, $modelCard->idBoard);
		
        $response = Yii::$app->getResponse();
        $response->setStatusCode(204);
    }

    public function findModel($id)
    {
        $model = BoardLabels::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException;
        }
        return $model;
    }

}