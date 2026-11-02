<?php
declare(strict_types=1);
ob_start(); // Keep response headers writable while exercising HTML renderers in CLI.
// Executed only in GitHub Actions against an ephemeral MySQL container.
// Tests actual PDO persistence and HTML sanitization, not mocked data.
require __DIR__.'/../static/api/bootstrap.php';
$db=cms_db();
$statements=preg_split('/;\s*(?:\n|$)/',file_get_contents(__DIR__.'/../server/schema.sql'));
foreach ($statements as $sql) {
  $sql=trim($sql);
  if ($sql!=='' && !preg_match('/^\s*--.*$/sD',$sql)) $db->exec($sql);
  else if ($sql!=='' && str_contains($sql,'CREATE TABLE')) $db->exec($sql);
}
// NOTE: first statement may start with SQL comments; run any statement with CREATE TABLE.
foreach ($db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN) as $table) {
  echo "Table: ".$table."\n";
}
$expected=['cms_users','cms_articles','cms_revisions','cms_media','cms_login_attempts','cms_audit_log','cms_editorial_layout','cms_editorial_layout_history'];
$tables=$db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach($expected as $t)if(!in_array($t,$tables,true))throw new RuntimeException('Missing table '.$t);

$hash=password_hash('test-password-with-strong-length',PASSWORD_DEFAULT);
$db->prepare("INSERT INTO cms_users(email,display_name,password_hash,role) VALUES(?,?,?,'admin')")
  ->execute(['owner@myca.invalid','تست مدیر',$hash]);
$id=(int)$db->lastInsertId();
if(!password_verify('test-password-with-strong-length',$hash))throw new RuntimeException('Hash verification failed');

$dirty='<h2>عنوان تست</h2><p onclick="alert(1)">مراقبت از سالمند <strong>در منزل</strong></p><script>alert(2)</script><a href="javascript:alert(3)">لینک مخرب</a><img src="/media/uploads/test.jpg" onerror="alert(4)" alt="سالمند">';
$clean=cms_html($dirty);
foreach(['<script','onclick=','javascript:','onerror='] as $needle)if(str_contains($clean,$needle))throw new RuntimeException('Unsanitized HTML: '.$needle);
if(!str_contains($clean,'در منزل')||!str_contains($clean,'<h2>'))throw new RuntimeException('Lost semantic editor content');

$db->prepare("INSERT INTO cms_articles(slug,title,excerpt,body_html,status,category_slug,author_id,meta_title,robots,published_at) VALUES(?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP())")
  ->execute(['elderly-home-test','عنوان آزمایشی','خلاصه آزمایشی',$clean,'published','elder-care',$id,'متای تست','index,follow']);
$aid=(int)$db->lastInsertId();
$q=$db->prepare("SELECT title,body_html FROM cms_articles WHERE slug=? AND status='published'");
$q->execute(['elderly-home-test']);$article=$q->fetch();
if(!$article||$article['title']!=='عنوان آزمایشی')throw new RuntimeException('PDO article persistence failed');

$db->prepare('INSERT INTO cms_revisions(article_id,editor_id,snapshot) VALUES(?,?,?)')
  ->execute([$aid,$id,json_encode($article,JSON_UNESCAPED_UNICODE)]);
$db->prepare("UPDATE cms_articles SET title='نسخه ویرایش‌شده' WHERE id=?")->execute([$aid]);
$q=$db->prepare('SELECT snapshot FROM cms_revisions WHERE article_id=? ORDER BY id DESC LIMIT 1');
$q->execute([$aid]);$before=json_decode($q->fetchColumn(),true);
if(($before['title']??'')!=='عنوان آزمایشی')throw new RuntimeException('Revisions failed');

echo "PASS: MySQL schema, auth hash, real article CRUD, revision persistence, safe HTML.\n";


$_GET['slug']='elderly-home-test';
ob_start();
include __DIR__.'/../static/api/story.php';
$html=ob_get_clean();
if (!str_contains($html,'نسخه ویرایش‌شده') || !str_contains($html,'<h1>') || !str_contains($html,'application/ld+json') || !str_contains($html,'canonical')) {
  throw new RuntimeException('SEO SSR article HTML failed: '.substr($html,0,400));
}
ob_start();
include __DIR__.'/../static/api/sitemap.php';
$xml=ob_get_clean();
if (!str_contains($xml,'elderly-home-test') || !str_contains($xml,'<urlset')) {
  throw new RuntimeException('Dynamic sitemap is missing published article');
}
echo "PASS: server-rendered HTML metadata + dynamic sitemap from actual database.\n";


// Editorial Layout Studio smoke test: published ordering, durable drafts and SSR.
require_once __DIR__.'/../static/api/layout.php';
$default=cms_layout_default();
if(count($default['sections'])!==5)throw new RuntimeException('Missing default layout sections');
$invalid=$default;
$invalid['sections'][1]['id']='hero';
try{cms_layout_normalize($invalid);throw new RuntimeException('Duplicate section was accepted');}
catch(InvalidArgumentException $e){}
$db->prepare("INSERT INTO cms_articles(slug,title,excerpt,body_html,status,category_slug,author_id,published_at) VALUES(?,?,?,?, 'published','elder-care',?,UTC_TIMESTAMP())")
  ->execute(['another-elder-guide','مقاله دوم','خلاصه دوم','<p>آزمایش</p>',$id]);
