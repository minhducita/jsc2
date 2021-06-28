<?php

/**
 * Auth: Minhanh
 * Email: minhanh.itqn@gmail.com
 */

namespace api\modules\v1\controllers;

use yii\rest\Controller;
use api\common\models\Member;
use api\modules\v1\controllers\NotificationsAccessController;
use api\common\models\UploadFile;
use api\modules\v1\models\Attachments;
use api\modules\v1\models\BoardLabels;
use api\modules\v1\models\Card;
use api\modules\v1\models\Report;
use api\modules\v1\models\Members;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use yii\base\ErrorException;
use api\common\controllers\RestApiController;
use common\helpers\StringHelper;
use common\helpers\SlackMessengerHelper;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use api\common\models\UploadForm;

class ReportController extends Controller
{
    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * Description :add report
     */
    function actionCreateReport()
    {
        $status = false;
        $message = '';
        $request = Yii::$app->request;
        $data = Yii::$app->request->post();
        if (($request->isPost) && !empty($request->post())) {
            $message = 'message not empty and is method post';
            $rowReport = Report::find()
                ->where([
                    'idMember' => $data['idMember'],
                    'created_at' => $data['created_at'],
                    'idCard' => $data['idCard']
                ])->one();
            $id = $rowReport['id'];
            $nameCard = $rowReport['idCard'];
            $hourReportToday = $this->actionReportToday($data['idMember'], $data['created_at']);
            $hours = $hourReportToday + $data['hours'];
            if ($rowReport == null) {
                if ($hours <= 23) {
                    try {
                        $model = new Report();
                        $model->hours = $data['hours'];
                        $model->idMember = $data['idMember'];
                        $model->idCard = $data['idCard'];
                        $model->created_at = $data['created_at'];
                        $model->idBoard = $data['idBoard'];
                        $model->save();
                        $status = true;
                        $message = "You have reported success";
                    } catch (ErrorException $e) {
                        $message = 'You report not yet successful .';
                    }
                } else {
                    $message = 'Must be smaller than 23 hours' . '. The total number of hours reported today is = ' . $hourReportToday . 'h';
                }
            } else {
                $hourReportToday = $this->actionReported($data['idMember'], $data['created_at'], $data['idCard']);
                $hours = $hourReportToday + $data['hours'];
                if ($hours <= 23) {
                    try {
                        $model = Report::find()->where(['id' => $id])->one();
                        $model->hours = $data['hours'];
                        $model->save();
                        $status = true;
                        $message = 'You have updated the report successfully card ' . $nameCard;
                    } catch (ErrorException $e) {
                        $message = 'You have not updated the report successfully.' . $nameCard;
                    }
                } else {
                    $message = 'Must be smaller than 23 hours' . '. The total number of hours reported today is = ' . $hourReportToday . 'h';
                }
            }

        } else {
            $message = 'method get no insert database';
        }
        return ['status' => $status, 'message' => $message];
    }

    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * Description :show sum hours in a card
     */
    public function actionShowSumHours()
    {
        $result = [];
        $request = Yii::$app->request;
        $data = Yii::$app->request->post();
        if (($request->isPost) && !empty($request->post('idCard'))) {
            $idCard = $data['idCard'];
            $onlyCard = Report::getAllmembersnoday($idCard);
            $arrayData = [];
            foreach ($onlyCard as $key => $value) {
                $sumColumn = Report::sumOnlymembernoday($value->idMember, $value->idCard);
                $arrayData[$key] = ['idCard' => $value->idCard, 'idMember' => $value->idMember, 'hours' => $sumColumn];
            }
            $result = $arrayData;
        }
        return $result;
    }

    private function actionReportToday($idMember, $date)
    {
        $hours = 0;
        $reports = Report::find()
            ->where([
                'idMember' => $idMember,
                'created_at' => $date,
            ])->all();
        if (!empty($reports)) {
            foreach ($reports as $key => $report) {
                $hours += $report['hours'];
            }
        }
        return $hours;
    }

    private function actionReported($idMember, $date, $idCard)
    {
        $hours = 0;
        $reports = Report::find()
            ->where([
                'idMember' => $idMember,
                'created_at' => $date
            ])
            ->andWhere(['<>','idCard', $idCard])
            ->all();
        if (!empty($reports)) {
            foreach ($reports as $key => $report) {
                $hours += $report['hours'];
            }
        }
        return $hours;
    }
}
