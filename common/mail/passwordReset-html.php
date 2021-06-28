<?php
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $user common\models\User */
?>
<div class="password-reset">
    <p>Hello <?= Html::encode($user->username) ?>,</p>
    <p>Password: <span style="font-size:20px;font-weight:bold"><?= $password ?></span> </p>
</div>
