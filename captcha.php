<?php
error_reporting(E_ALL ^ E_NOTICE);

require "captaincaptcha.class.php";

session_start();
mt_srand();

// za verzioniranje - ?v=2 ili ?v=2,3,...
$versions = array();
if (isset($_GET['v']))
{
	$versions = preg_split('#,#', $_GET['v']);
}

$captcha_name = "testcaptcha";
if (isset($_GET['captcha_name']))
{
	$captcha_name = $_GET['captcha_name'];
}

$captcha = new CaptainCaptcha($captcha_name, $versions);
$captcha->render();

?>