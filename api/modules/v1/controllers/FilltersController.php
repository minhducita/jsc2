<?php
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
use api\common\controllers\RestApiController;
use common\helpers\StringHelper;


// Khánh

            class FilltersController extends Controller
            {

                function actionSendsms()
                {
                    Yii::$app->slack->message('Tôi đã send thành công')->send();
                    Yii::$app->slack->send('Hello', ':thumbs_up:', [
                        [
                            // attachment object
                            'text' => 'text of attachment',
                            'pretext' => 'pretext here',
                        ],
                    ]);
                }
                function actionNew()
                {
                    // Yii::error('anh123456');
                    Yii::info('anh123456');
                    
                    $data = [
                        'closed' => false,
                        'dateLastActivity' => 1554876524395,
                        'idBoard' => 39,
                        'idList' => 93,
                        'idLabels' => '[]',
                        'displayName' => 'NameCard + '.rand(),
                        'pos' => 37,
                    ];
                    $model = new Card([
                        'scenario' => Model::SCENARIO_DEFAULT
                    ]);
                    $model->load($data, '');
                    $model->name = StringHelper::StrRewrite(strtolower($model->displayName));
                    $model->closed = Card::CARD_ACTIVE;
                    $model->idMember = 15;
                    if($model->important){
                        $model->important = 1;
                    };
                    if($model->urgent){
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
                        'due' => (!empty($model->due))? $model->due: null,
                        'subscribed' => false,
                        'viewingMemberVoted' => 0,
                        'startDate'=> (!empty($model->startDate))? $model->startDate: null,
                        'votes' => 0
                    ]);
                    if ($model->save()) 
                    {
                        $response = Yii::$app->getResponse();
                        $response->setStatusCode(201);
                    } elseif (!$model->hasErrors())
                    {
                        throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
                    }
                    /*notification*/
                    $idCard    = Card::find()->select('idBoard')->where(['id'=>$model->id])->one();
                    $idMembers = BoardMemberships::find()->select('idMember')->where(["idBoard"=>$idCard->idBoard])->column();
                    if(!empty($idMembers)) {
                        $params = array(
                            'table'=>"card",
                            'type'=> 12,// create card
                            'dataParams'=> 'created',
                            'idTable'=>$model->id,
                            'datapost'=>$data,
                            'idSender'=>$idMembers,
                            'idReceive'=>15,
                        );
                        $this->_createNotification($params);
                        
                        /*slack send notification */
                        if(!empty($slackAction) && !empty($model)) {
                            $slackAction = "createCard";
                            $slackDataParams = $dataParams;
                            $this->sendSlack($slackAction, $model, $slackDataParams);
                        }
                        
                    }
                    /* send slack notification*/		
                    Card::deleteCache($model->id, $model->idBoard);
                    return $model;

                }

                private function _createNotification($params) {
                    $notification = new NotificationsAccessController();
                    $notification ->createNotifyConformTable($params);
                }

                

                function actionReport()
                {
                    return Report::getTimeReport();
                }

            // create report Auth:minhanh.itqn@gmail.com
                function actionCreatereport()
                {
                    $request = Yii::$app->request;
                //    $params =Yii::$app->request->post();
                //    return  Yii::$app->request->isPost;
                if (($request->isPost)&& !empty($request->post())) 
                {
                    $model = new Report();
                    $arrayidmember =[7,13,15];
                    $arraycard =[1,2,6,145];
                    $arraytime =['2019-05-09','2019-05-10','2019-05-11','2019-05-15','2019-05-12','2019-05-16'];
                    $data =
                    [
                        // * @property string $hours
                        // * @property string $idMember
                        // * @property integer $idCard
                        // * @property string $created_at
                        // * @property string $updated_at

                        'hours'=> rand(1,8),
                        'idMember'=>$arrayidmember[array_rand($arrayidmember, 1)],
                        'idCard'=>$arraycard[array_rand($arraycard, 1)],
                        'created_at'=>$arraytime[array_rand($arraytime, 1)],


                    ];
                    $model->hours =$data['hours'];
                    $model->idMember =$data['idMember'];
                    $model->idCard =$data['idCard'];
                    $model->created_at =$data['created_at'];
                
                    if ($model->save()) {
                        $response = Yii::$app->getResponse();
                        $response->setStatusCode(201);

                        return ['result'=>true];

                    } elseif (!$model->hasErrors()) 
                    {
                        throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
                    }
                
                    
                } 
                else 
                {
                    return "method get no insert database";
                }
                


                }

            /**
             * Auth: Minhanh
             * Email: minhanh.itqn@gmail.com
             */

            // TH1 only card only member
                
            public function actionOnly()
            {
                $idMember = 1;
                $idCard =145;
                $startday ='2019-05-09';
                $endday ='2019-05-11';
                return Report::getOnlymember($idMember ,$idCard ,$startday,$endday);
            }
            public function actionSumonly()
            {
                $idMember = 7;
                $idCard =145;
                $startday ='2019-05-09';
                $endday ='2019-05-16';

                $subcolumn = Report::sumOnlymember($idMember ,$idCard ,$startday,$endday);
                return['idMember'=>$idMember,'idCard'=>$idCard ,'sumhours'=>$subcolumn];
            }
            public function actionSumonlycard()
            {
                //    $idMember = 1;
                $idCard =145;
                $startday ='2019-05-07';
                $endday ='2019-05-16';
                $onlycard = Report::getAllmembers($idCard,$startday,$endday);
                $arraydata =[];
            foreach ($onlycard as $key => $value) 
            {
                $subcolumn = Report::sumOnlymember($value->idMember ,$value->idCard ,$startday,$endday);
                $arraydata[ $key] = ['idCard'=>$value->idCard, 'idMember'=>$value->idMember,'hours'=>$subcolumn];    
            }
            return $arraydata;
            }
            public function actionReportmember()
            {
                $startday ='2019-05-07';
                $endday ='2019-05-16';
                $date = Report::find()
                ->select(['idCard','created_at'])
                ->where(['between', 'created_at',$startday, $endday ])
                ->orderBy([
                    'created_at'=>SORT_ASC,
                    'idCard'=>SORT_ASC ])
                    ->asArray()
                    ->distinct() 
                ->all();

                foreach ($array as $key => $value)
                {
                $members =Members::find()
                ->select(['id','displayName'])
                ->where(['between', 'id',1,3 ])
                ->asArray()
                ->all();
                return  $date;
            }
            }

            public function actionApireportexcel()
            {
                $rows = (new \yii\db\Query())
                ->select('r.created_at , r.hours ,m.username, c.displayName ,r.idMember,r.idCard')
                ->from('report_times r')
                ->innerJoin('card c', 'r.idCard =c.id ')
                ->innerJoin('member m', ' r.idMember = m.id ')
            ->orderBy('r.created_at ASC')
                ->all();
                return $rows;
                
            }

            //    api.dev.com/v1/apislack/apireportexcel/
            public function actionExport()
            {
//                $data = json_decode(file_get_contents("http://api.dev.com/v1/apislack/apireportexcel/"),true);
                    $data =Yii::$app->request->post();
//                    echo "<pre>";
//                    print_r($data);
//                    echo "</pre>";die();
                    $date = date('d-m-Y_H-i-s');
                    $excel = new PHPExcel();
                    $excel->setActiveSheetIndex(0);
                    $excel->getActiveSheet()->setTitle('BÁO CÁO CARD');
                    //background color
                    $excel->getActiveSheet()->getStyle("A1:F1")->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00ffff00');
                    //font center
                    $excel->getActiveSheet()->getStyle("A1:F1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $excel->getActiveSheet()->getColumnDimension('A')->setWidth(10);
                    $excel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
                    $excel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
                    $excel->getActiveSheet()->getColumnDimension('D')->setWidth(30);
                    $excel->getActiveSheet()->getColumnDimension('E')->setWidth(30);
                    $excel->getActiveSheet()->getColumnDimension('F')->setWidth(30);
                    $excel->getActiveSheet()->getStyle('A1:F1')->getFont()->setBold(true);
                    $excel->getActiveSheet()->setCellValue('A1', 'Date');
                    $excel->getActiveSheet()->setCellValue('B1', 'idMember');
                    $excel->getActiveSheet()->setCellValue('C1', 'hours');
                    $excel->getActiveSheet()->setCellValue('D1', 'displayName');
                    $excel->getActiveSheet()->setCellValue('E1', 'username');
                    $excel->getActiveSheet()->setCellValue('F1', 'created_at');
                    $numRow = 2;
                    foreach ($data as $key => $row) {
                        $excel->getActiveSheet()->setCellValue('A' . $numRow, $key["date"]);
                        foreach ($row as $key3 => $row3){
                            echo "<pre>";
                        print_r($key);
                        print_r($row);
                        echo "</pre>";die();
                            foreach ($row['card'] as $key2 => $row2){
//                                print_r($key);
//                                print_r($row);
//                                echo "</pre>";die();
//                        $excel->getActiveSheet()->setCellValue('A' . $numRow, $row["idCard"]);
//                        $excel->getActiveSheet()->setCellValue('B' . $numRow, $row["idMember"]);
//                        $excel->getActiveSheet()->setCellValue('C' . $numRow, $row["hours"]);
//                        $excel->getActiveSheet()->setCellValue('D' . $numRow, $row["displayName"]);
//                        $excel->getActiveSheet()->setCellValue('E' . $numRow, $row["username"]);
//                        $excel->getActiveSheet()->setCellValue('F' . $numRow, $row["created_at"]);
//                        $numRow++;
                            }
                        }
                    }
                    // border table
                    $styleArray = array(
                        'borders'=>array(
                            'allborders'=>array(
                                'style'=>PHPExcel_Style_Border::BORDER_THIN
                            )
                        )
                    );
                    $excel->getActiveSheet()->getStyle('A1:' . 'F'.($numRow))->applyFromArray($styleArray);
                    $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
//                    ob_end_clean();
//                    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//                    header('Content-Disposition: attachment;filename="data.xlsx"');
//                    header('Cache-Control: max-age=0');
//                    $objWriter->save('php://output');
                    $objWriter->save(__DIR__.'/../../../../exportExcel/data'.$date.'.xlsx');
            }


            /**
             * Auth: Minhanh
             * Email: minhanh.itqn@gmail.com
             * api.dev.com/v1/fillters/exportmember/
             * Decription : return array [day][card][hours] trả về mãng 3 chiêu
             */
            public function actionExportmember()
            {
                $members= [6,7,13,15];
                $members_displayname= Members::find()
                ->select(['id','displayName'])
                ->where(['id'=>$members])
                ->all();
                $date =(new \yii\db\Query())
                ->select('r.created_at ,')
                ->distinct()
                ->from('report_times r')
                ->where(['between', 'created_at','2019-04-2', '2019-10-2'])
                ->orderBy('r.created_at ASC')
                ->all();
                $results=[];
            foreach ($date as $key => $value) 
            { 
                $results[$key]=[
                    'index_day'=> $key,
                    'date'=>$date[$key]['created_at'],
                    'cards'=>$this->actionCardsmembershour($date[$key]['created_at'],$members)
                ];
            }

         $data=[
               'members'=>$members_displayname,
               'data'=>  $results
             ];
            return $data;
            }
            /**
             * Auth: Minhanh
             * Email: minhanh.itqn@gmail.com
             */
            private function actionCardsinday($date)
            {
                $cards =Report::find()->select(['idCard'])
                ->where(['created_at'=>$date])
                ->distinct()
                ->all();
               return $cards;
            }
            /**
             * Auth: Minhanh
             * Email: minhanh.itqn@gmail.com
             */
            private function actionCardsmembershour($date,$members)
            {   
                $cards =$this->actionCardsinday($date);
                $results=[];
                foreach ($cards as $key => $card) 
                {
                    $results[$key]= [
                        'index_card' =>$key,
                        'card'=>$card->idCard,
                        'members'=>$this->actionMembershour($date,$card,$members)
                    ];
                }
                return $results;
            }
            /**
             * Auth: Minhanh
             * Email: minhanh.itqn@gmail.com
             */
            private function actionMembershour($date,$idcard,$members)
            {
                $results=[];
                foreach ($members as $key => $idmember)
                {
                    $results[$key]=[
                        'idMember'=>$idmember,
                        'hour'=>$this->actionHourmember($date,$idcard,$idmember)
                    ];
                }
                return $results;
            }
            /**
             * Auth: Minhanh
             * Email: minhanh.itqn@gmail.com
             */
            private function actionHourmember($date,$idcard,$idmember)
            {
                // $date ="2019-05-15";
                // $idcard=145;
                // $idmember =6;
                $results =Report::find()->select(['hours'])
                ->where(['created_at'=>$date])
                ->andwhere(['idCard'=>$idcard])
                ->andwhere(['idMember'=>$idmember])
                ->limit(1)
                ->all();
              if(!empty($results))
              {
                  return $results[0]->hours;
              }
              else {
                return 0;
              }
            }
            

            }

            