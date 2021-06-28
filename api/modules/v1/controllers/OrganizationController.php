<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace api\modules\v1\controllers;

use api\modules\v1\models\OrganizationMemberships;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\Url;
use yii\web\ServerErrorHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;


use api\common\controllers\RestApiController;
use api\modules\v1\controllers\NotificationsAccessController;

use api\common\models\Member;
/*
 ** library upload image 
*/
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use yii\web\UploadedFile;
use api\common\models\UploadForm;

use yii\helpers\Json;

use api\modules\v1\models\Organization;
use api\modules\v1\models\Notifications;
use api\modules\v1\models\Board;
use api\modules\v1\models\OrganizationMember;
use common\helpers\StringHelper;

class OrganizationController extends RestApiController
{
//    public function behaviors()
//    {
//        $behaviors = parent::behaviors();
//        $behaviors['verbFilter'] = \yii\helpers\ArrayHelper::merge($behaviors['verbFilter'], [
//            'actions' => [
//                'abc' => ['OPTIONS']
//            ],
//        ]);
//        return $behaviors;
//    }
	
    public function actionIndex()
    {
        $idMember = userIdentity()->getId();
        return new ActiveDataProvider([
            'query' => Organization::find()->where([
                'id' => OrganizationMember::find()->select(['idOrganization'])->where(['idMember' => $idMember])
            ]),
        ]);
    }
	
    public function actionCreate()
    {
        $model = new Organization([
            'scenario' => Model::SCENARIO_DEFAULT
        ]);
        $model->load(request()->getBodyParams(), '');
        $transaction = db()->beginTransaction();
        $model->idMember = userIdentity()->getId();
        $model->name = StringHelper::StrRewrite(strtolower($model->displayName));
        $model->permission = Organization::ORGANIZATION_PRIVATE;
        if ($model->save()) {
            $oMember = new OrganizationMember([
                'scenario' => Model::SCENARIO_DEFAULT
            ]);
            $oMember->idOrganization = $model->id;
            $oMember->idMember = userIdentity()->getId();
            $oMember->save();
            $oMemberShips = new OrganizationMemberships([
                'scenario' => Model::SCENARIO_DEFAULT
            ]);
            $oMemberShips->idOrganization = $model->id;
            $oMemberShips->idMember = userIdentity()->getId();
            $oMemberShips->memberType = OrganizationMemberships::ADMIN_TYPE;
            $oMemberShips->save();

            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $attributes = $model->attributes;
            unset($attributes['desc'], $attributes['website'], $attributes['idMember']);
            $transaction->commit();
			Organization::deleteCache($model->id);
            return $attributes;
        } elseif (!$model->hasErrors()) {
            $transaction->rollBack();
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }
        return $model;
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        if ($model->permission == Organization::ORGANIZATION_PRIVATE) {
            if (!app()->user->getIsGuest() && !$model->isMemberInOrganization(userIdentity()->getId(), $model->id)) {
                throw new ForbiddenHttpException(Yii::t('yii', 'Teams is private. You can\'t joined'));
            }
        } elseif ($model->permission == Organization::ORGANIZATION_PUBLIC) {

        }
        return $model;
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $model->load(request()->getBodyParams(), '');
		
        $model->name = str_replace(" ","",preg_replace('/\s\s+/',' ', trim($model->name)));
        $idMember = userIdentity()->getId();
        $memberShips = OrganizationMemberships::findOne([
            'idOrganization' => $id,
            'idMember' => $idMember
        ]);
        if ($memberShips && $memberShips->memberType == OrganizationMemberships::ADMIN_TYPE) {
            if ($model->save() === false && !$model->hasErrors()) {
                throw new ForbiddenHttpException(Yii::t('yii', 'Failed to update the object for unknown reason'));
            }
        } else {
            throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
        }
		
		Organization::deleteCache($id);
        return $model;
    }
    public function actionDelete($id)
    {
        $model = $this->findModel($id);
		if(!empty($model)) {
			$returnData = $model;
			$idMember = userIdentity()->getId();
			$idOrganization = $model->id;
			if ($model->idMember == $idMember) {
				/* Notification */
				$idMembers = OrganizationMember::find()->select("idMember")->where(['idOrganization'=>$returnData->id])->column();
				$params = array(
					'table'=>'organization',
					'idTable'=>$id,
					'idSender'=>$idMembers,
					'idReceive'=> userIdentity()->getId(),
					'datapost'=>request()->getBodyParams(),
					'dataParams'=> array('action'=>'deleted'),
					'type'      => 25
				);
				$this->_createNotification($params);
				/* end notification*/
				
				$this->_deletelogo($model);
				Organization::deleteCache($model->id);
				if ($model->delete() === false) {
				   throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
				}
				// update from team to team My board
				Board::updateAll(['idOrganization'=>0],'idOrganization = '.$returnData->id);				
				// end update from  team to team My Board
				return $returnData;
			   // Yii::$app->getResponse()->setStatusCode(204);
			} 			
		}
		throw new ForbiddenHttpException(Yii::t('yii', 'You are not allowed to perform this action.'));
		
    }

