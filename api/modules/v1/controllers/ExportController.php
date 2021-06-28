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
use PHPExcel;
use PHPExcel_Style_Alignment;
use PHPExcel_Style_Border;
use PHPExcel_Autoloader;
use PHPExcel_IOFactory;

class ExportController extends Controller
{
    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * Description :get resquest to print file excel
     */

    public function actionExportFileExcel()
    {
        $dataExport = [];
        $status = false;
        $description = "Success";
        $type = "member";
        $data = Yii::$app->request->post();
        if (!empty($data)) {
            $startday = $data['startDate'];
            $endday = $data['endDate'];
            $idboard = $data['idBoard'];
            $cards = $data['idCards'];
            $members = $data['idMembers'];
            sort($cards);
            sort($members);
            if (array_key_exists('type', $data) && !empty($data['type'])) {
                $type = $data['type']; //type export card or member
            }
            if ($type == 'card') {
                $data = $this->getDataCard($startday, $endday, $idboard, $cards, $members); // data export card type
                $datacards = Card::find()->select(['id', 'displayName'])
                    ->where(['id' => $cards])
                    ->all();
                if (!empty($data) && !empty($datacards)) {
                    $dataExport = [
                        "data" => $data,
                        'cards' => $datacards,
                        'type' => 'card'
                    ];
                    $status = true;
                } else {
                    $description = "Empty datacards or empty data or both are empty";
                }
            } else {
                if ($type == 'member') {
                    // data export member type
                    $data = $this->getDataMember($startday, $endday, $idboard, $cards, $members);
                    $dataMembers = Members::find()->select(['id', 'username', 'displayName'])
                        ->where(['id' => $members])
                        ->all();
                    if (!empty($data) && !empty($dataMembers)) {
                        $dataExport = [
                            "data" => $data,
                            'member' => $dataMembers,
                            'type' => 'member'
                        ];
                        $status = true;
                    } else {
                        $description = "Empty datacards or empty data or both are empty";
                    }
                } else {
                    $description = "Empty datacards or empty data or both are empty";  // Not a card type and member
                }
            }

        } else {
            $description = "request is empty or invalid"; //request empty
        }
        $printFile = $this->actionPrintFile($dataExport);
        if (!empty($printFile)) {
            $status = $printFile['status'];
            $description = $printFile['description'];
        }

        return [
            'status' => $status,
            'description' => $description,
            'export' => $printFile,
            'dataExport' => $dataExport
        ];
    }

    /**
     * Auth: NguyenKhanh
     * Email: nguyenkhanh7493@gmail.com
     * Description :show data filter export file excel
     */
    public function actionGetBoard()
    {
        try {
            $dataBoard = Report::getBoard();
            return $dataBoard;
        } catch (ErrorException $e) {
            Yii::warning($e->getMessage());
        }
    }

    public function actionGetAllCard()
    {
        try {
            $data = Yii::$app->request->post();
            $startDate = $data['startDate'];
            $endDate = $data['endDate'];
            $dataCards = Report::getAllCard($startDate, $endDate);
            return $dataCards;
        } catch (ErrorException $e) {
            Yii::warning($e->getMessage());
        }
    }

    public function actionGetAllMember()
    {
        try {
            $data = Yii::$app->request->post();
            $startDate = $data['startDate'];
            $endDate = $data['endDate'];
            $dataMembers = Report::getAllMember($startDate, $endDate);
            return $dataMembers;
        } catch (ErrorException $e) {
            Yii::warning($e->getMessage());
        }
    }

    public function actionGetCards()
    {
        try {
            $data = Yii::$app->request->post();
            $idBoard = $data['idBoard'];
            $startDate = $data['startDate'];
            $endDate = $data['endDate'];
            $dataCards = Report::getCards($idBoard, $startDate, $endDate);
            return $dataCards;
        } catch (ErrorException $e) {
            Yii::warning($e->getMessage());
        }

    }

    public function actionGetMembers()
    {
        try {
            $data = Yii::$app->request->post();
            $idBoard = $data['idBoard'];
            $startDate = $data['startDate'];
            $endDate = $data['endDate'];
            $dataCards = Report::getMembersNew($idBoard, $startDate, $endDate);
            return $dataCards;
        } catch (ErrorException $e) {
            Yii::warning($e->getMessage());
        }
    }

    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * Description : export  card type
     */
    private function getDataCard($startday, $endday, $idboard, $cards, $members)
    {
        $data = [];
        if ($idboard != '-1') {
            $reports = Report::find()
                ->select(['created_at'])
                ->where(['between', 'created_at', $startday, $endday])
                ->andwhere(['idBoard' => $idboard])
                ->distinct()
                ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
        } else {
            $reports = Report::find()
                ->select(['created_at'])
                ->where(['between', 'created_at', $startday, $endday])
                ->distinct()
                ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
        }
        if (!empty($reports)) {
            foreach ($reports as $key => $report) {
                $data[$report->created_at] = $this->actionMember($members, $cards, $report->created_at, $idboard);
            }
        }
        return $data;
    }

    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     * Desctiption : export member type
     */
    private function getDataMember($startday, $endday, $idboard, $cards, $members)
    {
        $data = [];
        if ($idboard != '-1') {
            $reports = Report::find()
                ->select(['created_at'])
                ->where(['between', 'created_at', $startday, $endday])
                ->andwhere(['idBoard' => $idboard])
                ->distinct()
                ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
        } else {
            $reports = Report::find()
                ->select(['created_at'])
                ->where(['between', 'created_at', $startday, $endday])
                ->distinct()
                ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
        }
        if (!empty($reports)) {
            foreach ($reports as $key => $report) {
                $data[$report->created_at] = $this->actionCards($members, $cards, $report->created_at, $idboard);
            }
        }
        return $data;
    }

