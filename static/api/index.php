<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/layout.php';
require_once __DIR__.'/editorial-import.php';
ini_set('display_errors','0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header('X-Frame-Options: DENY');

try {
  $db=cms_db();
  $route=(string)($_GET['route']??'me');
  $method=$_SERVER['REQUEST_METHOD']??'GET';
  if ($route==='health'&&$method==='GET') cms_json(['ok'=>true,'backend'=>'PHP/MySQL']);
  if ($route==='categories'&&$method==='GET') cms_json(['ok'=>true,'categories'=>cms_categories()]);
  if ($route==='published'&&$method==='GET') {
    $q=$db->query("SELECT slug,title,excerpt,category_slug,cover_url,cover_alt,published_at FROM cms_articles WHERE status='published' AND published_at <= UTC_TIMESTAMP() ORDER BY published_at DESC LIMIT 24");
    cms_json(['ok'=>true,'articles'=>$q->fetchAll()]);
  }

  // First-party collection; only administrators can import or replace the showcase.
  if ($route==='editorial-pack-preview'&&$method==='GET') {
    cms_user('admin');
    $p=cms_editorial_pack();
    $q=$db->query("SELECT slug,status FROM cms_articles ORDER BY id DESC LIMIT 1000");
    $current=[];foreach($q->fetchAll() as $row)$current[$row['slug']]=$row['status'];
    cms_json(['ok'=>true,'articles'=>array_map(static fn($a)=>[
      'title'=>$a['title'],'slug'=>$a['slug'],'category_slug'=>$a['category_slug'],
      'cover_url'=>$a['cover_url'],'exists'=>array_key_exists($a['slug'],$current),
      'existing_status'=>$current[$a['slug']]??null,
      'sections'=>count($a['sections']),'sources'=>count($a['sources'])
    ],$p)]);
  }
  if ($route==='editorial-pack-install'&&$method==='POST') {
    cms_csrf();$u=cms_user('admin');$data=cms_input();
    if(empty($data['confirm_install'])) cms_error('تأیید نصب بسته محتوایی لازم است.');
    try {
      $result=cms_editorial_pack_install($db,(int)$u['id'],!empty($data['replace_layout']));
    }catch(RuntimeException $e){
      if(str_starts_with($e->getMessage(),'PACK_SLUG_CONFLICT:'))
        cms_error('یکی از نامک‌های بسته در دیتابیس وجود دارد ولی منتشر نشده است: '.substr($e->getMessage(),19),409);
      throw $e;
    }
    cms_json(['ok'=>true,'result'=>$result]);
  }

  // Editorial Layout Studio: drafts and public versions are separate.
  if ($route==='layout-public'&&$method==='GET') {
    $published=cms_layout_public($db);
    cms_json(['ok'=>true,'layout'=>$published?cms_layout_resolve($db,$published):null]);
  }
  if ($route==='layout'&&$method==='GET') {
    cms_user('editor');
    $row=cms_layout_record($db);
    $draft=$row?cms_layout_parse($row['draft_json']):null;
    $q=$db->query("SELECT id,title,status,category_slug,cover_url FROM cms_articles WHERE status='published' AND published_at<=UTC_TIMESTAMP() ORDER BY published_at DESC LIMIT 500");
    cms_json(['ok'=>true,'layout'=>$draft??cms_layout_default(),
      'version'=>(int)($row['version']??0),'published_version'=>(int)($row['published_version']??0),
      'published_at'=>$row['published_at']??null,'articles'=>$q->fetchAll()]);
  }
  if ($route==='layout-history'&&$method==='GET') {
    cms_user('editor');
    $q=$db->query('SELECT h.id,h.version,h.created_at,u.display_name AS publisher FROM cms_editorial_layout_history h JOIN cms_users u ON h.published_by=u.id ORDER BY h.id DESC LIMIT 20');
    cms_json(['ok'=>true,'history'=>$q->fetchAll()]);
  }
  if ($route==='layout-save'&&$method==='POST') {
    cms_csrf();$u=cms_user('editor');$data=cms_input();
    try{$layout=cms_layout_normalize($data['layout']??null);}
    catch(InvalidArgumentException $e){cms_error($e->getMessage());}
    $expected=(int)($data['version']??-1);
    $db->beginTransaction();
    try {
      $row=cms_layout_record($db,true);
      $version=(int)($row['version']??0);
      if($expected!==$version){$db->rollBack();cms_error('چیدمان توسط کاربر دیگری تغییر کرده است. صفحه را دوباره باز کنید.',409);}
      $newVersion=$version+1;
      if($row){
        $q=$db->prepare('UPDATE cms_editorial_layout SET draft_json=?,version=?,updated_by=? WHERE id=1');
        $q->execute([cms_layout_json($layout),$newVersion,(int)$u['id']]);
      } else {
        $q=$db->prepare('INSERT INTO cms_editorial_layout(id,draft_json,version,updated_by) VALUES(1,?,?,?)');
        $q->execute([cms_layout_json($layout),$newVersion,(int)$u['id']]);
      }
      $db->commit();
    } catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    cms_log((int)$u['id'],'save','layout',1);
    cms_json(['ok'=>true,'version'=>$newVersion]);
  }
  if ($route==='layout-publish'&&$method==='POST') {
    cms_csrf();$u=cms_user('editor');$data=cms_input();
    $expected=(int)($data['version']??-1);
    $db->beginTransaction();
    try {
      $row=cms_layout_record($db,true);
      if(!$row){$db->rollBack();cms_error('ابتدا پیش‌نویس چیدمان را ذخیره کنید.');}
      if($expected!==(int)$row['version']){$db->rollBack();cms_error('چیدمان تغییر کرده؛ پیش از انتشار آن را بازخوانی کنید.',409);}
      $layout=cms_layout_parse($row['draft_json']);
      if(!$layout){$db->rollBack();cms_error('پیش‌نویس چیدمان معتبر نیست.');}
      // Never publish a pinned card pointing to a draft, archive or missing article.
      $all=[];
      foreach($layout['sections'] as $sec)if($sec['enabled']&&in_array($sec['id'],['hero','editors','latest'],true)){
        foreach($sec['article_ids'] as $id)$all[$id]=true;
      }
      if($all){
        $ids=array_keys($all);
        $q=$db->prepare("SELECT id FROM cms_articles WHERE status='published' AND published_at<=UTC_TIMESTAMP() AND id IN (".implode(',',array_fill(0,count($ids),'?')).")");
        $q->execute($ids);
        if(count($q->fetchAll())!==count($ids)){$db->rollBack();cms_error('یکی از مقالات انتخاب‌شده منتشر نشده یا دیگر در دسترس نیست.',409);}
      }
      $resolved=cms_layout_resolve($db,$layout);
      foreach($resolved['sections'] as $sec)if($sec['id']==='hero'&&$sec['enabled']&&count($sec['articles'])===0){
        $db->rollBack();cms_error('برای انتشار ویترین حداقل یک مقاله منتشرشده در بخش شاخص لازم است.',409);
      }
      $newPublished=(int)$row['published_version']+1;
      $serialized=cms_layout_json($layout);
      $q=$db->prepare('UPDATE cms_editorial_layout SET published_json=?,published_version=?,published_at=UTC_TIMESTAMP(),updated_by=? WHERE id=1');
      $q->execute([$serialized,$newPublished,(int)$u['id']]);
      $q=$db->prepare('INSERT INTO cms_editorial_layout_history(version,layout_json,published_by) VALUES(?,?,?)');
      $q->execute([$newPublished,$serialized,(int)$u['id']]);
      $db->commit();
    } catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    cms_log((int)$u['id'],'publish','layout',1);
    cms_json(['ok'=>true,'published_version'=>$newPublished]);
  }
  if ($route==='layout-restore'&&$method==='POST') {
    cms_csrf();$u=cms_user('editor');$data=cms_input();
    $historyId=(int)($data['history_id']??0);$expected=(int)($data['version']??-1);
    $q=$db->prepare('SELECT layout_json FROM cms_editorial_layout_history WHERE id=?');
    $q->execute([$historyId]);$saved=$q->fetchColumn();
    if(!$saved)cms_error('نسخه منتشرشده پیدا نشد.',404);
    $layout=cms_layout_parse((string)$saved);
    if(!$layout)cms_error('ساختار نسخه قبلی قابل بازیابی نیست.');
    $db->beginTransaction();
    try {
      $row=cms_layout_record($db,true);
      if(!$row||$expected!==(int)$row['version']){$db->rollBack();cms_error('چیدمان تغییر کرده است. ابتدا تازه‌سازی کنید.',409);}
      $version=(int)$row['version']+1;
      $q=$db->prepare('UPDATE cms_editorial_layout SET draft_json=?,version=?,updated_by=? WHERE id=1');
      $q->execute([cms_layout_json($layout),$version,(int)$u['id']]);
      $db->commit();
    } catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    cms_log((int)$u['id'],'restore','layout',1);
    cms_json(['ok'=>true,'version'=>$version,'layout'=>$layout]);
  }

  if ($route==='me'&&$method==='GET') {
    cms_session();
    $u=null;
    if (!empty($_SESSION['user_id'])) {
      $q=$db->prepare('SELECT id,email,display_name,role FROM cms_users WHERE id=? AND active=1');
      $q->execute([(int)$_SESSION['user_id']]);$u=$q->fetch()?:null;
    }
    $initialized=(bool)$db->query('SELECT id FROM cms_users LIMIT 1')->fetchColumn();
    cms_json(['ok'=>true,'authenticated'=>(bool)$u,'user'=>$u,'csrf'=>$_SESSION['csrf'],'initialized'=>$initialized]);
  }
  if ($route==='setup'&&$method==='POST') {
    cms_csrf();$data=cms_input();
    if ($db->query('SELECT COUNT(*) FROM cms_users')->fetchColumn()>0) cms_error('راه‌اندازی قبلاً انجام شده.',403);
    $cfg=cms_config();
    $token=(string)($data['setup_token']??'');
    if (strlen($token)<32 || !hash_equals((string)($cfg['setup_token']??''),$token)
       || str_contains((string)$cfg['setup_token'],'REPLACE')) cms_error('کلید راه‌اندازی معتبر نیست.',403);
    $email=filter_var((string)($data['email']??''),FILTER_VALIDATE_EMAIL);
    $name=cms_clean_text($data['display_name']??'',120);
    $password=(string)($data['password']??'');
    if (!$email || !$name || strlen($password)<14) cms_error('ایمیل، نام و رمز حداقل ۱۴ کاراکتری لازم است.');
    $q=$db->prepare("INSERT INTO cms_users(email,display_name,password_hash,role) VALUES(?,?,?,'admin')");
    $q->execute([$email,$name,password_hash($password,PASSWORD_DEFAULT)]);
    cms_json(['ok'=>true,'message'=>'مدیر اولیه ایجاد شد. کلید setup را از فایل تنظیمات حذف یا تغییر بده.']);
  }
  if ($route==='login'&&$method==='POST') {
    cms_csrf();$data=cms_input();cms_session();
    $email=strtolower(trim((string)($data['email']??'')));
    $password=(string)($data['password']??'');
    $cfg=cms_config();
    $ipHash=hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown'),$cfg['session_secret']);
    $q=$db->prepare('SELECT COUNT(*) FROM cms_login_attempts WHERE ip_hash=? AND created_at>UTC_TIMESTAMP()-INTERVAL 15 MINUTE');
    $q->execute([$ipHash]);
    if ((int)$q->fetchColumn()>=10) cms_error('تلاش‌های ناموفق زیاد است؛ بعداً دوباره امتحان کنید.',429);
    $q=$db->prepare('SELECT * FROM cms_users WHERE email=? AND active=1');$q->execute([$email]);$u=$q->fetch();
    if (!$u || !password_verify($password,$u['password_hash'])) {
      $q=$db->prepare('INSERT INTO cms_login_attempts(ip_hash) VALUES(?)');$q->execute([$ipHash]);
      cms_error('ایمیل یا رمز عبور نادرست است.',401);
    }
    session_regenerate_id(true);
    $_SESSION['user_id']=(int)$u['id'];$_SESSION['csrf']=bin2hex(random_bytes(32));
    $db->prepare('UPDATE cms_users SET last_login_at=UTC_TIMESTAMP() WHERE id=?')->execute([$u['id']]);
    $db->prepare('DELETE FROM cms_login_attempts WHERE ip_hash=?')->execute([$ipHash]);
    cms_json(['ok'=>true,'user'=>['id'=>$u['id'],'email'=>$u['email'],'display_name'=>$u['display_name'],'role'=>$u['role']],'csrf'=>$_SESSION['csrf']]);
  }
  if ($route==='logout'&&$method==='POST') {
    cms_csrf();cms_session();
    $_SESSION=[];session_regenerate_id(true);
    cms_json(['ok'=>true]);
  }
  if ($route==='metrics'&&$method==='GET') {
    cms_user();
    $counts=$db->query('SELECT status,COUNT(*) AS count FROM cms_articles GROUP BY status')->fetchAll();
    $recent=$db->query('SELECT id,slug,title,status,updated_at FROM cms_articles ORDER BY updated_at DESC LIMIT 6')->fetchAll();
    cms_json(['ok'=>true,'counts'=>$counts,'recent'=>$recent]);
  }
  if ($route==='users'&&$method==='GET') {
    cms_user('admin');
    $users=$db->query('SELECT id,email,display_name,role,active,created_at,last_login_at FROM cms_users ORDER BY id ASC LIMIT 100')->fetchAll();
    cms_json(['ok'=>true,'users'=>$users]);
  }
  if ($route==='user-create'&&$method==='POST') {
    cms_csrf();$admin=cms_user('admin');$data=cms_input();
    $email=filter_var(strtolower(trim((string)($data['email']??''))),FILTER_VALIDATE_EMAIL);
    $name=cms_clean_text($data['display_name']??'',120);
    $password=(string)($data['password']??'');
    $role=cms_clean_text($data['role']??'author',20);
    if (!$email||!$name||strlen($password)<14||!in_array($role,['admin','editor','author'],true)) cms_error('نام، ایمیل، نقش معتبر و رمز حداقل ۱۴ کاراکتری لازم است.');
    try {
      $q=$db->prepare('INSERT INTO cms_users(email,display_name,password_hash,role) VALUES(?,?,?,?)');
      $q->execute([$email,$name,password_hash($password,PASSWORD_DEFAULT),$role]);
    } catch(PDOException $e) {
      if ($e->getCode()==='23000') cms_error('این ایمیل قبلاً ثبت شده است.',409);
      throw $e;
    }
    $id=(int)$db->lastInsertId();
    cms_log((int)$admin['id'],'create','user',$id);
    cms_json(['ok'=>true,'id'=>$id]);
  }
  if ($route==='user-update'&&$method==='POST') {
    cms_csrf();$admin=cms_user('admin');$data=cms_input();
    $id=(int)($data['id']??0);$role=cms_clean_text($data['role']??'',20);
    $active=!empty($data['active'])?1:0;
    if($id<1||!in_array($role,['admin','editor','author'],true))cms_error('شناسه یا نقش نامعتبر است.');
    if($id===(int)$admin['id'] && ($role!=='admin'||$active!==1))cms_error('امکان غیرفعال‌سازی یا تنزل نقش حساب مدیر خودت وجود ندارد.',403);
    $db->beginTransaction();
    try {
      $q=$db->prepare('SELECT role,active FROM cms_users WHERE id=? FOR UPDATE');$q->execute([$id]);$before=$q->fetch();
      if(!$before){$db->rollBack();cms_error('کاربر پیدا نشد.',404);}
      if($before['role']==='admin' && (int)$before['active']===1 && ($role!=='admin'||$active!==1)){
        $n=(int)$db->query("SELECT COUNT(*) FROM cms_users WHERE role='admin' AND active=1")->fetchColumn();
        if($n<2){$db->rollBack();cms_error('آخرین مدیر فعال را نمی‌توان غیرفعال کرد.',403);}
      }
      $q=$db->prepare('UPDATE cms_users SET role=?,active=? WHERE id=?');
      $q->execute([$role,$active,$id]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    cms_log((int)$admin['id'],'update','user',$id);
    cms_json(['ok'=>true,'id'=>$id]);
  }
  if ($route==='articles'&&$method==='GET') {
    $u=cms_user();
    $search=cms_clean_text($_GET['q']??'',90);
    $status=cms_clean_text($_GET['status']??'',16);
    $sql='SELECT id,slug,title,excerpt,category_slug,status,cover_url,updated_at,published_at,author_id FROM cms_articles WHERE 1=1';
    $args=[];
    if ($u['role']==='author') {$sql.=' AND author_id=?';$args[]=(int)$u['id'];}
    if ($status&&in_array($status,['draft','review','published','archived'],true)) {$sql.=' AND status=?';$args[]=$status;}
    if ($search) {$sql.=' AND (title LIKE ? OR slug LIKE ?)';$args[]='%'.$search.'%';$args[]='%'.$search.'%';}
    $sql.=' ORDER BY updated_at DESC LIMIT 150';
    $q=$db->prepare($sql);$q->execute($args);
    cms_json(['ok'=>true,'articles'=>$q->fetchAll()]);
  }
  if ($route==='article'&&$method==='GET') {
    $u=cms_user();$id=(int)($_GET['id']??0);
    $q=$db->prepare('SELECT * FROM cms_articles WHERE id=?');$q->execute([$id]);$a=$q->fetch();
    if (!$a || ($u['role']==='author'&&(int)$a['author_id']!==(int)$u['id'])) cms_error('مقاله پیدا نشد.',404);
    $a['faq_json']=$a['faq_json']?json_decode($a['faq_json'],true):[];
    cms_json(['ok'=>true,'article'=>$a]);
  }
  if ($route==='save'&&$method==='POST') {
    cms_csrf();$u=cms_user();$data=cms_input();
    $id=(int)($data['id']??0);
    $before=null;
    if ($id>0) {
      $q=$db->prepare('SELECT * FROM cms_articles WHERE id=? FOR UPDATE');
      $db->beginTransaction();$q->execute([$id]);$before=$q->fetch();
      if (!$before || ($u['role']==='author'&&(int)$before['author_id']!==(int)$u['id'])) {
        $db->rollBack();cms_error('مقاله پیدا نشد.',404);
      }
    }
    $title=cms_clean_text($data['title']??'',250);
    $slug=cms_slug(cms_clean_text($data['slug']??'',160));
    $excerpt=cms_clean_text($data['excerpt']??'',1500);
    $body=cms_html((string)($data['body_html']??''));
    $cat=cms_clean_text($data['category_slug']??'elder-care',80);
    $status=cms_clean_text($data['status']??'draft',16);
    if (!$title||!$excerpt||!array_key_exists($cat,cms_categories())||!in_array($status,['draft','review','published','archived'],true))
      cms_error('عنوان، توضیح، دسته‌بندی یا وضعیت مقاله نامعتبر است.');
    if (in_array($status,['published','archived'],true)&&$u['role']==='author') cms_error('انتشار فقط با دسترسی سردبیر ممکن است.',403);
    if ($status==='published' && trim(strip_tags($body))==='') cms_error('محتوای مقاله برای انتشار خالی است.');
    $cover=cms_safe_url(cms_clean_text($data['cover_url']??'',700));
    $alt=cms_clean_text($data['cover_alt']??'',255);
    $metaTitle=cms_clean_text($data['meta_title']??'',250);
    $metaDesc=cms_clean_text($data['meta_description']??'',320);
    $canonical=cms_safe_url(cms_clean_text($data['canonical_url']??'',700),false);
    $robots=cms_clean_text($data['robots']??'index,follow',35);
    if (!in_array($robots,['index,follow','noindex,follow','noindex,nofollow'],true)) $robots='index,follow';
    $keyword=cms_clean_text($data['focus_keyword']??'',190);
    $featured=!empty($data['featured'])?1:0;
    $faqs=$data['faq_json']??[];
    if (!is_array($faqs)||count($faqs)>20) cms_error('سؤالات متداول نامعتبرند.');
    $faqItems=[];
    foreach($faqs as $f) {
      if (!is_array($f)) continue;
      $question=cms_clean_text($f['question']??'',250);
      $answer=cms_clean_text($f['answer']??'',1600);
      if ($question&&$answer) $faqItems[]=['question'=>$question,'answer'=>$answer];
    }
    $values=[$slug,$title,$excerpt,$body,$status,$cat,$cover,$alt,$metaTitle,$metaDesc,$canonical,$robots,$keyword,json_encode($faqItems,JSON_UNESCAPED_UNICODE),$featured];
    try {
      if ($id>0) {
        $q=$db->prepare('INSERT INTO cms_revisions(article_id,editor_id,snapshot) VALUES(?,?,?)');
        $q->execute([$id,(int)$u['id'],json_encode($before,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE)]);
        $sql="UPDATE cms_articles SET slug=?,title=?,excerpt=?,body_html=?,status=?,category_slug=?,cover_url=?,cover_alt=?,meta_title=?,meta_description=?,canonical_url=?,robots=?,focus_keyword=?,faq_json=?,featured=?,published_at=CASE WHEN ?='published' THEN COALESCE(published_at,UTC_TIMESTAMP()) ELSE published_at END WHERE id=?";
        $q=$db->prepare($sql);$q->execute([...$values,$status,$id]);
        $db->commit();
      } else {
        $sql="INSERT INTO cms_articles(slug,title,excerpt,body_html,status,category_slug,cover_url,cover_alt,meta_title,meta_description,canonical_url,robots,focus_keyword,faq_json,featured,author_id,published_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,CASE WHEN ?='published' THEN UTC_TIMESTAMP() ELSE NULL END)";
        $q=$db->prepare($sql);$q->execute([...$values,(int)$u['id'],$status]);
        $id=(int)$db->lastInsertId();
      }
    } catch(PDOException $e) {
      if ($db->inTransaction()) $db->rollBack();
      if ($e->getCode()==='23000') cms_error('این اسلاگ قبلاً استفاده شده است.',409);
      throw $e;
    }
    cms_log((int)$u['id'],$status==='published'?'publish':'save','article',$id);
    cms_json(['ok'=>true,'id'=>$id,'slug'=>$slug,'status'=>$status,'public_url'=>'/stories/'.$slug.'/']);
  }
  if ($route==='revisions'&&$method==='GET') {
    $u=cms_user();$id=(int)($_GET['id']??0);
    $q=$db->prepare('SELECT author_id FROM cms_articles WHERE id=?');$q->execute([$id]);$owner=$q->fetchColumn();
    if (!$owner||($u['role']==='author'&&(int)$owner!==(int)$u['id']))cms_error('دسترسی ندارید.',403);
    $q=$db->prepare('SELECT r.id,r.editor_id,r.created_at,u.display_name FROM cms_revisions r JOIN cms_users u ON u.id=r.editor_id WHERE r.article_id=? ORDER BY r.id DESC LIMIT 30');
    $q->execute([$id]);cms_json(['ok'=>true,'revisions'=>$q->fetchAll()]);
  }
  if ($route==='restore'&&$method==='POST') {
    cms_csrf();$u=cms_user('editor');$data=cms_input();
    $id=(int)($data['id']??0);$revision=(int)($data['revision_id']??0);
    $q=$db->prepare('SELECT snapshot FROM cms_revisions WHERE article_id=? AND id=?');$q->execute([$id,$revision]);
    $snap=$q->fetchColumn();if(!$snap)cms_error('نسخه یافت نشد.',404);
    $old=json_decode($snap,true);
    if(!is_array($old))cms_error('نسخه آسیب دیده است.');
    $q=$db->prepare('SELECT * FROM cms_articles WHERE id=?');$q->execute([$id]);$current=$q->fetch();
    if(!$current)cms_error('مقاله یافت نشد.',404);
    $db->beginTransaction();
    try {
      $q=$db->prepare('INSERT INTO cms_revisions(article_id,editor_id,snapshot) VALUES(?,?,?)');
      $q->execute([$id,(int)$u['id'],json_encode($current,JSON_UNESCAPED_UNICODE)]);
      $keys=['slug','title','excerpt','body_html','category_slug','cover_url','cover_alt','meta_title','meta_description','canonical_url','robots','focus_keyword','faq_json','featured'];
      $sql='UPDATE cms_articles SET '.implode(',',array_map(fn($k)=>$k.'=?',$keys)).",status='draft' WHERE id=?";
      $values=array_map(fn($k)=>$old[$k]??'',$keys);
      $db->prepare($sql)->execute([...$values,$id]);$db->commit();
    } catch(Throwable $e) {$db->rollBack();throw $e;}
    cms_log((int)$u['id'],'restore','article',$id);
    cms_json(['ok'=>true,'id'=>$id,'status'=>'draft']);
  }
  if ($route==='media'&&$method==='GET') {
    cms_user();
    $q=$db->query('SELECT id,path,mime,bytes,alt,created_at FROM cms_media ORDER BY id DESC LIMIT 100');
    cms_json(['ok'=>true,'media'=>$q->fetchAll()]);
  }
  if ($route==='upload'&&$method==='POST') {
    cms_csrf();$u=cms_user();
    $file=$_FILES['file']??null;
    if (!$file||is_array($file['error'])||$file['error']!==UPLOAD_ERR_OK) cms_error('آپلود فایل انجام نشد.');
    if ($file['size']>4*1024*1024||$file['size']<100)cms_error('حجم تصویر باید کمتر از ۴ مگابایت باشد.');
    $info=@getimagesize($file['tmp_name']);
    $mime=$info['mime']??'';
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
    if (!$ext||($info[0]??0)>6000||($info[1]??0)>6000)cms_error('فقط JPEG، PNG و WebP استاندارد مجاز هستند.');
    $dir=dirname(__DIR__).'/media/uploads';
    if (!is_dir($dir)&&!mkdir($dir,0755,true))cms_error('پوشه رسانه قابل ایجاد نیست.',500);
    $name=gmdate('Ymd').'-'.bin2hex(random_bytes(12)).'.'.$ext;
    if(!move_uploaded_file($file['tmp_name'],$dir.'/'.$name))cms_error('ذخیره تصویر ناموفق بود.',500);
    chmod($dir.'/'.$name,0644);
    $url='/media/uploads/'.$name;
    $alt=cms_clean_text($_POST['alt']??'',255);
    $q=$db->prepare('INSERT INTO cms_media(uploader_id,path,mime,bytes,alt) VALUES(?,?,?,?,?)');
    $q->execute([(int)$u['id'],$url,$mime,$file['size'],$alt]);
    cms_log((int)$u['id'],'upload','media',(int)$db->lastInsertId());
    cms_json(['ok'=>true,'path'=>$url,'alt'=>$alt,'id'=>(int)$db->lastInsertId()]);
  }
  cms_error('درخواست پشتیبانی نمی‌شود.',404);
} catch (Throwable $e) {
  error_log('MYCA CMS: '.$e->getMessage());
  if ($e instanceof PDOException && $e->getCode()==='42S02') cms_error('جدول‌های چیدمان نصب نشده‌اند؛ مهاجرت 001_editorial_layout.sql را در phpMyAdmin اجرا کنید.',503);
  if ($e->getMessage()==='CMS_NOT_CONFIGURED') cms_error('بک‌اند هنوز به پایگاه داده متصل نشده است.',503);
  cms_error('خطای داخلی سرور؛ گزارش فنی را بررسی کنید.',500);
}
