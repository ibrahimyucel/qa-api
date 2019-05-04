<?php

require_once('settings.php');

?>
<html>
<head>
<style type="text/css">

#login-button {
	display: block;
	text-align: center;
	margin: 50px 0;
}

</style>
</head>

<body>

<a id="login-button" href="<?= 'https://accounts.google.com/o/oauth2/auth?scope=' . urlencode('https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email') . '&redirect_uri=' . urlencode(CLIENT_REDIRECT_URL) . '&response_type=code&client_id=' . CLIENT_ID . '&access_type=online' ?>">Login with Google</a>

</body>
</html>