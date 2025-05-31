<?php
// filepath: /home/abeni/Dev/quiz-app/logout.php
session_start();
session_unset();
session_destroy();
header('Location: /login.php');
exit;
