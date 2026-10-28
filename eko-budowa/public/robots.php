<?php
require_once __DIR__ . '/../src/bootstrap.php';
header('Content-Type: text/plain; charset=utf-8');
$site = rtrim(env('SITE_URL', 'http://localhost:8080'), '/');
echo "User-agent: *\nDisallow: /admin/\nAllow: /\n\nSitemap: $site/sitemap.xml\n";
