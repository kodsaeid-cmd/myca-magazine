<?php
declare(strict_types=1);
// Editorial Layout Studio: one versioned, ordered homepage configuration.
// No client-submitted HTML, CSS, paths, or arbitrary template names are accepted.
require_once __DIR__.'/bootstrap.php';

function cms_layout_default(): array {
  return ['schema'=>1,'sections'=>[
    ['id'=>'hero','title'=>'مقالات شاخص','enabled'=>true,'mode'=>'auto','limit'=>4,'category_slug'=>'','article_ids'=>[],'category_slugs'=>[]],
    ['id'=>'categories','title'=>'آنچه به آن نیاز دارید','enabled'=>true,'mode'=>'manual','limit'=>6,'category_slug'=>'','article_ids'=>[],'category_slugs'=>array_keys(cms_categories())],
    ['id'=>'editors','title'=>'انتخاب سردبیر','enabled'=>true,'mode'=>'auto','limit'=>3,'category_slug'=>'','article_ids'=>[],'category_slugs'=>[]],
    ['id'=>'latest','title'=>'تازه‌ترین خواندنی‌ها','enabled'=>true,'mode'=>'auto','limit'=>6,'category_slug'=>'','article_ids'=>[],'category_slugs'=>[]],
    ['id'=>'banner','title'=>'برای مراقبت، تنها نیستید','enabled'=>true,'mode'=>'manual','limit'=>1,'category_slug'=>'','article_ids'=>[],'category_slugs'=>[]],
  ]];
}
function cms_layout_normalize(mixed $input): array {
  if (!is_array($input) || !isset($input['sections']) || !is_array($input['sections']) || count($input['sections'])!==5) {
    throw new InvalidArgumentException('ساختار بخش‌های ویترین معتبر نیست.');
  }
  $defaults=array_column(cms_layout_default()['sections'],null,'id');
  $seen=[];$sections=[];
  foreach ($input['sections'] as $source) {
    if (!is_array($source)) throw new InvalidArgumentException('بخش چیدمان نامعتبر است.');
    $id=(string)($source['id']??'');
    if (!isset($defaults[$id])||isset($seen[$id])) throw new InvalidArgumentException('شناسه بخش تکراری یا ناشناخته است.');
    $seen[$id]=true;
    $base=$defaults[$id];
    $ids=$source['article_ids']??[];
    if (!is_array($ids)||count($ids)>24) throw new InvalidArgumentException('فهرست مقالات بیش از حد مجاز است.');
    $ids=array_values(array_unique(array_filter(array_map(static function($v):int {
      return (is_int($v)||is_string($v)&&ctype_digit($v)) ? (int)$v : 0;
    },$ids),fn($v)=>$v>0)));
    $cats=$source['category_slugs']??$base['category_slugs'];
    if(!is_array($cats)||count($cats)>6) throw new InvalidArgumentException('دسته‌بندی‌های انتخابی نامعتبر است.');
    $cats=array_values(array_unique(array_filter($cats,fn($v)=>is_string($v)&&array_key_exists($v,cms_categories()))));
    $mode=(string)($source['mode']??$base['mode']);
    if (!in_array($mode,['manual','auto'],true)) throw new InvalidArgumentException('حالت چیدمان نامعتبر است.');
    $category=(string)($source['category_slug']??'');
    if($category!==''&&!array_key_exists($category,cms_categories())) throw new InvalidArgumentException('دسته‌بندی فیلتر نامعتبر است.');
    $limit=max(1,min($id==='hero'?4:($id==='editors'?3:12),(int)($source['limit']??$base['limit'])));
    $sections[]=[
      'id'=>$id,
      'title'=>cms_clean_text($source['title']??$base['title'],85),
      'enabled'=>(bool)($source['enabled']??true),
      'mode'=>$mode,'limit'=>$limit,'category_slug'=>$category,
      'article_ids'=>$ids,'category_slugs'=>$id==='categories'?$cats:[]
    ];
  }
  return ['schema'=>1,'sections'=>$sections];
}
function cms_layout_json(array $layout): string {
  return json_encode($layout,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
function cms_layout_record(PDO $db,bool $lock=false): ?array {
  $sql='SELECT * FROM cms_editorial_layout WHERE id=1'.($lock?' FOR UPDATE':'');
  $row=$db->query($sql)->fetch();
  return $row?:null;
}
function cms_layout_parse(?string $json): ?array {
  if(!$json) return null;
  $val=json_decode($json,true);
  return is_array($val)?cms_layout_normalize($val):null;
}
function cms_layout_public(PDO $db): ?array {
  $row=cms_layout_record($db);
  return $row?cms_layout_parse($row['published_json']):null;
}
function cms_layout_articles(PDO $db,array $section,array &$seen): array {
  $ids=$section['article_ids'];
  $limit=(int)$section['limit'];
  $selected=[];
  if ($ids) {
    $placeholders=implode(',',array_fill(0,count($ids),'?'));
    $q=$db->prepare("SELECT id,slug,title,excerpt,category_slug,cover_url,cover_alt,published_at FROM cms_articles WHERE status='published' AND published_at<=UTC_TIMESTAMP() AND id IN ($placeholders)");
    $q->execute($ids);
    $byId=[];
    foreach($q->fetchAll() as $a)$byId[(int)$a['id']]=$a;
    foreach($ids as $id)if(isset($byId[$id])&&!isset($seen[$id])){$selected[]=$byId[$id];$seen[$id]=true;}
  }
  $selected=array_slice($selected,0,$limit);
  if($section['mode']==='auto'&&count($selected)<$limit){
    $sql="SELECT id,slug,title,excerpt,category_slug,cover_url,cover_alt,published_at FROM cms_articles WHERE status='published' AND published_at<=UTC_TIMESTAMP()";
    $args=[];
    if($section['category_slug']!==''){$sql.=' AND category_slug=?';$args[]=$section['category_slug'];}
    $sql.=' ORDER BY published_at DESC,id DESC LIMIT 100';
    $q=$db->prepare($sql);$q->execute($args);
    foreach($q->fetchAll() as $a){
      $id=(int)$a['id'];
      if(isset($seen[$id]))continue;
      $selected[]=$a;$seen[$id]=true;
      if(count($selected)>=$limit)break;
    }
  }
  return $selected;
}
function cms_layout_resolve(PDO $db,array $layout): array {
  $used=[];
  foreach($layout['sections'] as &$section) {
    $section['articles']=[];
    if(!$section['enabled'])continue;
    if(in_array($section['id'],['hero','editors','latest'],true)){
      $section['articles']=cms_layout_articles($db,$section,$used);
    }
  }
  unset($section);
  return $layout;
}
