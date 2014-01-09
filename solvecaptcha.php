<?php

require "captaincaptcha.class.php";

session_start();

$captchas = array(	
	array('captcha6', CaptainCaptcha::$VERSION_NOROTATION . ',' . CaptainCaptcha::$VERSION_PLAIN),
	array('captcha7', CaptainCaptcha::$VERSION_NOROTATION . ',' . CaptainCaptcha::$VERSION_PLAIN . ',' . CaptainCaptcha::$VERSION_COLORFUL),
	array('captcha5', CaptainCaptcha::$VERSION_PLAIN),	
	array('captcha1', CaptainCaptcha::$VERSION_NORMAL),
	array('captcha2', CaptainCaptcha::$VERSION_TEXTURE),
	array('captcha3', CaptainCaptcha::$VERSION_DOUBLE_TEXT),
	array('captcha4', CaptainCaptcha::$VERSION_SLEEP),
	array('captcha8', CaptainCaptcha::$VERSION_EASY . ',' . CaptainCaptcha::$VERSION_TEXTURE),
);

?>
<!doctype>
<html>
<head>
	<meta charset="utf-8">
	<title>CAPTCHA primjer</title>
	<style type="text/css">
	body {
		font:11px verdana;
	}
	.captchafield {
		float:left;
		margin:50px;
	}
	</style>
</head>
<body>
<?php


if (isset($_GET['do']) && $_GET['do'] == 'submit')
{
	foreach ($captchas as $captcha)
	{
		if (isset($_GET[$captcha[0] . 'text']))
		{
			if ($_SESSION[$captcha[0]] == md5($_GET[$captcha[0].'text']))
			{
?><span style="color:green">Bravo! Ispravan unos!</span><?php
			}
			else
			{
?><span style="color:red">Krivi unos!</span><?php
			}
			exit;
		}
	}

	echo "No session text!";
}
else
{
	foreach ($captchas as $captcha)
	{
?>
	<div class="captchafield">
	<img src="captcha.php?v=<?php echo $captcha[1]; ?>&captcha_name=<?php echo $captcha[0]; ?>" alt="<?php echo $captcha[0]; ?>" />
	<form action="solvecaptcha.php" method="get">
		<input type="hidden" name="do" value="submit" />
		<input type="text" name="<?php echo $captcha[0]; ?>text" />

		<input type="submit" value="Pošalji" />
	</form>
	</div>
<?php
	}
}

?>
</body>
</html>