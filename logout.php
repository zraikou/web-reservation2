<?php
require_once 'php/config.php';
require_once 'php/auth.php';

$result = logoutUser();
redirectWith('login.php', 'You have been successfully logged out.', 'info'); 