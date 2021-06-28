<?php
/**
 * Author: SINHNGUYEN
 * Email: sinhnguyen193@gmail.com
 */
function app()
{
    return Yii::$app;
}


function db()
{
    return Yii::$app->db;
}

function request()
{
    return Yii::$app->request;
}

function response()
{
    return Yii::$app->response;
}

function requestParam($name, $defaultValue = null)
{
    return isset(request()->params[$name]) ? request()->params[$name] : $defaultValue;
}

function isAjaxRequest()
{
    return Yii::$app->request->isAjax;
}

function isPjaxRequest()
{
    return Yii::$app->request->isPjax;
}

function userIdentity()
{
    return Yii::$app->user->identity;
}





