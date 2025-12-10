<?php
require_once 'config/database.php';
startSession();
session_unset();
session_destroy();
header('Location: index.php');
exit;
?>