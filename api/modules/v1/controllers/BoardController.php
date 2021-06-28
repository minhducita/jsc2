<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace api\modules\v1\controllers;
use api\common\controllers\RestApiController;
use api\common\models\Member;
use api\modules\v1\models\Activity;
use api\modules\v1\models\Board;
use api\modules\v1\models\BoardLabels;
use api\modules\v1\models\BoardMember;
use api\modules\v1\models\BoardMemberships;
use api\modules\v1\models\BoardUser;
use api\modules\v1\models\BoardStar;
use api\common\models\User;
use api\modules\v1\models\Options;
use api\modules\v1\controllers\NotificationsAccessController;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\helpers\StringHelper;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;


class BoardController extends RestApiController
{

    public function actionIndex()
    {
        $idMember = userIdentity()->getId();
    }
	
    public function actionCreate()
    {
        $model = new Board ([
            'scenario' => Model::SCENARIO_DEFAULT
        ]);
		
        $model->load(request()->getBodyParams(), '');
        $transaction = db()->beginTransaction();
        $model->idMember = userIdentity()->getId();
        $model->name = StringHelper::StrRewrite(strtolower($model->displayName));
        $model->prefs = Json::encode(Board::$boardPrefsDefault);
        $model->labelNames = Json::encode([
            'black' => '',
            'blue'  => '',
            'green' => '',
            'lime'  => '',
            'orange' => '',
            'pink' => '',
            'purple' => '',
            'red' => '',
            'sky' => '',
            'yellow' => ''
        ]);
        if ($model->save()) {	
            /**
             * save model BoardLabels
             */
            db()->createCommand()->batchInsert(BoardLabels::tableName(), [
                'name',
                'color',
                'used',
                'idBoard'
            ], array_map(function($color) use($model) {
                return [
                    'name' => '',
                    'color' => $color,
                    'used' => 0,
                    'idBoard' => $model->id
                ];
            }, ['green', 'yellow', 'orange', 'purple', 'red', 'blue']))->execute();
            /*
             * save model BoardMember
             */
            $boardMemberModel = new BoardMember();
            $boardMemberModel->idBoard = $model->id;
            $boardMemberModel->idMember = userIdentity()->getId();
            $boardMemberModel->save();
            /**
             * save model MemberShips
             */
            $boardMemberShipsModel = new BoardMemberships();
            $boardMemberShipsModel->idMember = userIdentity()->getId();
            $boardMemberShipsModel->idBoard = $model->id;
            $boardMemberShipsModel->memberType = BoardMemberships::ADMIN_ROLE;
            $boardMemberShipsModel->orgMemberType = BoardMemberships::ORG_ADMIN_ROLE;
            $boardMemberShipsModel->save();

            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $transaction->commit();
        } elseif (!$model->hasErrors()) {
            $transaction->rollBack();
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
		Board::deleteCache($model->id);
		$model = $this->findModel($model->id);
        return $model;
    }
	
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $params = request()->getBodyParams();
        $model->load($params, '');
        if (isset($params['type']) && $params['type'] === 'prefs/background') {
            $prefs = Json::decode($model->prefs);
			$defaultPrefs = Board::$boardPrefsDefault;
			$prefs  = array_merge($defaultPrefs,$prefs);
            $model->prefs = Json::encode(array_merge($prefs, array_intersect_key($params, $prefs)));
		}
		
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
		
		Board::deleteCache($model->id);
		
		/* notification */
		$modelboarmember  = BoardMember::find($id)->select('idMember')->where(['idBoard'=>$model->id])->column();
		$action = (isset($params['closed']))?($params['closed'] == 1)?'Closed':'Opened':'Edited';
		$params = array(
			'table'		=> 'board',
			'datapost'	=> request()->getBodyParams(),
			'idTable'	=> $id,
			'dataParams'=> array('action'=>$action),
			'type'      => isset($params['closed'])?7:0,
			'idSender'	=> $modelboarmember,
			'idReceive' => userIdentity()->getId(),
		);	
		$this->_createNotification($params);
        return  $model ;
    }
	
    public function actionView($id)
    {
        return Board::findCacheOne($id);
    }