    private function actionCards($members, $cards, $date, $idboard)
    {
        $cards = Card::find()->where(['id' => $cards])->all();
        $data = [];
        foreach ($cards as $key => $card) {
            $data[$key] = [
                'id' => $card->id,
                'displayName' => $card->displayName,
                'times' => $this->actionTimeTypeMember($members, (int)$card->id, $date, $idboard),
            ];
        }
        return $data;
    }

    private function actionMember($members, $cards, $date, $idboard)
    {
        $members = Members::find()->where(['id' => $members])->all();
        $data = [];
        foreach ($members as $key => $member) {
            $data[$key] = [
                'displayName' => $member->displayName,
                'times' => $this->actionTimeTypeCard((int)$member->id, $cards, $date, $idboard),
            ];
        }
        return $data;
    }

    private function actionTimeTypeMember($members, $card, $date, $idboard)
    {
        $data = [];
        foreach ($members as $key => $member) {
            $data[$key] = [
                'time' => $this->getHours($member, $card, $date, $idboard)
            ];
        }
        return $data;
    }

    private function actionTimeTypeCard($member, $cards, $date, $idboard)
    {
        $data = [];
        foreach ($cards as $key => $card) {
            $data[$key] = [
                'time' => $this->getHours($member, $card, $date, $idboard)
            ];
        }
        return $data;
    }

    private function getHours($member, $card, $date, $idboard)
    {
        $time = 0;
        if ($idboard != '-1') {
            $report = Report::find()
                ->select(['hours'])
                ->where(['idMember' => $member])
                ->andwhere(['idBoard' => $idboard])
                ->andwhere(['idCard' => $card])
                ->andwhere(['created_at' => $date])
                ->one();
        } else {
            $report = Report::find()
                ->select(['hours'])
                ->where(['idMember' => $member])
                ->andwhere(['idCard' => $card])
                ->andwhere(['created_at' => $date])
                ->one();
        }
        if (!empty($report)) {
            $time = $report['hours'];
        }
        return $time;
    }

    /**
     * Auth: Minhanh + NguyenKhanh
     * Email: minhanh.itqn@gmail.com
     * Desctiption :Print and save file excel on folder server
     */

    private function actionPrintFile($dataExport)
    {
        $status = false;
        $description = "print not success ";
        if ($dataExport != null) {
            $type = isset($dataExport['type']) ? $dataExport['type'] : null;
            $description = "Have dataExport";
            if ($type == 'member') {
                $result = $this->printFileExcelMember($dataExport);
                if ($result['status']) {
                    $status = $result['status'];
                    $description = "print file excel success (type member)";

                } else {
                    $description = $result['description'];
                }
            } elseif ($type == 'card') {

                $result = $this->printFileExcelCard($dataExport);
                if ($result['status']) {
                    $status = $result['status'];
                    $description = "print file excel success (type card)";
                } else {
                    $description = $result['description'];
                }
            } else {
                $description = "Invalid or empty data";
            }
        } else {
            $status = "empty data";
        }
        return ['status' => $status, 'description' => $description];
    }

