<?php
require_once __DIR__ . '/../src/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$site = rtrim(env('SITE_URL', 'http://localhost:8080'), '/');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>' . e($site) . '/</loc><lastmod>' . date('Y-m-d') . '</lastmod><changefreq>monthly</changefreq><priority>1.0</priority></url></urlset>';