    public function findModel($id)
    {
        $id = is_numeric($id) ? ['id' => (int) $id] : ['name' => $id];
        if (($closed = request()->get('closed')) && in_array($closed, [Board::BOARD_ACTIVE, Board::BOARD_CLOSED])) {
            $id['closed'] = $closed;
        }
        $model = Board::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException;
        }
        return $model;
    }

	public function actionStarBoard($id)
    {
        $model = BoardStar::findOne(['idBoard' => $id, 'idMember' => userIdentity()->getId()]);
        if ($model) {
            $model->isShow = 1;
            if ($model->save()) {
                $response = Yii::$app->getResponse();
                $response->setStatusCode(201);
            } elseif (!$model->hasErrors()) {
                throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
            }
        } else {
            $model = new BoardStar();
            $model->idMember = userIdentity()->getId();
            $model->isShow = 1;
            $model->load(request()->getBodyParams(), '');
            if ($model->save()) {
                $response = Yii::$app->getResponse();
                $response->setStatusCode(201);
            } elseif (!$model->hasErrors()) {
                throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
            }
        }
		Board::deleteCache($id);
        unset($model->idMember, $model->isShow);
		return $model;
	}
	
    public function actionUnStarboard($id)
    {
        $model = BoardStar::findOne(['idBoard' => $id, 'idMember' => userIdentity()->getId()]);
        if ($model === null) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        } else {
            $model->isShow = 0;
            if (!$model->save() && !$model->hasErrors()) {
                throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
            }
        }
		Board::deleteCache($id);
        Yii::$app->getResponse()->setStatusCode(204);
    }
    public function actionMakeMembers($id, $idMember)
    {
        $model = BoardMemberships::findOne([
            'idBoard' => $id,
            'idMember' => $idMember
        ]);

        if ($model === null) {
            $model = new BoardMember([
                'scenario' => Model::SCENARIO_DEFAULT
            ]);

            $model->idBoard = $id;
            $model->idMember = $idMember;
            if ($model->save()) {
                $memberShipsModel = new BoardMemberships([
                    'scenario' => Model::SCENARIO_DEFAULT
                ]);
                $memberShipsModel->idBoard = $id;
                $memberShipsModel->idMember = $idMember;
                $memberShipsModel->memberType = request()->getBodyParam('type') ? request()->getBodyParam('type') : 'normal';
                if ($memberShipsModel->save() === false) {
                    throw new ServerErrorHttpException('Failed add the member to board.');
                }
				$idMembers = BoardMemberships::find()->select('idMember')->where(["idBoard"=>$id])->column();
				$params = array(
					'table'		=> 'board',
					'datapost'	=> request()->getBodyParams(),
					'idTable'	=> $id,
					'dataParams'=> array('action'=>'added'),
					'type'      => 4,
					'idSender'	=>$idMembers,
					'idEffectMember'=>$idMember,
					'idReceive' => userIdentity()->getId(),
				);
				$this->_createNotification($params);
            } else if (!$model->hasErrors()) {
                throw new ServerErrorHttpException('Failed add the member to board.');
            }
        } else {
            if (request()->getBodyParam('type')) {
				$idMembers = BoardMemberships::find()->select('idMember')->where(["idBoard"=>$id])->column();
				$params = array(
					'table'		=> 'board',
					'datapost'	=> request()->getBodyParams(),
					'idTable'	=> $id,
					'dataParams'=> array('action'=>'made'),
					'type'      => 6,
					'idSender'	=>$idMembers,
					'idEffectMember'=>$idMember,
					'idReceive' => userIdentity()->getId(),
				);
				$this->_createNotification($params);
                $model->memberType = request()->getBodyParam('type');
            }
            if ($model->save() === false) {
                throw new ServerErrorHttpException('Failed change role the member organization.');
            }
        }
		
		Board::deleteCache($id);
		Member::deleteCache($idMember);

        return Member::findCacheOne($idMember);//Member::findOne($idMember);
    }

    public function actionDeleteMembers($id, $idMember)
    {
        $model = BoardMember::findOne([
            'idBoard' => $id,
            'idMember' => $idMember
        ]);
		
        $memberShipsModel = BoardMemberships::findOne([
            'idBoard' => $id,
            'idMember' => $idMember
        ]);
		
		Board::deleteCache($id);
		Member::deleteCache($idMember);
		
        if ($model->delete() === false || $memberShipsModel->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
		
		/* delete cache*/
		
		
		$idMembers = BoardMemberships::find()->select('idMember')->where(["idBoard"=>$id])->column();
		$idMembers[] = $idMember;
		$params = array(
			'table'				=> 'board',
			'datapost'			=> request()->getBodyParams(),
			'idTable'			=> $id,
			'dataParams'		=> array('action'=>'removed'),
			'type'      		=> 5,
			'idSender'			=> $idMembers,
			'idEffectMember' 	=> $idMember,
			'idReceive' => userIdentity()->getId(),
		);
		$this->_createNotification($params);
        Yii::$app->getResponse()->setStatusCode(204);
    }

	private function _createNotification($params) {
		$notification = new NotificationsAccessController();
		$notification ->createNotifyConformTable($params);
	}
}