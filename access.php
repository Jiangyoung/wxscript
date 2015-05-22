<?php

$app_root_url = 'http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['REQUEST_URI']);
define('APP_ROOT_URL',rtrim($app_root_url,'/\\'));

//require_once "statrt.php";

require_once "MessageCenter.class.php";

$postStr = file_get_contents("php://input");

$msgCenter = new MessageCenter($postStr);

$msgCenter->dispatch();
