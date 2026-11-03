<?php
declare(strict_types=1);
// MYCA local photo transport: readable, validated images from the site's own
// webroot. This is a public image endpoint, not a file browser or generic proxy.
// Useful where a hosting hotlink rule or MIME policy blocks direct .webp files.
$webRoot=dirname(__DIR__);
$source=(string)($_GET['p']??'');
if (strlen($source)>240 ||
    !preg_match('~^/(?:media/(?:editorial|editorial-more|editorial-inline|uploads)|media-check)/[A-Za-z0-9_./-]+\\.(?:jpg|jpeg|png|webp)$~D',$source) ||
    str_contains($source,'..') || str_contains($source,'//')) {
  http_response_code(404); exit;
}
$allowedBase= str_starts_with($source,'/media-check/')
  ? $webRoot.'/media-check' : $webRoot.'/media';
$base=realpath($allowedBase);
$path=realpath($webRoot.$source);
if (!$base||!$path||!str_starts_with($path,$base.DIRECTORY_SEPARATOR)||!is_file($path)||!is_readable($path)) {
  http_response_code(404); exit;
}
$bytes=filesize($path);
if ($bytes<100||$bytes>8*1024*1024) {http_response_code(404);exit;}
$info=@getimagesize($path);
$mime=(string)($info['mime']??'');
if (!in_array($mime,['image/jpeg','image/png','image/webp'],true) ||
    (int)($info[0]??0)<30 || (int)($info[1]??0)<30) {
  http_response_code(415); exit;
}
header('Content-Type: '.$mime);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
header('Content-Disposition: inline');
$etag='"'.hash('sha256',$source.'|'.$bytes.'|'.filemtime($path)).'"';
header('ETag: '.$etag);
if ((string)($_SERVER['HTTP_IF_NONE_MATCH']??'')===$etag) {
  http_response_code(304); exit;
}
header('Content-Length: '.$bytes);
if (($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;
readfile($path);
