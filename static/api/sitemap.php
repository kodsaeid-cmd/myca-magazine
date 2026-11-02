<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
try {
  $q=cms_db()->query("SELECT slug,updated_at FROM cms_articles WHERE status='published' AND published_at<=UTC_TIMESTAMP() AND robots='index,follow' ORDER BY updated_at DESC LIMIT 5000");
  $rows=$q->fetchAll();
  $origin=rtrim((string)(cms_config()['site_url']??'https://mycablog.dandoonii.ir'),'/');
  echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";
  echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
  echo '<url><loc>'.htmlspecialchars($origin.'/stories/',ENT_XML1).'</loc></url>'."\n";
  foreach($rows as $r) {
    $url=$origin.'/stories/'.$r['slug'].'/';
    echo '<url><loc>'.htmlspecialchars($url,ENT_XML1).'</loc><lastmod>'.gmdate('Y-m-d',strtotime($r['updated_at'].' UTC')).'</lastmod></url>'."\n";
  }
  echo '</urlset>';
} catch(Throwable $e) {
  error_log('MYCA sitemap: '.$e->getMessage());
  http_response_code(503);
  echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"/>';
}
