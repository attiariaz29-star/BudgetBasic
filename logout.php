<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
logout_user();
session_start();
flash('info', 'You have been logged out safely.');
header('Location: index.php');
exit;