    public function actionMakeMembers($idOrganization, $idMember)
    {
        $model = OrganizationMemberships::findOne([
            'idOrganization' => $idOrganization,
            'idMember' => $idMember
        ]);
        if ($model === null) {
            $model = new OrganizationMember([
                'scenario' => Model::SCENARIO_DEFAULT
            ]);

            $model->idOrganization = $idOrganization;
            $model->idMember = $idMember;
            if ($model->save()) {
                $memberShipsModel = new OrganizationMemberships([
                    'scenario' => Model::SCENARIO_DEFAULT
                ]);
                $memberShipsModel->idOrganization = $idOrganization;
                $memberShipsModel->idMember = $idMember;
                $memberShipsModel->memberType = request()->getBodyParam('type') ? request()->getBodyParam('type') : 'normal';
                if ($memberShipsModel->save() === false) {
                    throw new ServerErrorHttpException('Failed add the member to organization.');
                }
				/* save Notification */
				$idMembers = OrganizationMemberships::find()->select('idMember')->where(['idOrganization'=>$idOrganization])->column();
				$params = array(
                    "table"=>"organization",
                    "datapost"=> request()->getBodyParams(),
					'dataParams'=>array('action'=>'added'),
                    'idTable'=>$idOrganization,
                    'idSender'=>$idMembers,
					'idEffectMember'=>$idMember,
                    'type'=>1,
                    'idReceive'=>userIdentity()->getId()
                );
				$notification = new NotificationsAccessController();
				$notification ->createNotifyConformTable($params);
				/* end save notifiction*/
            } else if (!$model->hasErrors()) {
                throw new ServerErrorHttpException('Failed add the member to organization.');
            }
        } else {
            if (request()->getBodyParam('type')) {
                $model->memberType = request()->getBodyParam('type');
				/* save Notification */
				$idMembers = OrganizationMemberships::find()->select('idMember')->where(['idOrganization'=>$idOrganization])->column();
				$params = array(
					"table"=>"organization",
                    "datapost"=>request()->getBodyParams(),
					'dataParams'=>array('action'=>'made'),
                    'idTable'=>$idOrganization,
                    'idSender'=>$idMembers,
                    'type'=>3,
					'idEffectMember'=>$idMember,
                    'idReceive'=>userIdentity()->getId()
                );
				$this->_createNotification($params);
				/* end save notifiction*/
            }
            if ($model->save() === false) {
                throw new ServerErrorHttpException('Failed change role the member organization.');
            }
        }
		Organization::deleteCache($idOrganization);
        return Member::findOne($idMember);
    }

