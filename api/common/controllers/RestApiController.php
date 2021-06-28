<?php
/**
 * Auth: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
namespace api\common\controllers;

use api\common\models\Memcache;

use Yii;
use yii\rest\Controller;
use yii\web\MethodNotAllowedHttpException;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\helpers\Url;


class RestApiController extends Controller
{

    public $serializer = 'yii\com\rest\SerializerData';
	
	private $_urlCache = "";
	
    public $format = [
      'json' => [
          'application/json' => \yii\web\Response::FORMAT_JSON,
      ],
      'xml' => [
          'application/xml' => \yii\web\Response::FORMAT_XML,
      ]
    ];
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats'] = isset($this->format[request()->get('format')]) ? $this->format[request()->get('format')] : $this->format['json'];
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::className(),
            'authMethods' => [
                HttpBasicAuth::className(),
                HttpBearerAuth::className(),
                QueryParamAuth::className(),
            ],
        ];		
        return $behaviors;
    }
	
    public function beforeAction($action)
    {
		$pageCache = new Memcache;
		
		$request = Yii::$app->request;
		
		$this->_urlCache = Url::home(true).Url::current();
		$paramCache = array(
			'accessToken' => $request->get('access-token'),
			'actionName'  => $action->id,
			'controllerName' => $action->controller->id,
			'url' => $this->_urlCache
		);
		$result = $pageCache->findCachePage($paramCache);
		
		if(!empty($result)) {
			echo $result ;exit;
		}
				
		
        if (request()->getMethod() === 'OPTIONS') {
            \Yii::$app->getResponse()->setStatusCode(405);
        }
        return parent::beforeAction($action);
    }
	
	public function afterAction($action, $result)
    {
		$request = Yii::$app->request;
		$result = parent::afterAction($action, $result);
		
		$pageCache = new Memcache;
		$paramCache = array(
			'accessToken' => $request->get('access-token'),
			'actionName'  => $action->id,
			'controllerName' => $action->controller->id,
			'url' => $this->_urlCache
		);
		$pageCache->setCachePage($paramCache, $result);
		return $result;
    }
}