$secondId=(int)$db->lastInsertId();
$layout=$default;
$layout['sections'][0]['mode']='manual';
$layout['sections'][0]['article_ids']=[$secondId,$aid];
$layout=cms_layout_normalize($layout);
$db->prepare('INSERT INTO cms_editorial_layout(id,draft_json,published_json,version,published_version,updated_by,published_at) VALUES(1,?,?,?,?,?,UTC_TIMESTAMP())')
  ->execute([cms_layout_json($layout),cms_layout_json($layout),1,1,$id]);
$db->prepare('INSERT INTO cms_editorial_layout_history(version,layout_json,published_by) VALUES(?,?,?)')
  ->execute([1,cms_layout_json($layout),$id]);
$published=cms_layout_public($db);
$resolved=cms_layout_resolve($db,$published);
if(($resolved['sections'][0]['articles'][0]['slug']??'')!=='another-elder-guide')
  throw new RuntimeException('Manual article order was not preserved');
if(($resolved['sections'][0]['articles'][1]['slug']??'')!=='elderly-home-test')
  throw new RuntimeException('Second pinned article was not preserved');
ob_start();
include __DIR__.'/../static/api/homepage.php';
$home=ob_get_clean();
if(!str_contains($home,'another-elder-guide')||!str_contains($home,'elderly-home-test')||!str_contains($home,'<h1>'))
  throw new RuntimeException('CMS homepage SSR did not render the published configuration');
if(strpos($home,'another-elder-guide')>strpos($home,'elderly-home-test'))
  throw new RuntimeException('CMS homepage SSR changed the manual order');
echo "PASS: editable layout, validation, version history, published sorting and HTML homepage.\\n";


// Ten source-backed MYCA articles are real editable MySQL records.
require_once __DIR__.'/../static/api/editorial-import.php';
$pack=cms_editorial_pack();
if(count($pack)!==10)throw new RuntimeException('Editorial pack is incomplete');
foreach($pack as $p){
  if(count($p['sections'])<4 || count($p['faq_json'])<2 || count($p['sources'])<1)
    throw new RuntimeException('Editorial pack article has incomplete structure');
  if(strlen($p['meta_title'])>250 || strlen($p['meta_description'])>320)
    throw new RuntimeException('SEO field exceeds CMS schema');
}
$first=cms_editorial_pack_install($db,$id,true);
if($first['created']!==10 || $first['layout_published']!==true)
  throw new RuntimeException('Editorial pack install did not insert/publish ten articles');
$again=cms_editorial_pack_install($db,$id,false);
if($again['created']!==0 || $again['already_present']!==10)
  throw new RuntimeException('Editorial pack import is not idempotent');
$publishedArticles=$db->query("SELECT slug,title,body_html,robots,cover_url,faq_json FROM cms_articles WHERE slug='choose-elderly-caregiver-at-home'")->fetch();
if(!$publishedArticles || !str_contains($publishedArticles['body_html'],'<h2>') ||
   !str_contains($publishedArticles['body_html'],'https://www.nia.nih.gov/') ||
   $publishedArticles['robots']!=='index,follow' ||
   !str_starts_with($publishedArticles['cover_url'],'/media/editorial/'))
  throw new RuntimeException('Full SEO article content did not persist');
$editorialLayout=cms_layout_resolve($db,cms_layout_public($db));
if(count($editorialLayout['sections'][0]['articles'])!==4 ||
   count($editorialLayout['sections'][2]['articles'])!==3 ||
   count($editorialLayout['sections'][3]['articles'])!==3)
   throw new RuntimeException('Editorial 4+3+3 homepage layout failed');
ob_start();
include __DIR__.'/../static/api/homepage.php';
$realHomepage=ob_get_clean();
if(!str_contains($realHomepage,'choose-elderly-caregiver-at-home'))
  throw new RuntimeException('Real homepage did not switch to CMS articles');
echo "PASS: ten researched articles imported, preserved, SEO metadata, editability and homepage publication.\\n";


// The public root is always the CMS. Never ship or serve the sample showcase.
$apache=file_get_contents(__DIR__.'/../static/.htaccess');
if(!str_contains($apache,'DirectoryIndex index.php index.html')||
   !str_contains($apache,'RewriteRule ^$ api/homepage.php')||
   !str_contains($apache,'RewriteRule ^search/?$ api/stories.php'))
  throw new RuntimeException('Apache is not enforcing the DB-backed homepage/search');
$renderer=file_get_contents(__DIR__.'/../static/api/homepage.php');
if(str_contains($renderer,'readfile(')||str_contains($renderer,"../index.html"))
  throw new RuntimeException('Old static homepage can still reappear during CMS failure');
$fallback=file_get_contents(__DIR__.'/../src/routes/+page.svelte');
if(str_contains($fallback,'$lib/content.js')||str_contains($fallback,'HeroNewsDesk'))
  throw new RuntimeException('Prerendered homepage still embeds old sample cards');
ob_start();
include __DIR__.'/../static/index.php';
$rootHtml=ob_get_clean();
if(!str_contains($rootHtml,'choose-elderly-caregiver-at-home')||
   str_contains($rootHtml,'/article/elderly-care-at-home'))
  throw new RuntimeException('PHP directory index is not serving CMS articles exclusively');
echo "PASS: root PHP index, legacy showcase removal, fail-closed renderer, search routing.\\n";

ob_end_flush();
