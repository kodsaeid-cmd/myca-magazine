<?php
declare(strict_types=1);

const MYCA_CMS_CATEGORIES = [
  'elder-care'=>'مراقبت سالمند', 'child-family'=>'کودک و خانواده',
  'health'=>'سلامت و پیشگیری', 'equipment'=>'تجهیزات مراقبتی',
  'caregiver-skills'=>'آموزش مراقبین', 'home-safety'=>'خانه ایمن'
];

function cms_config(): array {
  $path = dirname(__DIR__, 2) . '/myca-magazine-config.php';
  // CLI tests run in a repository checkout, not at the production cPanel path.
  if (PHP_SAPI === 'cli' && getenv('MYCA_CMS_TEST_CONFIG')) $path = (string)getenv('MYCA_CMS_TEST_CONFIG');
  if (!is_file($path)) {
    throw new RuntimeException('CMS_NOT_CONFIGURED');
  }
  $config = require $path;
  if (!is_array($config) || str_contains((string)($config['db_name'] ?? ''), 'REPLACE')) {
    throw new RuntimeException('CMS_NOT_CONFIGURED');
  }
  return $config;
}
function cms_db(): PDO {
  static $db = null;
  if ($db) return $db;
  $config = cms_config();
  $db = new PDO(
    'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
    $config['db_user'], $config['db_password'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]
  );
  return $db;
}
function cms_session(): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  ini_set('session.use_strict_mode','1');
  ini_set('session.use_only_cookies','1');
  session_name('myca_cms_session');
  session_set_cookie_params([
    // HTTP cookies are permitted only in explicit local Docker development. Production stays HTTPS-only.
    'lifetime'=>0,'path'=>'/','secure'=>(getenv('MYCA_ENV')==='development' && getenv('MYCA_COOKIE_SECURE')==='0') ? false : true,'httponly'=>true,'samesite'=>'Lax'
  ]);
  session_start();
  if (!isset($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32));
}
function cms_json(array $data, int $status=200): never {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, private');
  header('X-Content-Type-Options: nosniff');
  echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
  exit;
}
function cms_error(string $message,int $status=400): never {
  cms_json(['ok'=>false,'error'=>$message],$status);
}
function cms_input(): array {
  $body=file_get_contents('php://input');
  if (strlen($body)>1500000) cms_error('حجم درخواست بیش از حد مجاز است.',413);
  $data=json_decode($body,true);
  if (!is_array($data)) cms_error('ساختار اطلاعات نامعتبر است.');
  return $data;
}
function cms_csrf(): void {
  cms_session();
  $token=(string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
  if (!$token || !hash_equals((string)$_SESSION['csrf'],$token)) cms_error('توکن امنیتی منقضی یا نامعتبر است.',419);
  $origin=(string)($_SERVER['HTTP_ORIGIN'] ?? '');
  if ($origin && !hash_equals(parse_url((string)(cms_config()['site_url']),PHP_URL_HOST)?:'',parse_url($origin,PHP_URL_HOST)?:'')) {
    cms_error('مبدأ درخواست مجاز نیست.',403);
  }
}
function cms_user(string $minimum='author'): array {
  cms_session();
  $id=(int)($_SESSION['user_id'] ?? 0);
  if (!$id) cms_error('برای ادامه وارد پنل شوید.',401);
  $query=cms_db()->prepare('SELECT id,email,display_name,role FROM cms_users WHERE id=? AND active=1');
  $query->execute([$id]);
  $user=$query->fetch();
  if (!$user) { $_SESSION=[]; cms_error('نشست نامعتبر است.',401); }
  $ranks=['author'=>1,'editor'=>2,'admin'=>3];
  if (($ranks[$user['role']]??0)<($ranks[$minimum]??9)) cms_error('دسترسی کافی ندارید.',403);
  return $user;
}
function cms_clean_text(mixed $value, int $max): string {
  return mb_substr(trim((string)($value ?? '')),0,$max,'UTF-8');
}
function cms_slug(string $value): string {
  $value=strtolower(trim($value));
  if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$value) || strlen($value)>160) {
    cms_error('اسلاگ فقط باید شامل حروف انگلیسی کوچک، عدد و خط تیره باشد.');
  }
  return $value;
}
function cms_safe_url(string $url,bool $allowRelative=true): string {
  $url=trim($url);
  if ($url==='') return '';
  if (str_starts_with($url,'https://') && filter_var($url,FILTER_VALIDATE_URL)) return $url;
  if ($allowRelative && str_starts_with($url,'/') && !str_starts_with($url,'//')
      && !str_contains($url,"\0") && !preg_match('/[\r\n<>]/',$url)) return $url;
  return '';
}
function cms_html(string $html): string {
  if (strlen($html)>700000) cms_error('متن مقاله بیش از حد بزرگ است.',413);
  if (trim($html)==='') return '';
  if (!class_exists('DOMDocument')) cms_error('افزونه PHP DOM در سرور فعال نیست.',503);
  $doc=new DOMDocument();
  $previous=libxml_use_internal_errors(true);
  $doc->loadHTML('<!doctype html><html><head><meta charset="utf-8"></head><body><div id="myca-root">'.$html.'</div></body></html>',
    LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
  libxml_clear_errors();libxml_use_internal_errors($previous);
  $root=$doc->getElementById('myca-root');
  if (!$root) return '';
  $allow=array_fill_keys(['p','h2','h3','h4','strong','em','u','s','blockquote','ul','ol','li','a','img','table','thead','tbody','tr','th','td','pre','code','hr','br'],true);
  $walk=function(DOMNode $parent) use (&$walk,$allow,$doc) {
    for ($node=$parent->firstChild;$node;) {
      $next=$node->nextSibling;
      if ($node instanceof DOMComment) {$parent->removeChild($node);}
      elseif ($node instanceof DOMElement) {
        $tag=strtolower($node->tagName);
        if (in_array($tag,['script','style','iframe','object','embed','form','svg','math','video','audio'],true)) {
          $parent->removeChild($node);
        } elseif (!isset($allow[$tag])) {
          $walk($node);
          while($node->firstChild) $parent->insertBefore($node->firstChild,$node);
          $parent->removeChild($node);
        } else {
          $attrs=[];
          foreach($node->attributes as $a) $attrs[$a->name]=$a->value;
          foreach(array_keys($attrs) as $name) $node->removeAttribute($name);
          if ($tag==='a') {
            $url=cms_safe_url((string)($attrs['href']??''));
            if ($url) {$node->setAttribute('href',$url);$node->setAttribute('rel','noopener noreferrer');}
          }
          if ($tag==='img') {
            $src=cms_safe_url((string)($attrs['src']??''));
            if (!$src) { $parent->removeChild($node); $node=$next;continue; }
            $node->setAttribute('src',$src);
            $node->setAttribute('alt',cms_clean_text($attrs['alt']??'',200));
            $node->setAttribute('loading','lazy');
          }
          if (($tag==='th'||$tag==='td')&&isset($attrs['colspan'])&&ctype_digit($attrs['colspan'])&&(int)$attrs['colspan']<=8) $node->setAttribute('colspan',$attrs['colspan']);
          $walk($node);
        }
      }
      $node=$next;
    }
  };
  $walk($root);
  $output='';
  foreach($root->childNodes as $child) $output.=$doc->saveHTML($child);
  return $output;
}
function cms_categories(): array {return MYCA_CMS_CATEGORIES;}
function cms_log(int $uid,string $action,string $entity,int $entityId): void {
  $q=cms_db()->prepare('INSERT INTO cms_audit_log(user_id,action,entity_type,entity_id) VALUES(?,?,?,?)');
  $q->execute([$uid,$action,$entity,$entityId]);
}
