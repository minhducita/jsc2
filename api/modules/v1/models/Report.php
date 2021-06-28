<?php

namespace api\modules\v1\models;

use Yii;
use api\common\models\Member;
use api\common\models\Memcache;

use api\modules\v1\models\Members;
use api\modules\v1\models\Notifications;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * This is the model class for table "card".
 *
 * @property integer $id
 * @property integer $hours
 * @property integer $idMember
 * @property integer $idCard
 * @property string $created_at
 * @property string $updated_at
 *

 */
class Report extends \yii\com\db\ModelActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'report_times';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // the name, email, subject and body attributes are required
            [['hours', 'idMember', 'idCard', 'created_at'], 'required'],
        ];
    }

    public function extraFields()
    {
        return ['attachments', 'checklists', 'members', 'memberShips', 'comments', 'lists', 'notifications'];
    }

    public function getAttachments()
    {
        $attachments = $this->hasMany(Attachments::className(), ['idCard' => 'id']);
        return Attachments::findCacheAll($attachments, 1);
    }

    public function getChecklists()
    {
        $checklist = $this->hasMany(Checklist::className(), ['idCard' => 'id']);
        return Checklist::findCacheAll($checklist, 1);
    }

    public function getComments()
    {
        $comments = $this->hasMany(Comments::className(), ['idCard' => 'id'])->orderBy(['addedDate' => SORT_DESC]);
        return Comments::findCacheAll($comments, 1);
    }

    public function getMembers()
    {
        $member = Member::find()->where([
            'id' => CardMembers::find()->select(['idMember'])->where(['idCard' => $this->id])
        ]);
        return Member::findCacheAll($member);
    }

    public function getTimeReport()
    {
        $time = Report::find()
            ->all();
        return $time;
    }
    /**
     * Auth: Minhanh
     * Email: minhanh.itqn@gmail.com
     */

    //TH1: get all members in a card
    public function getAllmembers($idCard, $startday, $endday)
    {
        $result = Report::find()->select(['idMember', 'idCard'])
            ->where(['idCard' => $idCard])
            ->andwhere(['between', 'created_at', $startday, $endday])
            ->orderBy([
                'created_at' => SORT_DESC,
                'id' => SORT_DESC
            ])
            ->distinct()
            ->all();
        return $result;
    }

    //TH1.1: get all members in a card no have day
    public function getAllmembersnoday($idCard)
    {
        $result = Report::find()->select(['idMember', 'idCard'])
            ->where(['idCard' => $idCard])
            ->orderBy([
                'created_at' => SORT_DESC,
                'id' => SORT_DESC
            ])
            ->distinct()
            ->all();
        return $result;
    }

    //TH2: get only member in a card
    public function getOnlymember($idMember, $idCard, $startday, $endday)
    {
        $result = Report::find()->where(['idCard' => $idCard, 'idMember' => $idMember])
            ->andwhere(['between', 'created_at', $startday, $endday])
            ->orderBy([
                'created_at' => SORT_DESC,
                'id' => SORT_DESC
            ])
            ->all();
        return $result;
    }

    //TH3 get all members in all cards
    public function All($startday, $endday)
    {
        $result = Report::find()->where(['between', 'created_at', $startday, $endday])
            ->orderBy([
                ['id' => SORT_DESC],
                ['created_at' => SORT_DESC]
            ])
            ->all();
        return $result;
    }

    // TH4 get only member in all cards
    public function allCard($idMember, $startday, $endday)
    {
        $result = Report::find()->where(['idMember' => $idMember])
            ->andwhere(['between', 'create_at', $startday, $endday])
            ->orderBy([
                ['id' => SORT_DESC],
                ['created_at' => SORT_DESC]
            ])
            ->all();
        return $result;
    }

    //TH5 sum column
    public function sumOnlymember($idMember, $idCard, $startday, $endday)
    {
        $result = Report::find()->where(['idCard' => $idCard, 'idMember' => $idMember])
            ->andwhere(['between', 'created_at', $startday, $endday])
            ->sum('hours');
        return $result;
    }

    //TH5 sum column no day
    public function sumOnlymembernoday($idMember, $idCard)
    {
        $result = Report::find()->where(['idCard' => $idCard, 'idMember' => $idMember])
            ->sum('hours');
        return $result;

    }

    /**
     * Auth: NguyenKhanh
     * Email: nguyenkhanh7493@gmail.com
     * Description :show data filter export file excel
     */
    public function getBoard()
    {
        $listBoard = (new \yii\db\Query())
            ->select(['b.id', 'b.displayName'])
            ->from('report_times r')
            ->innerJoin('board b', 'r.idBoard = b.id')
            ->groupBy(['b.id', 'b.displayName'])
            ->all();
        return $listBoard;
    }

    public function getCards($idBoard, $startDate, $endDate)
    {
        $listCard = (new \yii\db\Query())
            ->select(['c.id as idCard', 'c.displayName as displayName'])
            ->from('card c')
            ->innerJoin('report_times r', 'r.idCard = c.id')
            ->innerJoin('board b', 'r.idBoard = b.id')
            ->where(['=', 'r.idBoard', $idBoard])
            ->andWhere(['between', 'created_at', $startDate, $endDate])
            ->groupBy(['idCard', 'displayName'])
            ->all();
        return $listCard;
    }

    public function getMembersNew($idBoard, $startDate, $endDate)
    {
        $listMember = (new \yii\db\Query())
            ->select(['r.idMember as idMember', 'm.username as nameMember'])
            ->from('member m')
            ->innerJoin('report_times r', 'm.id = r.idMember')
            ->innerJoin('board b', 'r.idBoard = b.id')
            ->where(['=', 'r.idBoard', $idBoard])
            ->andWhere(['between', 'created_at', $startDate, $endDate])
            ->groupBy(['idMember', 'nameMember'])
            ->all();
        return $listMember;
    }

    public function getAllCard($startDate, $endDate)
    {
        $listCard = (new \yii\db\Query())
            ->select(['c.id as idCard', 'c.displayName as displayName'])
            ->from('card c')
            ->innerJoin('report_times r', 'r.idCard = c.id')
            ->andWhere(['between', 'created_at', $startDate, $endDate])
            ->groupBy(['idCard', 'displayName'])
            ->all();
        return $listCard;
    }

    public function getAllMember($startDate, $endDate) 
    {
        $listMember = (new \yii\db\Query())
            ->select(['r.idMember as idMember', 'm.username as nameMember'])
            ->from('member m')
            ->innerJoin('report_times r', 'm.id = r.idMember')
            ->where(['between', 'created_at', $startDate, $endDate])
            ->groupBy(['idMember', 'nameMember'])
            ->all();
        return $listMember;
    }
}

