<?php

namespace api\modules\v1\controllers;

use yii\rest\Controller;
use api\common\models\Member;
use api\modules\v1\controllers\NotificationsAccessController;
use api\common\models\UploadFile;
use api\modules\v1\models\Attachments;
use api\modules\v1\models\BoardLabels;
use api\modules\v1\models\Card;
use api\modules\v1\models\Board;
use api\modules\v1\models\CardMembers;
use api\modules\v1\models\BoardMemberships;
use api\modules\v1\models\Checklist;
use api\modules\v1\models\ChecklistItem;
use api\modules\v1\models\Comments;
use api\modules\v1\models\Lists;
use api\modules\v1\models\Options;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;
use api\common\controllers\RestApiController;
use common\helpers\StringHelper;
use common\helpers\SlackMessengerHelper;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use api\common\models\UploadForm;

class SlackController extends Controller
{
    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * Description : show dialog in slack
     */
    public function actionDialogSlack()
    {
        $request = Yii::$app->request;
        $dialog = [
            'callback_id' => 'sendMessage',
            'title' => 'Create Card',
            'submit_label' => 'Create',
            'elements' => [
                [
                    'type' => 'text',
                    'label' => 'Card name',
                    'name' => 'card_name'
                ],
                [
                    "label" => "Choose List Card in Board",
                    "name" => "listcard",
                    "type" => "select",
                    "option_groups" => $this->actionBoard(), // call list cards in list boards to show dialog slack
                ]
            ]
        ];
        // get trigger ID from incoming slash request
        $trigger = $request->post('trigger_id');
        // define POST query parameters
        $tokenSlack = "";
        if (isset(Yii::$app->params['token_slack']) && !empty(Yii::$app->params['token_slack'])) {
            $tokenSlack = Yii::$app->params['token_slack']; // set in file api/config/params.php
        } else {
            $tokenSlack = 'xoxp-529317125619-605703256161-664341245569-304b41118013f4c2882795afee0d3c23';
        }
        $query = [
            'token' => $tokenSlack,
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

    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * Desctiption : resolve request creat card form slack and response array[]
     */
    public function actionCreateCardSlack()
    {
        $data = json_decode($_POST['payload']);
        $nameUserSlack = $data->user->name; // username in slack create card
        $dataFrom = $data->submission;
        $nameCard = $dataFrom->card_name;
        $boardAndCard = explode(",", $dataFrom->listcard);
        $idBoard = (int)$boardAndCard[0];
        $idListCard = (int)$boardAndCard[1];
        $board = Board::find()->where(['id' => $idBoard])->one();
        $listCard = Lists::find()->where(['id' => $idListCard])->one();
        $nameBoard = $board['displayName'];
        $nameListCard = $listCard['name'];
        $status = "success";
        $this->actionCreate($idBoard, $idListCard, $nameCard, $nameUserSlack);
        $this->actionSendMessage($nameCard, $nameBoard, $nameListCard, $nameUserSlack, $status);
        return [];
    }

    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * create card from slack
     * url :api.dev.com/v1/slack/create/
     */
    public function actionCreate($idBoard, $idListCard, $nameCard, $nameUserSlack)
    {
        $idMemberSlack = 15;// default id Mr Masaki
        $data = [
            'closed' => false,
            'dateLastActivity' => time(),
            'idBoard' => $idBoard,
            'idList' => $idListCard,
            'idLabels' => '[]',
            'displayName' => $nameCard,
            'pos' => 99999,
        ];
        $model = new Card([
            'scenario' => Model::SCENARIO_DEFAULT
        ]);
        $model->load($data, '');
        $model->name = StringHelper::StrRewrite(strtolower($model->displayName));
        $model->closed = Card::CARD_ACTIVE;
        $model->idMember = $idMemberSlack;
        if (isset($model->important)) {
            $model->important = 1;
        };
        if ($model->urgent) {
            $model->urgent = 1;
        };
        /**
         * set Defaults bages
         */

        $model->badges = Json::encode([
            'attachments' => 0,
            'checkItems' => 0,
            'checkItemsChecked' => 0,
            'comments' => 0,
            'description' => false,
            'due' => (!empty($model->due)) ? $model->due : null,
            'subscribed' => false,
            'viewingMemberVoted' => 0,
            'startDate' => (!empty($model->startDate)) ? $model->startDate : null,
            'votes' => 0
        ]);
        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
        /*notification*/
        $idCard = Card::find()->select('idBoard')->where(['id' => $model->id])->one();
        $idMembers = BoardMemberships::find()->select('idMember')->where(["idBoard" => $idCard->idBoard])->column();
        if (!empty($idMembers)) {
            $params = array(
                'table' => "card",
                'type' => 12,// create card
                'dataParams' => 'created',
                'idTable' => $model->id,
                'datapost' => $data,
                'idSender' => $idMembers,
                'idReceive' => $idMemberSlack,
            );
            $this->createNotification($params);
            /*slack send notification */
            if (!empty($slackAction) && !empty($model)) {
                $slackAction = "createCard";
                $slackDataParams = $params;
                $this->sendSlack($slackAction, $model, $slackDataParams);
            }
        }
        /* send slack notification*/
        Card::deleteCacheslack($model->id, $model->idBoard, $idMemberSlack);
        return $model;
    }

    private function createNotification($params)
    {
        $notification = new NotificationsAccessController();
        $notification->createNotifyConformTable($params);
    }

    private function setLastUpdated($idCard)
    {
        $model = Card::findOne($idCard);
        $model->lastUpdated = time();
        $model->save();
        Card::deleteCache($model->id, $model->idBoard);
        return $model;
    }

    private function sendSlack($slackAction, $cardFind, $slackDataParams = array())
    {
        $slackBoard = Board::findOne($cardFind->idBoard);
        $slackList = Lists::findOne($cardFind->idList);
        $slackDataParams['UserdisplayName'] = userIdentity()->displayName;
        $slackDataParams['cardName'] = $cardFind->displayName;
        $slackDataParams['idCard'] = $cardFind->id;
        $slackDataParams['listName'] = $slackList->name;
        SlackMessengerHelper::sendSlackMessenger($slackAction, $slackBoard, $slackDataParams);
    }

    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * Description : get in all cards in boards to show dialog slack
     */
    private function actionBoard()
    {
        $idBoards = [73, 70, 47, 39]; // list board want show in slack
        $boards = Board::find()->where(['id' => $idBoards])->all();
        $data = [];
        foreach ($boards as $key => $board) {
            $data[$key] = [
                "label" => $board['displayName'],
                "options" => $this->getListCard($board), // get listcards in a board
            ];
        }
        return $data;
    }

    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * Description : get listcard in a board to show dialog slack
     */
    private function getListCard($board)
    {
        $cards = $board->getLists();
        $data = [];
        foreach ($cards as $key => $card) {
            $data[$key] = [
                'label' => $card['name'],
                'value' => $board['id'] . "," . $card['id'],
            ];
        }
        return $data;
    }

    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * Description :send message to channel slack
     */
    private function actionSendMessage($nameCard, $nameBoard, $nameListCard, $nameUserSlack, $status)
    {
        Yii::$app->slack->send('Create Card From Slack', ':thumbs_up:', [
            [
                "pretext" => "*Information card created .*",
                "text" => "1. *Username* :" . $nameUserSlack . "\n 2. *Namecard * : " .
                    $nameCard . "\n 3. *Nameboard* : " . $nameBoard . "\n 4. *ListCard* : " .
                    $nameListCard . "\n 5. *Status* : " . $status,
                "color" => "#7CD197"
            ],
        ]);
    }
}
