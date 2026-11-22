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
  $sources=json_decode($a['sources_json']??'[]',true)?:[];
  $selectedRelated=json_decode($a['related_article_ids']??'[]',true)?:[];
  $expertName=trim((string)($a['expert_name']??''));
  $expertTitle=trim((string)($a['expert_title']??''));
  $expertQuote=trim((string)($a['expert_quote']??''));
  // Build stable navigable anchors from H2/H3 in the stored, server-sanitized HTML.
  $bodyDoc=new DOMDocument();
  $oldErrors=libxml_use_internal_errors(true);
  $bodyDoc->loadHTML('<!doctype html><html><head><meta charset="utf-8"></head><body><div id="myca-prose-root">'.(string)$a['body_html'].'</div></body></html>', LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
  libxml_clear_errors();libxml_use_internal_errors($oldErrors);
  $bodyRoot=$bodyDoc->getElementById('myca-prose-root');
  $sections=[];$bodyHtml='';
  if($bodyRoot){
    foreach($bodyRoot->getElementsByTagName('*') as $node){
      if(!in_array(strtolower($node->nodeName),['h2','h3'],true))continue;
      $text=trim($node->textContent);
      if($text==='')continue;
      $anchor='section-'.(count($sections)+1);
      $node->setAttribute('id',$anchor);
      $sections[]=['id'=>$anchor,'level'=>$node->nodeName,'text'=>$text];
    }
    foreach($bodyRoot->childNodes as $child)$bodyHtml.=$bodyDoc->saveHTML($child);
  }
  $relatedItems=[];
  $ids=array_slice(array_values(array_unique(array_filter(array_map('intval',(array)$selectedRelated),static fn($v)=>$v>0 && $v!==(int)$a['id']))),0,6);
  if($ids){
    $placeholders=implode(',',array_fill(0,count($ids),'?'));
    $qr=cms_db()->prepare("SELECT id,slug,title,excerpt,cover_url FROM cms_articles WHERE status='published' AND published_at<=UTC_TIMESTAMP() AND id IN ($placeholders)");
    $qr->execute($ids);$found=[];
    foreach($qr->fetchAll() as $related)$found[(int)$related['id']]=$related;
    foreach($ids as $rid)if(isset($found[$rid]))$relatedItems[]=$found[$rid];
  }
  if(!$relatedItems){
    $qr=cms_db()->prepare("SELECT id,slug,title,excerpt,cover_url FROM cms_articles WHERE status='published' AND published_at<=UTC_TIMESTAMP() AND category_slug=? AND id<>? ORDER BY published_at DESC LIMIT 4");
    $qr->execute([$a['category_slug'],$a['id']]);$relatedItems=$qr->fetchAll();
  }
  $schema=['@context'=>'https://schema.org','@type'=>'Article','headline'=>$a['title'],
    'description'=>$description,'datePublished'=>gmdate('c',strtotime($a['published_at'].' UTC')),
    'dateModified'=>gmdate('c',strtotime($a['updated_at'].' UTC')),
    'inLanguage'=>'fa-IR','mainEntityOfPage'=>$canonical,
    'author'=>['@type'=>'Person','name'=>$a['author']],
    'publisher'=>['@type'=>'Organization','name'=>'مایکا','logo'=>['@type'=>'ImageObject','url'=>$origin.'/myca-logo.svg']]];
  if($image) $schema['image']=[$image];
  $citationUrls=[];
  foreach($sources as $source)if(is_array($source)&&!empty($source['url'])){
    $safe=cms_safe_url((string)$source['url'],false);
    if($safe!=='')$citationUrls[]=$safe;
  }
  if($citationUrls)$schema['citation']=$citationUrls;
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
    <div class="article-content-grid"><div class="article-primary">
    <?php if($sections): ?><nav class="article-toc" aria-label="فهرست مطالب"><h2>در این مقاله می‌خوانید</h2><ol><?php foreach($sections as $section): ?><li class="<?= $section['level']==='h3'?'toc-sub':'' ?>"><a href="#<?= $h($section['id']) ?>"><?= $h($section['text']) ?></a></li><?php endforeach; ?></ol></nav><?php endif; ?>
    <div class="prose"><?= cms_render_inline_images($bodyHtml) /* sanitized on write; first-party image delivery */ ?></div>
    <?php if($expertName&&$expertQuote): ?><section class="expert-opinion"><span class="eyebrow">دیدگاه تخصصی</span><blockquote><?= nl2br($h($expertQuote)) ?></blockquote><strong><?= $h($expertName) ?></strong><?php if($expertTitle): ?><small><?= $h($expertTitle) ?></small><?php endif; ?></section><?php endif; ?>
    <?php if($sources): ?><section class="article-sources"><h2>منابع و ارجاعات</h2><ol><?php foreach($sources as $source): ?><?php if(is_array($source)&&!empty($source['title'])): ?><li><?= $h($source['title']) ?><?php $refUrl=cms_safe_url((string)($source['url']??''),false);if($refUrl): ?> — <a href="<?= $h($refUrl) ?>" target="_blank" rel="noopener noreferrer">مشاهده منبع</a><?php endif; ?></li><?php endif; ?><?php endforeach; ?></ol></section><?php endif; ?>
    <?php if($relatedItems): ?><section class="article-bottom-related"><h2>مطالب مرتبط برای مطالعه بیشتر</h2><div class="related-list"><?php foreach($relatedItems as $r): ?><a href="/stories/<?= $h($r['slug']) ?>/"><?= $h($r['title']) ?></a><?php endforeach; ?></div></section><?php endif; ?>
    <?php if($faqSchema): ?><section class="faq"><h2>پرسش‌های متداول</h2><?php foreach($faq as $f): ?><details><summary><?= $h($f['question']) ?></summary><p><?= $h($f['answer']) ?></p></details><?php endforeach; ?></section><?php endif; ?>
    <div class="end-cta"><h3>برای مراقبت مطمئن‌تر همراه شما هستیم</h3><p>برای دریافت خدمات مراقبتی مایکا با ما در ارتباط باشید.</p><a href="tel:41124">تماس با ۴۱۱۲۴</a></div>
    </div><aside class="article-aside" aria-label="مقالات مرتبط"><h2>مطالب مرتبط</h2><?php if($relatedItems): ?><?php foreach($relatedItems as $r): ?><a class="aside-story" href="/stories/<?= $h($r['slug']) ?>/"><?= $h($r['title']) ?></a><?php endforeach; ?><?php else: ?><p>مطالب بیشتری در این موضوع منتشر خواهد شد.</p><?php endif; ?><div class="aside-service"><strong>به مراقبت نیاز دارید؟</strong><p>برای بررسی خدمات مایکا و راهنمایی بیشتر با ما تماس بگیرید.</p><a href="tel:41124">تماس با ۴۱۱۲۴</a></div></aside></div>
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
