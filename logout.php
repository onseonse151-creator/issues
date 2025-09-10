<?php
session_start();
session_unset();
session_destroy();

// Clear remember cookie
setcookie('user_id', '', time() - 3600, '/');

header('Location: landing_page.php');
exit;


