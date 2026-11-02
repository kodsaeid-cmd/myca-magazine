<?php
declare(strict_types=1);
require_once __DIR__.'/editorial-import.php';

// Second curated set; no existing CMS rows are overwritten.
function cms_editorial_more_pack(): array {
  $extra=array_merge(require __DIR__.'/editorial-pack-2a.php',require __DIR__.'/editorial-pack-2b.php');
  if(count($extra)!==10)throw new RuntimeException('INVALID_EDITORIAL_MORE_PACK');
  $ids=[];
  foreach($extra as $a){
    if(!isset($a['slug'],$a['title'],$a['sections'],$a['sources'])||isset($ids[$a['slug']]))throw new RuntimeException('INVALID_EDITORIAL_MORE_PACK');
    if(!preg_match('~^[a-z0-9]+(?:-[a-z0-9]+)*$~',$a['slug']))throw new RuntimeException('INVALID_EDITORIAL_MORE_PACK');
    if(!array_key_exists($a['category_slug'],cms_categories()))throw new RuntimeException('INVALID_EDITORIAL_MORE_PACK');
    if(!preg_match('~^/media/editorial-more/cover-[0-9]+-[a-z-]+\\.jpg$~',(string)$a['cover_url']))throw new RuntimeException('INVALID_EDITORIAL_MORE_PACK');
    if(count($a['sections'])<5||count($a['faq_json'])<2||count($a['sources'])<1)throw new RuntimeException('INCOMPLETE_EDITORIAL_MORE_PACK');
    $ids[$a['slug']]=true;
  }
  return $extra;
}
function cms_editorial_more_install(PDO $db,int $uid,bool $expandLayout): array {
  $extra=cms_editorial_more_pack();
  $titles=[];
  foreach(array_merge(cms_editorial_pack(),$extra) as $article)$titles[$article['slug']]=$article['title'];
  $created=0;$existing=0;$layoutPublished=false;$newVersion=null;
  $db->beginTransaction();
  try {
    foreach($extra as $a) {
      $q=$db->prepare('SELECT id,status FROM cms_articles WHERE slug=? FOR UPDATE');
      $q->execute([$a['slug']]);$old=$q->fetch();
      if($old){
        if($old['status']!=='published')throw new RuntimeException('PACK_SLUG_CONFLICT:'.$a['slug']);
        $existing++;
        continue;
      }
      $html=cms_editorial_html($a,$titles);
      $q=$db->prepare("INSERT INTO cms_articles
        (slug,title,excerpt,body_html,status,category_slug,cover_url,cover_alt,
         author_id,meta_title,meta_description,canonical_url,robots,focus_keyword,
         faq_json,featured,published_at)
         VALUES(?,?,?,?,'published',?,?,?,?,?,?,'','index,follow',?,?,0,UTC_TIMESTAMP())");
      $q->execute([$a['slug'],$a['title'],$a['excerpt'],$html,$a['category_slug'],
        $a['cover_url'],$a['cover_alt'],$uid,$a['meta_title'],$a['meta_description'],
        $a['focus_keyword'],json_encode($a['faq_json'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
      $created++;
    }
    if($expandLayout) {
      $row=cms_layout_record($db,true);
      if($row) {
        $draft=cms_layout_parse($row['draft_json']);
        $published=cms_layout_parse($row['published_json']);
        // Never silently overwrite the editor's unpublished layout changes.
        if($draft&&$published&&cms_layout_json($draft)!==cms_layout_json($published))
          throw new RuntimeException('LAYOUT_HAS_UNPUBLISHED_DRAFT');
        $layout=$published??$draft??cms_layout_default();
      } else $layout=cms_layout_default();
      // Keep the selected sections, pinned cards and editor-curated order.
      // Enlarge only the feed, with automatic fill of remaining CMS articles.
      foreach($layout['sections'] as &$sec) {
        if($sec['id']==='latest'){
          $sec['mode']='auto';
          $sec['limit']=13;
          $sec['category_slug']='';
        }
      }
      unset($sec);
      $layout=cms_layout_normalize($layout);
      $serial=cms_layout_json($layout);
      $newVersion=(int)($row['version']??0)+1;
      $publishedVersion=(int)($row['published_version']??0)+1;
      if($row){
        $q=$db->prepare('UPDATE cms_editorial_layout SET draft_json=?,published_json=?,version=?,published_version=?,published_at=UTC_TIMESTAMP(),updated_by=? WHERE id=1');
        $q->execute([$serial,$serial,$newVersion,$publishedVersion,$uid]);
      }else{
        $q=$db->prepare('INSERT INTO cms_editorial_layout(id,draft_json,published_json,version,published_version,updated_by,published_at) VALUES(1,?,?,?,?,?,UTC_TIMESTAMP())');
        $q->execute([$serial,$serial,$newVersion,$publishedVersion,$uid]);
      }
      $q=$db->prepare('INSERT INTO cms_editorial_layout_history(version,layout_json,published_by) VALUES(?,?,?)');
      $q->execute([$publishedVersion,$serial,$uid]);
      $layoutPublished=true;
    }
    $db->commit();
  }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
  cms_log($uid,'editorial_more_install','article',0);
  if($layoutPublished)cms_log($uid,'publish','layout',1);
  return ['created'=>$created,'already_present'=>$existing,'layout_published'=>$layoutPublished,'layout_version'=>$newVersion];
}