    public function actionDeleteMembers($idOrganization, $idMember)
    {
        $model = OrganizationMember::findOne([
            'idOrganization' => $idOrganization,
            'idMember' => $idMember
        ]);

        $memberShipsModel = OrganizationMemberships::findOne([
            'idOrganization' => $idOrganization,
            'idMember' => $idMember
        ]);
		
		Organization::deleteCache($idOrganization); // delete cache
        
		if ($model->delete() === false || $memberShipsModel->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
		$idMembers = OrganizationMemberships::find()->select('idMember')->where(['idOrganization'=>$idOrganization])->column();
		$idMembers[] = $idMember;		
		$params = array(
            "table"=>"organization",
            "datapost"=>request()->getBodyParams(),
			'dataParams'=>array('action'=>'removed'),
            'idTable'=>$idOrganization,
            'idSender'=>$idMembers,
			'idEffectMember'=>$idMember,
            'type'=>2,
            'idReceive'=>userIdentity()->getId()
        );
		$this->_createNotification($params);
        Yii::$app->getResponse()->setStatusCode(204);
		
		
    }
    public function findModel($id)
    {
        $model = is_numeric($id) ? Organization::findCacheOne((int)$id) : Organization::findOne(['name' => $id]);
        if ($model === null) {
            throw new NotFoundHttpException;
        }
        return $model;
    }

	private function _createNotification($params) {
		$notification = new NotificationsAccessController();
		$notification ->createNotifyConformTable($params);
	}
	
	public function actionAttachments($id) {
		$team = Organization::find()
		->with("memberTypeShip")
		->where(['id'=>$id])
		-> one();	
		if($team &&  $team->memberTypeShip->memberType == 'admin') {
			$uploadForm = new UploadForm();
			$uploadForm->file = UploadedFile::getInstanceByName('file');
			$folderteam   = md5($team->name);
			$nameImg = $uploadForm->file->baseName."_".time();
			if ($uploadForm->upload("teams/".$folderteam,$nameImg)) {
				$path = $uploadForm->uploadFolder . '/teams/'  .$folderteam . '/' .$nameImg . '.' . $uploadForm->file->extension;			
				$imagine = new Imagine();
			
				try {
				
					/* save image thumb */
					$path85x85 = $uploadForm->uploadFolder . '/teams/'  .$folderteam . '/' .$nameImg . '-85x85.' . $uploadForm->file->extension;
					
					$imagine->open(Yii::getAlias($path))
						->resize(new Box(85, 85))
						->save(Yii::getAlias($path85x85), ['quanlity' => 70]);
	
					/* update date logo organization*/
					$this->_deletelogo($team);
					
					$team->logo = str_replace('@frontend/web', '', $path);

					$team->logo_preview = Json::encode([
						[
							'width' 	=> 85,
							'height' 	=> 85,
							'typefile'	=> $uploadForm->file->extension,
							'bytes' 	=> filesize(Yii::getAlias($path85x85)),
							'url' 		=> str_replace('@frontend/web', '', $path85x85)
						]
					]);
					$team->save();
					Organization::deleteCache($team->id);
					return $team;
					
				} catch (\Exception $e) {
					//$transaction->rollBack();
					throw new ServerErrorHttpException('Failed to upload file to server.');
				}
			} else {
				throw new ServerErrorHttpException('Failed to upload file to server.');
			}
		} else 
			throw new ServerErrorHttpException('Failed to upload file to server. Permission Asset');
		
	}
	
	public function actionDeleteLogo($id) {
		$team = Organization::find()
		->with("memberTypeShip")
		->where(['id'=>$id])
		-> one();
		if($team) {
			$this->_deletelogo($team);
			$team->logo = "";
			$team->logo_preview = "";
			$team->save();
			Organization::deleteCache($team->id);
			return $team;
		} else {
			throw new ServerErrorHttpException('Failed to delete file to server. Permission Asset');
		}
	}
	
	private function _deletelogo ($organization) {
		if(!empty($organization->logo)) {
			$uploadForm = new UploadForm();
			$previews 		= json_decode($organization->logo_preview);
			$urllogo 		= Yii::getAlias($uploadForm->uploadFolder.str_replace("assets/img","",$organization->logo));
			@unlink($urllogo);
			foreach($previews as $preview) {
				$urllogothumb	= Yii::getAlias($uploadForm->uploadFolder.str_replace("assets/img","",$preview->url));
				@unlink($urllogothumb);
			}
		}
	}
}