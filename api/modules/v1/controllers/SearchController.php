<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace api\modules\v1\controllers;

use api\common\controllers\RestApiController;
use api\common\models\Member;

use yii\data\ActiveDataProvider;

use api\common\models\Search;

class SearchController extends RestApiController
{
    public function actionView($id)
    {
		
        $q     = request()->get('query');
        $type  = request()->get('type');
        switch($type) {
            case 'organizations':
                $query = Member::find()->select(['id', 'username', 'displayName', 'initialsName', 'avatarHash', 'bio', 'url', 'status', 'typeimg']);
                $dataProvider  = new ActiveDataProvider([
                    'query' => $query
                ]);
                $query->andFilterWhere(['like', 'displayName', $q]);
                return $dataProvider;
            break;
        }
    }
	
	public function actionIndex() {
		$dataSearch = [];
		$search = new Search();
		$search->nameSearch 			= trim(request()->get('search'));
		$search->idMember 				= userIdentity()->getId();
		if(is_numeric($search->nameSearch)) {
			$dataSearch["cards"]  			= $search->getCardsId();
		} else {
			$dataSearch["cards"]  			= $search->getCards();
		}
		$dataSearch["organizations"] 	= $search->getOrganizations();
		$dataSearch["boards"]  			= $search->getBoards();
		return $dataSearch;		
	}	
}