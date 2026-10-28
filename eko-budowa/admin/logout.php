<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { csrf_check(); admin_logout(); }
redirect('/admin/login.php');
