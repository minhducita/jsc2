<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \common\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Login';
?>
<div class="row login-container animated fadeInUp">
<div class="col-md-7 col-md-offset-2 tiles white no-padding">
    <div class="site-login">
        <h1><?= Html::encode($this->title) ?></h1>

        <p>Please fill out the following fields to login:</p>

        <div class="row">
            <div class="col-lg-11">
                <?php $form = ActiveForm::begin(['id' => 'login-form']); ?>

                    <?= $form->field($model, 'username', ['template' => '
                    <div class="input-append primary">
                    {input}
                    <span class="add-on"><span class="arrow"></span><i class="fa fa-align-justify"></i> </span>
                       {error}{hint}
                   </div>'])->textInput(['data-default' => '']) ?>
                   <div class="clearbox"></div>
                   <?= $form->field($model, 'password', ['template' => '
                    <div class="input-append primary">
                    {input}
                    <span class="add-on"><span class="arrow"></span><i class="fa fa-lock"></i> </span>
                       {error}{hint}
                   </div>'])->textInput(['data-default' => ''])->passwordInput() ?>
                    <div class="clearbox"></div>
                    <?= $form->field($model, 'rememberMe',['template' => '<div class="checkbox checkbox check-success">
    <input id="checkbox1" type="checkbox" value="1"> <label for="checkbox1">Remember</label>
    </div>']) ?>
                    <div class="form-group">
                        <?= Html::submitButton('Login', ['class' => 'btn btn-primary btn-cons-md', 'name' => 'login-button']) ?>
                    </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>
</div>