<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */

$resetLink = Yii::$app->urlManager->createAbsoluteUrl(['#/site/forgot', 'token' => $user->passwordResetToken]);
$resetLink = str_replace("api.","",$resetLink);
$resetLink = str_replace("api","",$resetLink);
$resetLink = str_replace("jawhm.info","jsc.jawhm.info",$resetLink);
?>
<div class="password-reset">
    <p>Hello <?= Html::encode($user->username) ?>,</p>

    <p>Follow the link below to reset your password:</p>

    <p><?= Html::a(Html::encode($resetLink), $resetLink) ?></p>
</div>
