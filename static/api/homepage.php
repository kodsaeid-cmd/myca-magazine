<?php
declare(strict_types=1);
// Server-rendered CMS homepage; old static homepage remains the safe fallback
// until the editor explicitly publishes the first layout.
require_once __DIR__.'/layout.php';
$preview=isset($_GET['preview']) && $_GET['preview']==='1';
if($preview) cms_user('editor');
try {
  $db=cms_db();
  $row=cms_layout_record($db);
  $config=$preview?($row?cms_layout_parse($row['draft_json']):cms_layout_default()):($row?cms_layout_parse($row['published_json']):null);
  if(!$config&&!$preview) {
    header('Cache-Control:public,max-age=90');
    readfile(__DIR__.'/../index.html');
    exit;
  }
  $layout=cms_layout_resolve($db,$config??cms_layout_default());
} catch(Throwable $e) {
  error_log('MYCA editorial homepage: '.$e->getMessage());
  if($preview) {http_response_code(503);header('Content-Type:text/plain; charset=utf-8');echo 'پیش‌نمایش در دسترس نیست؛ دیتابیس و جدول چیدمان را بررسی کنید.';exit;}
  // Do not break the running magazine when the optional migration is pending.
  header('Cache-Control:public,max-age=90');
  readfile(__DIR__.'/../index.html');
  exit;
}
$h=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
$origin=rtrim((string)(cms_config()['site_url']??'https://mycablog.dandoonii.ir'),'/');
$links=static fn($a)=>'/stories/'.rawurlencode((string)$a['slug']).'/';
$photo=static function($a)use($h) {
  $url=cms_safe_url((string)($a['cover_url']??''));
  return $url?'<img src="'.$h($url).'" alt="'.$h($a['cover_alt']?:$a['title']).'" loading="lazy">':'<span class="editorial-photo-missing">مجله مایکا</span>';
};
header('Content-Type:text/html; charset=utf-8');
header('Cache-Control:'.($preview?'no-store, private':'public,max-age=90'));
header('X-Content-Type-Options:nosniff');
if($preview) header('X-Robots-Tag:noindex, nofollow');
?><!doctype html><html lang="fa" dir="rtl"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>مجله مایکا | دانش برای مراقبت بهتر، زندگی آرام‌تر</title>
<meta name="description" content="مجله مایکا؛ راهنماهای مراقبت سالمند، کودک، خانواده، سلامت و تجهیزات مراقبتی.">
<meta name="robots" content="<?= $preview?'noindex,nofollow':'index,follow' ?>">
<link rel="canonical" href="<?= $h($origin.'/') ?>">
<meta property="og:type" content="website"><meta property="og:title" content="مجله مایکا | MYCA">
<link rel="icon" href="/favicon.svg"><link rel="stylesheet" href="/cms-public.css"><link rel="stylesheet" href="/cms-home.css">
</head><body class="editorial-home">
<a class="editorial-skip" href="#main-content">رفتن به محتوا</a>
<div class="topline">دانش برای مراقبت بهتر، آرامش برای زندگی</div>
<header class="sitehead"><a class="logo" href="/"><img src="/myca-logo.svg" width="116" height="43" alt="MYCA"><strong>مجله مایکا</strong></a>
<nav aria-label="ناوبری اصلی"><a href="/">خانه</a><a href="/stories/">تازه‌ترین مطالب</a><a href="/stories/?category=elder-care">مراقبت سالمند</a><a href="/stories/?category=child-family">کودک و خانواده</a><a href="/stories/?category=equipment">تجهیزات مراقبتی</a></nav>
<a class="headphone" href="tel:41124">پشتیبانی ۴۱۱۲۴</a></header>
<?php if($preview): ?><aside class="editorial-preview-label">پیش‌نمایش خصوصی چیدمان ذخیره‌شده — این نسخه هنوز منتشر نشده است.</aside><?php endif; ?>
<main id="main-content" class="editorial-wrap">
<?php foreach($layout['sections'] as $section): if(!$section['enabled'])continue;
  $sid=$section['id'];$items=$section['articles']??[];
  if($sid==='hero'):?>
    <section class="editorial-hero" aria-label="<?= $h($section['title']) ?>">
    <?php if($items): $lead=$items[0]; ?>
      <a class="editorial-hero-main" href="<?= $h($links($lead)) ?>">
        <?= $photo($lead) ?><span class="editorial-img-overlay"></span>
        <div class="editorial-hero-copy"><span class="editorial-kicker">انتخاب مجله مایکا</span><h1><?= $h($lead['title']) ?></h1><p><?= $h($lead['excerpt']) ?></p><span class="editorial-read">مطالعه مقاله</span></div>
      </a>
      <div class="editorial-hero-side"><h2><?= $h($section['title']) ?></h2>
      <?php foreach(array_slice($items,1) as $a): ?><a href="<?= $h($links($a)) ?>" class="editorial-hero-link">
        <?= $photo($a) ?><span><small><?= $h(cms_categories()[$a['category_slug']]??'مایکا') ?></small><strong><?= $h($a['title']) ?></strong></span>
      </a><?php endforeach; ?>
      </div>
    <?php else: ?><div class="editorial-empty">برای نمایش مقاله شاخص، نخست مقاله‌ای را منتشر کنید.</div><?php endif; ?>
    </section>
  <?php elseif($sid==='categories'):
    $catNames=cms_categories(); ?>
    <section class="editorial-section"><div class="editorial-section-heading"><h2><?= $h($section['title']) ?></h2><p>راهنمای خانواده‌ها برای مراقبت آگاهانه‌تر</p></div>
    <div class="editorial-category-grid">
    <?php foreach($section['category_slugs'] as $category): ?>
      <a class="editorial-category" href="/stories/?category=<?= $h(rawurlencode($category)) ?>"><span class="editorial-category-icon">✦</span><strong><?= $h($catNames[$category]??$category) ?></strong><small>مشاهده مقالات</small></a>
    <?php endforeach; ?></div></section>
  <?php elseif($sid==='editors'||$sid==='latest'): ?>
    <section class="editorial-section"><div class="editorial-section-heading"><h2><?= $h($section['title']) ?></h2><a href="/stories/">مشاهده همه مقالات</a></div>
      <div class="editorial-article-grid <?= $sid==='editors'?'editorial-feature-grid':'' ?>">
      <?php foreach($items as $a): ?><a class="editorial-article" href="<?= $h($links($a)) ?>">
        <div class="editorial-article-image"><?= $photo($a) ?></div>
        <div class="editorial-article-copy"><span class="editorial-tag"><?= $h(cms_categories()[$a['category_slug']]??'مایکا') ?></span><h3><?= $h($a['title']) ?></h3><p><?= $h($a['excerpt']) ?></p><span class="editorial-more">ادامه مطلب</span></div>
      </a><?php endforeach; ?>
      <?php if(!$items): ?><div class="editorial-empty">در این بخش هنوز مقاله منتشرشده‌ای وجود ندارد.</div><?php endif; ?>
      </div></section>
  <?php elseif($sid==='banner'): ?>
    <section class="editorial-cta"><div><span>MYCA · MyCare</span><h2><?= $h($section['title']) ?></h2><p>مایکا به خانواده‌ها کمک می‌کند خدمات مراقبتی مناسب را پیدا کنند و پیگیری خدمت را در مسیر مشخصی انجام دهند.</p><a href="tel:41124">تماس با ۴۱۱۲۴</a></div></section>
  <?php endif; endforeach; ?>
</main>
<footer><a href="/">مجله مایکا</a> · دانش برای مراقبت بهتر · پشتیبانی ۴۱۱۲۴</footer>
</body></html>
