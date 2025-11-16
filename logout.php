<?php
include 'includes/session_config.php';
session_destroy();
header('Location: index.php');
exit;
