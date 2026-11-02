<?php
declare(strict_types=1);
// Initial MYCA editorial collection: source-backed content with separate first-party cover assets.
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/layout.php';

function cms_editorial_pack(): array {
  $pack=require __DIR__.'/editorial-pack.php';
  if(!is_array($pack)||count($pack)!==10) throw new RuntimeException('INVALID_EDITORIAL_PACK');
  $ids=[];
  foreach($pack as $a){
    if(!isset($a['slug'],$a['title'],$a['sections'],$a['cover_url'])||isset($ids[$a['slug']])) throw new RuntimeException('INVALID_EDITORIAL_PACK');
    $ids[$a['slug']]=true;
    if(!array_key_exists($a['category_slug'],cms_categories())) throw new RuntimeException('INVALID_EDITORIAL_PACK');
    if(!preg_match('~^/media/editorial/cover-[a-z0-9-]+\\.webp$~',(string)$a['cover_url'])) throw new RuntimeException('INVALID_EDITORIAL_PACK');
  }
  return $pack;
}
function cms_editorial_html(array $article): string {
  $h=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
  $html='<p>'.$h($article['intro']).'</p>';
  foreach($article['sections'] as $section) {
    if(!is_array($section)||count($section)<2) throw new RuntimeException('INVALID_EDITORIAL_PACK');
    $html.='<h2>'.$h($section[0]).'</h2>';
    if(isset($section[1])&&is_string($section[1]))$html.='<p>'.$h($section[1]).'</p>';
    if(isset($section[2])) {
      if(is_array($section[2])){
        $html.='<ul>';
        foreach($section[2] as $li)$html.='<li>'.$h($li).'</li>';
        $html.='</ul>';
      } else $html.='<p>'.$h($section[2]).'</p>';
    }
    if(isset($section[3])) {
      if(is_array($section[3])){
        $html.='<ul>';
        foreach($section[3] as $li)$html.='<li>'.$h($li).'</li>';
        $html.='</ul>';
      } else $html.='<p>'.$h($section[3]).'</p>';
    }
  }
  $html.='<h2>مطالب مرتبط در مجله مایکا</h2><ul>';
  foreach($article['related']??[] as $slug) {
    $html.='<li><a href="/stories/'.$h($slug).'/">مطالعه راهنمای مرتبط</a></li>';
  }
  $html.='</ul><h2>منابع و مطالعه بیشتر</h2><ul>';
  foreach($article['sources']??[] as $src) {
    $html.='<li><a href="'.$h($src['url']).'">'.$h($src['name']).'</a></li>';
  }
  $html.='</ul><blockquote><p>این مطلب برای آگاهی عمومی است و جایگزین معاینه، تشخیص یا توصیه فرد دارای صلاحیت حرفه‌ای نیست. در شرایط اضطراری با خدمات امدادی محل تماس بگیرید.</p></blockquote>';
  return cms_html($html);
}
function cms_editorial_pack_install(PDO $db,int $uid,bool $replaceLayout): array {
  $pack=cms_editorial_pack();
  $db->beginTransaction();
  $created=[];$existing=[];$articleIds=[];
  try{
    foreach($pack as $article){
      $q=$db->prepare('SELECT id,status FROM cms_articles WHERE slug=? FOR UPDATE');
      $q->execute([$article['slug']]);$old=$q->fetch();
      if($old){
        if($old['status']!=='published')throw new RuntimeException('PACK_SLUG_CONFLICT:'.$article['slug']);
        $articleIds[]=(int)$old['id'];$existing[]=$article['slug'];
        continue; // Never overwrite a hand-edited or previously published article.
      }
      $body=cms_editorial_html($article);
      if(trim(strip_tags($body))==='')throw new RuntimeException('EMPTY_EDITORIAL_ARTICLE');
      $q=$db->prepare("INSERT INTO cms_articles
        (slug,title,excerpt,body_html,status,category_slug,cover_url,cover_alt,
         author_id,meta_title,meta_description,canonical_url,robots,focus_keyword,
         faq_json,featured,published_at)
         VALUES(?,?,?,?,'published',?,?,?,?,?,?,?,'','index,follow',?,?,?,UTC_TIMESTAMP())");
      $q->execute([
        $article['slug'],$article['title'],$article['excerpt'],$body,$article['category_slug'],
        $article['cover_url'],$article['cover_alt'],$uid,$article['meta_title'],$article['meta_description'],
        $article['focus_keyword'],json_encode($article['faq_json'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),
        ($article['position']<=4?1:0)
      ]);
      $articleIds[]=(int)$db->lastInsertId();$created[]=$article['slug'];
    }
    if(count($articleIds)!==10)throw new RuntimeException('INCOMPLETE_EDITORIAL_PACK');
    $newLayoutVersion=null;$publishedVersion=null;
    if($replaceLayout){
      $layout=cms_layout_default();
      foreach($layout['sections'] as &$section){
        if($section['id']==='hero'){$section['mode']='manual';$section['limit']=4;$section['article_ids']=array_slice($articleIds,0,4);}
        elseif($section['id']==='editors'){$section['mode']='manual';$section['limit']=3;$section['article_ids']=array_slice($articleIds,4,3);}
        elseif($section['id']==='latest'){$section['mode']='manual';$section['limit']=3;$section['article_ids']=array_slice($articleIds,7,3);}
      }
      unset($section);
      $layout=cms_layout_normalize($layout);
      $row=cms_layout_record($db,true);
      $newLayoutVersion=(int)($row['version']??0)+1;
      $publishedVersion=(int)($row['published_version']??0)+1;
      $json=cms_layout_json($layout);
      if($row){
        $q=$db->prepare('UPDATE cms_editorial_layout SET draft_json=?,published_json=?,version=?,published_version=?,published_at=UTC_TIMESTAMP(),updated_by=? WHERE id=1');
        $q->execute([$json,$json,$newLayoutVersion,$publishedVersion,$uid]);
      }else{
        $q=$db->prepare('INSERT INTO cms_editorial_layout(id,draft_json,published_json,version,published_version,updated_by,published_at) VALUES(1,?,?,?,?,?,UTC_TIMESTAMP())');
        $q->execute([$json,$json,$newLayoutVersion,$publishedVersion,$uid]);
      }
      $q=$db->prepare('INSERT INTO cms_editorial_layout_history(version,layout_json,published_by) VALUES(?,?,?)');
      $q->execute([$publishedVersion,$json,$uid]);
    }
    $db->commit();
  }catch(Throwable $e){
    if($db->inTransaction())$db->rollBack();
    throw $e;
  }
  cms_log($uid,'editorial_pack_install','article',0);
  if($replaceLayout)cms_log($uid,'publish','layout',1);
  return ['created'=>count($created),'already_present'=>count($existing),
    'article_ids'=>$articleIds,'layout_published'=>$replaceLayout,'layout_version'=>$newLayoutVersion,
    'published_version'=>$publishedVersion];
}
