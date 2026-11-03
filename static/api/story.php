<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
try {
  $slug=strtolower((string)($_GET['slug']??''));
  if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug)) throw new RuntimeException('NOT_FOUND');
  $q=cms_db()->prepare("SELECT a.*,u.display_name AS author FROM cms_articles a JOIN cms_users u ON a.author_id=u.id WHERE a.slug=? AND a.status='published' AND a.published_at<=UTC_TIMESTAMP() LIMIT 1");
  $q->execute([$slug]);$a=$q->fetch();
  if(!$a) throw new RuntimeException('NOT_FOUND');
  $origin=rtrim((string)(cms_config()['site_url']??'https://mycablog.dandoonii.ir'),'/');
  $canonical=$a['canonical_url']?:$origin.'/stories/'.$slug.'/';
  $title=$a['meta_title']?:$a['title'].' | مجله مایکا';
  $description=$a['meta_description']?:$a['excerpt'];
  $cover=cms_public_image_url((string)($a['cover_url']??''));
  $image=$cover?(str_starts_with($cover,'/')?$origin.$cover:$cover):'';
  $faq=json_decode($a['faq_json']??'[]',true)?:[];
  $schema=['@context'=>'https://schema.org','@type'=>'Article','headline'=>$a['title'],
    'description'=>$description,'datePublished'=>gmdate('c',strtotime($a['published_at'].' UTC')),
    'dateModified'=>gmdate('c',strtotime($a['updated_at'].' UTC')),
    'inLanguage'=>'fa-IR','mainEntityOfPage'=>$canonical,
    'author'=>['@type'=>'Person','name'=>$a['author']],
    'publisher'=>['@type'=>'Organization','name'=>'مایکا','logo'=>['@type'=>'ImageObject','url'=>$origin.'/myca-logo.svg']]];
  if($image) $schema['image']=[$image];
  $faqSchema=[];
  foreach($faq as $f)if(!empty($f['question'])&&!empty($f['answer']))
    $faqSchema[]=['@type'=>'Question','name'=>$f['question'],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f['answer']]];
  $h=fn($s)=>htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
  header('Content-Type: text/html; charset=utf-8');
  header('Cache-Control: public,max-age=90');
  header('X-Content-Type-Options: nosniff');
  ?><!doctype html><html lang="fa" dir="rtl"><head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $h($title) ?></title><meta name="description" content="<?= $h($description) ?>">
  <meta name="robots" content="<?= $h($a['robots']) ?>"><link rel="canonical" href="<?= $h($canonical) ?>">
  <meta property="og:type" content="article"><meta property="og:locale" content="fa_IR">
  <meta property="og:title" content="<?= $h($a['title']) ?>"><meta property="og:description" content="<?= $h($description) ?>">
  <meta property="og:url" content="<?= $h($canonical) ?>">
  <?php if($image): ?><meta property="og:image" content="<?= $h($image) ?>"><?php endif; ?>
  <script type="application/ld+json"><?= json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
  <?php if($faqSchema): ?><script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'FAQPage','mainEntity'=>$faqSchema],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script><?php endif; ?>
  <link rel="preload" href="/fonts/Vazir-Regular.woff2" as="font" type="font/woff2" crossorigin><link rel="stylesheet" href="/vazir-local.css?v=2">
<link rel="stylesheet" href="/cms-public.css"><link rel="icon" href="/favicon.svg">
  </head><body>
    <div class="topline">دانش برای مراقبت بهتر، آرامش برای زندگی</div>
    <header class="sitehead"><a class="logo" href="/"><img src="/myca-logo.svg" alt="مایکا" width="96"> <strong>مجله مایکا</strong></a>
    <nav><a href="/">خانه</a><a href="/stories/">تازه‌ترین مطالب</a><a href="/category/elder-care/">مراقبت سالمند</a><a href="/category/health/">سلامت و زندگی</a></nav><a class="headphone" href="tel:41124">پشتیبانی ۴۱۱۲۴</a></header>
    <main class="article-wrap"><div class="breadcrumb"><a href="/">مجله مایکا</a> / <a href="/stories/">مطالب تازه</a> / <a href="/category/<?= $h($a['category_slug']) ?>/"><?= $h(cms_categories()[$a['category_slug']]??'مراقبت') ?></a></div>
    <article><div class="article-head"><span class="eyebrow"><?= $h(cms_categories()[$a['category_slug']]??'راهنمای مایکا') ?></span>
    <h1><?= $h($a['title']) ?></h1><p class="excerpt"><?= $h($a['excerpt']) ?></p>
    <div class="byline"><?= $h($a['author']) ?> · <time datetime="<?= $h($a['published_at']) ?>"><?= $h(substr($a['published_at'],0,10)) ?></time></div>
    </div>
    <?php if($image): ?><figure class="cover"><img src="<?= $h($image) ?>" alt="<?= $h($a['cover_alt']?:$a['title']) ?>" loading="eager"></figure><?php endif; ?>
    <div class="prose"><?= cms_render_inline_images($a['body_html']) /* sanitized on write; first-party image delivery */ ?></div>
    <?php if($faqSchema): ?><section class="faq"><h2>پرسش‌های متداول</h2><?php foreach($faq as $f): ?><details><summary><?= $h($f['question']) ?></summary><p><?= $h($f['answer']) ?></p></details><?php endforeach; ?></section><?php endif; ?>
    <div class="end-cta"><h3>برای مراقبت مطمئن‌تر همراه شما هستیم</h3><p>برای دریافت خدمات مراقبتی مایکا با ما در ارتباط باشید.</p><a href="tel:41124">تماس با ۴۱۱۲۴</a></div>
    </article></main>
    <footer><a href="/">مجله مایکا</a> · حقوق محتوای مایکا محفوظ است</footer>
  </body></html><?php
} catch(Throwable $e) {
  $notFound=$e->getMessage()==='NOT_FOUND';
  http_response_code($notFound?404:503);
  header('Content-Type:text/html; charset=utf-8');
  if(!$notFound) error_log('MYCA story: '.$e->getMessage());
  echo '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><title>مجله مایکا</title><body style="font:16px Tahoma;margin:80px"><h1>'.($notFound?'مطلب پیدا نشد':'سرویس محتوا موقتاً در دسترس نیست').'</h1><a href="/">بازگشت به مجله</a></body></html>';
}