    private function printFileExcelMember($dataExport)
    {
        try {
            $status = false;
            $description = "";
            $excel = new PHPExcel();
            $excel->setActiveSheetIndex(0);
            $excel->getActiveSheet()->setTitle('REPORT TYPE MEMBER');
            $letters = range('A', 'Z');
            $numCell = 3;
            $name_ids = [];
            $countMember = count($dataExport['member']);
            $v = $letters[($numCell - 2) + $countMember] . '1';
            //Set color
            $excel->getActiveSheet()->getStyle("A1:$v")->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00ffff00');
            //Font center
            $excel->getActiveSheet()->getStyle("A1:$v")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //Set width format
            $excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $excel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
            for ($i = 2; $i <= $countMember + 1; $i++) {
                $excel->getActiveSheet()->getColumnDimension($letters[$i])->setWidth(20);
            }
            //Set font bold
            $excel->getActiveSheet()->getStyle('A1:F1')->getFont()->setBold(true);
            $numRow = 1;
            $excel->getActiveSheet()->setCellValue('A' . $numRow, "Date");
            $excel->getActiveSheet()->setCellValue('B' . $numRow, "Card");
            foreach ($dataExport["member"] as $member) {
                $excel->getActiveSheet()->setCellValue($letters[$numCell - 1] . $numRow, $member["displayName"]);
                $name_ids[$member["id"]] = $letters[$numCell - 1];
                $numCell++;
            }
            $numRow++;
            foreach ($dataExport["data"] as $key => $date_group) {
                $date = $key;
                $num = 1;
                if ($num == 1) {
                    $excel->getActiveSheet()->setCellValue("A" . $numRow, $date);
                } else {
                    $excel->getActiveSheet()->setCellValue("A" . $numRow, null);
                }
                foreach ($date_group as $card) {

                    $excel->getActiveSheet()->setCellValue("B" . $numRow, $card['id']);
                    $timelogs = $dataExport["member"];
                    foreach ($timelogs as $timelog) {
                        $numCell = 3;
                        foreach ($card['times'] as $times) {
                            $excel->getActiveSheet()->setCellValue($letters[$numCell - 1] . $numRow, $times["time"]);
                            $name_ids[$timelog["id"]] = $letters[$numCell - 1];
                            $numCell++;
                        }
                    }
                    $num++;
                    $numRow++;
                }
            }
            // Border table
            $styleArray = array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN
                    )
                )
            );
            $columns = 3;
            $count_member = count($dataExport['member']);
            $b = $letters[($columns - 2) + $count_member];
            $excel->getActiveSheet()->getStyle('A1:' . $b . ($numRow))->applyFromArray($styleArray);
            $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $objWriter->save(__DIR__ . '/../../../../frontend/web/export/ExportMember.xlsx');
            $status = true;
        } catch (ErrorException $e) {
            $description = "Export type Card not success";
        }
        return ['status' => $status, 'description' => $description];
    }

    private function printFileExcelCard($dataExport)
    {
        try {
            $status = false;
            $description = "";
            $excel = new PHPExcel();
            $excel->setActiveSheetIndex(0);
            $excel->getActiveSheet()->setTitle('REPORT TYPE MEMBER');
            $letters = range('A', 'Z');
            $numCell = 3;
            $name_ids = [];
            $countCard = count($dataExport['cards']);
            $v = $letters[($numCell - 2) + $countCard] . '1';
            //Set color
            $excel->getActiveSheet()->getStyle("A1:$v")->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB('00ffff00');
            //Font center
            $excel->getActiveSheet()->getStyle("A1:$v")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //Set width format
            $excel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $excel->getActiveSheet()->getColumnDimension('B')->setWidth(40);
            for ($i = 2; $i <= $countCard + 1; $i++) {
                $excel->getActiveSheet()->getColumnDimension($letters[$i])->setWidth(20);
            }
            //Set font bold
            $excel->getActiveSheet()->getStyle('A1:F1')->getFont()->setBold(true);
            $numRow = 1;
            $excel->getActiveSheet()->setCellValue('A' . $numRow, "Date");
            $excel->getActiveSheet()->setCellValue('B' . $numRow, "member");
            foreach ($dataExport["cards"] as $cards) {
                $excel->getActiveSheet()->setCellValue($letters[$numCell - 1] . $numRow, $cards["id"]);
                $name_ids[$cards["id"]] = $letters[$numCell - 1];
                $numCell++;
            }
            $numRow++;
            foreach ($dataExport["data"] as $key => $data_group) {
                $date = $key;
                $num = 1;
                if ($num == 1) {
                    $excel->getActiveSheet()->setCellValue("A" . $numRow, $date);
                } else {
                    $excel->getActiveSheet()->setCellValue("A" . $numRow, null);
                }
                foreach ($data_group as $member_group) {
                    $excel->getActiveSheet()->setCellValue("B" . $numRow, $member_group['displayName']);
                    $timelogs = $dataExport["cards"];
                    foreach ($timelogs as $timelog) {
                        $numCell = 3;
                        foreach ($member_group['times'] as $times) {
                            $excel->getActiveSheet()->setCellValue($letters[$numCell - 1] . $numRow, $times["time"]);
                            $name_ids[$timelog["id"]] = $letters[$numCell - 1];
                            $numCell++;
                        }
                    }
                    $num++;
                    $numRow++;
                }
            }
            // Border table
            $styleArray = array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN
                    )
                )
            );
            $columns = 3;
            $count_member = count($dataExport['cards']);
            $b = $letters[($columns - 2) + $count_member];
            $excel->getActiveSheet()->getStyle('A1:' . $b . ($numRow))->applyFromArray($styleArray);
            $objWriter = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $objWriter->save(__DIR__ . '/../../../../frontend/web/export/ExportCard.xlsx');
            $status = true;
        } catch (ErrorException $e) {
            $description = "Export type Card not success";
        }
        return ['status' => $status, 'description' => $description];
    }
}

