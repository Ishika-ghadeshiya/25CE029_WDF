<?php
session_start();

$timeout = 900; // 15 minutes

if (!isset($_SESSION["user_id"])) {
    header("Location: login.html");
    exit;
}

if (isset($_SESSION["last_activity"]) &&
    time() - $_SESSION["last_activity"] > $timeout) {

    $_SESSION = [];
    session_destroy();

    header("Location: login.html?timeout=1");
    exit;
}

$_SESSION["last_activity"] = time();
?>