<?php
declare(strict_types=1);
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
$expected=['cms_users','cms_articles','cms_revisions','cms_media','cms_login_attempts','cms_audit_log'];
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
