<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
try {
  $db=cms_db();
  $search=mb_substr(trim((string)($_GET['q']??'')),0,80);
  $category=(string)($_GET['category']??'');
  if($category!==''&&!array_key_exists($category,cms_categories())) {http_response_code(404);exit('دسته‌بندی پیدا نشد.');}
  $origin=rtrim((string)(cms_config()['site_url']??'https://mycablog.dandoonii.ir'),'/');
  $categoryTitle=$category?(cms_categories()[$category]??''):'';
  $isSearch=$search!=='';
  $sql="SELECT slug,title,excerpt,cover_url,cover_alt,category_slug,published_at FROM cms_articles WHERE status='published' AND published_at<=UTC_TIMESTAMP()";
  $args=[];
  if($category && array_key_exists($category,cms_categories())){$sql.=' AND category_slug=?';$args[]=$category;}
  if($search){$sql.=' AND (title LIKE ? OR excerpt LIKE ?)';$args[]='%'.$search.'%';$args[]='%'.$search.'%';}
  $sql.=' ORDER BY published_at DESC LIMIT 72';
  $q=$db->prepare($sql);$q->execute($args);$items=$q->fetchAll();
  $h=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
  $pageTitle=$categoryTitle?'مقالات '.$categoryTitle.' | مجله مایکا':'جدیدترین مقالات مجله مایکا';
  $description=$categoryTitle?'راهنماهای عملی درباره '.$categoryTitle.' برای خانواده‌ها و مراقبین در مجله مایکا.':'راهنماهای تخصصی مراقبت سالمند، سلامت، ایمنی منزل و تجهیزات مراقبتی در مجله مایکا.';
  $canonical=$category?$origin.'/category/'.$category.'/':$origin.'/stories/';
  $noindex=$isSearch || ($category!=='' && !$items);
  header('Content-Type:text/html; charset=utf-8');
  header('Cache-Control:public,max-age=90');
  ?><!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $h($pageTitle) ?></title>
  <meta name="description" content="<?= $h($description) ?>">
  <meta name="robots" content="<?= $noindex?'noindex,follow':'index,follow' ?>">
  <?php if(!$isSearch): ?><link rel="canonical" href="<?= $h($canonical) ?>"><?php endif; ?>
  <link rel="preload" href="/fonts/Vazir-Regular.woff2" as="font" type="font/woff2" crossorigin><link rel="stylesheet" href="/vazir-local.css?v=2">
<link rel="stylesheet" href="/cms-public.css"><link rel="icon" href="/favicon.svg"></head>
  <body><div class="topline">دانش برای مراقبت بهتر، آرامش برای زندگی</div>
  <header class="sitehead"><a class="logo" href="/"><img src="/myca-logo.svg" alt="مایکا" width="96"> <strong>مجله مایکا</strong></a>
  <nav><a href="/">خانه</a><a href="/category/elder-care/">مراقبت سالمند</a><a href="/category/health/">سلامت و زندگی</a></nav><a class="headphone" href="tel:41124">۴۱۱۲۴</a></header>
  <main class="stories-wrap"><span class="eyebrow">MYCA CONTENT</span><h1><?= $h($pageTitle) ?></h1>
  <p class="excerpt">راهنماهای کاربردی و معتبر برای خانواده‌ها و مراقبین</p>
  <form method="get" action="/stories/" class="story-filters"><input type="search" name="q" value="<?= $h($search) ?>" placeholder="جست‌وجوی مقالات…">
    <select name="category"><option value="">همه موضوعات</option><?php foreach(cms_categories() as $slug=>$label): ?><option value="<?= $h($slug) ?>" <?= $category===$slug?'selected':'' ?>><?= $h($label) ?></option><?php endforeach; ?></select><button>جست‌وجو</button></form>
  <section class="story-grid"><?php foreach($items as $a): ?>
    <a class="story-card" href="/stories/<?= $h($a['slug']) ?>/">
    <?php if($a['cover_url']): ?><img src="<?= $h($a['cover_url']) ?>" loading="lazy" alt="<?= $h($a['cover_alt']) ?>"><?php endif; ?>
    <div><span class="eyebrow"><?= $h(cms_categories()[$a['category_slug']]??'مایکا') ?></span>
    <h2><?= $h($a['title']) ?></h2><p><?= $h($a['excerpt']) ?></p></div></a>
  <?php endforeach; ?></section><?php if(!$items): ?><p class="empty">هنوز مطلبی با این مشخصات منتشر نشده است.</p><?php endif; ?></main>
  <footer>مایکا · همراه خانواده‌ها</footer></body></html><?php
} catch(Throwable $e) {
  error_log('MYCA stories: '.$e->getMessage());
  http_response_code(503);
  echo 'سرویس مجله هنوز به پایگاه داده متصل نشده است.';
}
