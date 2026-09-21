<?php
session_start();

// সমস্ত সেশন ডাটা মুছে ফেলা
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// হোমপেজে পাঠিয়ে সাথে সাথে লগইন ড্রপডাউনটি ওপেন রাখার নির্দেশ
header("Location: index.php?show_login=1");
exit();
?>