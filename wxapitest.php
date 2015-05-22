<?php
require_once "WxRequestApi.class.php";
$appid = 'wx5e643cb403cf75ab';
$appsecret = '00f94bfe72c04918cc03911656162238';
$apiObj = new WxRequestApi($appid,$appsecret);

$res = $apiObj->getAccess_token();

var_dump($res);

$res = $apiObj->getJsapi_ticket();

var_dump($res);