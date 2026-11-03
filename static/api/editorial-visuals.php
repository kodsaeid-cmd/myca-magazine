<?php
declare(strict_types=1);
// Curated three-image plans for all twenty authored MYCA articles.
// Reuses already-owned first-party images, never browser-side external CDNs.
// Captions (ALT) truthfully describe visible scenes rather than claiming that
// a generic photo illustrates a clinical maneuver or diagnosis.
require_once __DIR__.'/bootstrap.php';
function cms_editorial_visual_library(): array {
  return [
    'care-talk'=>['/media/editorial/cover-01-caregiver-selection.webp','گفت‌وگوی کارشناس مراقبت با اعضای خانواده'],
    'companionship'=>['/media/editorial/cover-02-care-vs-nursing.webp','همراهی مراقب با بانوی سالمند در خانه'],
    'bathroom'=>['/media/editorial/cover-03-bathroom-safety.webp','صندلی حمام و دستگیره ایمنی در سرویس بهداشتی'],
    'corridor'=>['/media/editorial/cover-04-fall-prevention.webp','راهروی منزل با دستگیره و نورپردازی مناسب'],
    'conversation'=>['/media/editorial/cover-05-dementia-communication.webp','گفت‌وگوی آرام با بانوی سالمند در خانه'],
    'meal'=>['/media/editorial/cover-06-senior-nutrition.webp','سالمند در حال صرف غذای خانگی'],
    'break'=>['/media/editorial/cover-07-caregiver-wellbeing.webp','مراقب در حال استراحت کنار پنجره'],
    'medicine'=>['/media/editorial/cover-08-medication-safety.webp','جعبه تقسیم قرص و جدول پیگیری دارو'],
    'walker'=>['/media/editorial/cover-09-walker-selection.webp','سالمند در حال راه‌رفتن با واکر در منزل'],
    'water'=>['/media/editorial/cover-10-dehydration.webp','مراقب در حال کمک به سالمند برای نوشیدن آب'],
    'discharge'=>['/media/editorial-more/cover-11-elder-discharge.jpg','کمک به حرکت سالمند در فضای داخلی منزل'],
    'report'=>['/media/editorial-more/cover-12-daily-care-report.jpg','گفت‌وگو و هماهنگی خانواده و مراقب'],
    'home'=>['/media/editorial-more/cover-13-pressure-prevention.jpg','فضای داخلی منزل با امکانات مراقبت و ایمنی'],
    'rest'=>['/media/editorial-more/cover-14-elderly-sleep.jpg','فضای آرام خانه و مراقبت از آسایش سالمند'],
    'social'=>['/media/editorial-more/cover-15-social-connection.jpg','همراهی و تعامل خانوادگی با سالمند'],
    'activity'=>['/media/editorial-more/cover-16-physical-activity.jpg','سالمند در حال انجام فعالیت حرکتی روزانه'],
    'memory'=>['/media/editorial-more/cover-17-dementia-activities.jpg','گفت‌وگوی مراقب و سالمند در خانه'],
    'oral'=>['/media/editorial-more/cover-18-oral-health.jpg','سالمند کنار میز غذا در محیط خانه'],
    'childsafe'=>['/media/editorial-more/cover-19-child-medication-safety.jpg','فضای خانه و مراقبت از ایمنی کودک'],
    'childroutine'=>['/media/editorial-more/cover-20-child-family-routine.jpg','بازی و فعالیت روزانه کودک در خانه'],
    'childplay'=>['/media-check/photo-child-play.jpg','بازی کودک و همراهی خانواده در محیط خانه'],
    'childhome'=>['/media-check/photo-child-home-safety.jpg','محیط خانه و وسایل مرتبط با کودک']
  ];
}
function cms_editorial_visual_manifest(): array {
  return [
    'choose-elderly-caregiver-at-home'=>['companionship','conversation','break'],
    'difference-caregiver-nurse'=>['care-talk','medicine','water'],
    'safe-bathroom-for-older-adults'=>['corridor','walker','home'],
    'prevent-falls-elderly-home'=>['bathroom','walker','activity'],
    'communication-with-dementia-older-adult'=>['companionship','care-talk','break'],
    'elderly-nutrition-poor-appetite'=>['water','oral','social'],
    'caregiver-stress-warning-signs'=>['care-talk','companionship','social'],
    'medication-safety-elderly-home'=>['report','care-talk','water'],
    'choose-walker-for-elderly'=>['corridor','bathroom','activity'],
    'dehydration-signs-older-adults'=>['meal','oral','care-talk'],
    'elderly-discharge-home-checklist'=>['walker','care-talk','medicine'],
    'daily-caregiver-shift-report'=>['care-talk','medicine','water'],
    'pressure-injury-prevention-at-home'=>['walker','bathroom','discharge'],
    'better-sleep-for-older-adults'=>['break','social','conversation'],
    'social-connection-older-adults'=>['conversation','companionship','memory'],
    'safe-physical-activity-for-seniors'=>['corridor','walker','water'],
    'meaningful-activities-for-dementia'=>['conversation','social','companionship'],
    'elderly-oral-care-at-home'=>['meal','water','care-talk'],
    'safe-medicine-storage-children'=>['medicine','childplay','childroutine'],
    'family-routine-young-child-caregiver'=>['childsafe','childplay','childhome']
  ];
}
function cms_editorial_visual_items(): array {
  $library=cms_editorial_visual_library();
  $items=[];
  foreach(cms_editorial_visual_manifest() as $slug=>$names) {
    if(count($names)!==3 || count(array_unique($names))!==3)
      throw new RuntimeException('VISUAL_MANIFEST_INVALID');
    foreach($names as $name){
      if(!isset($library[$name]))throw new RuntimeException('VISUAL_LIBRARY_INVALID');
      $items[$slug][]=['url'=>$library[$name][0],'alt'=>$library[$name][1]];
    }
  }
  return $items;
}
function cms_visuals_apply_body(string $html,array $photos): string {
  // This operation adds only images. No title, paragraph, link or text is
  // regenerated; existing uploaded images or custom article edits survive.
  if(preg_match('~<img\\b~i',$html)) return $html;
  $dom=new DOMDocument();
  $prev=libxml_use_internal_errors(true);
  $ok=$dom->loadHTML('<!doctype html><html><head><meta charset="UTF-8"></head><body><div id="myca-content">'.$html.'</div></body></html>',LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);
  libxml_clear_errors();libxml_use_internal_errors($prev);
  $root=$dom->getElementById('myca-content');
  if(!$ok||!$root) throw new RuntimeException('ARTICLE_HTML_INVALID');
  $heads=[];
  foreach($root->getElementsByTagName('h2') as $h2) {
    $title=trim($h2->textContent);
    if($title===''||str_contains($title,'مطالب مرتبط')||str_contains($title,'منابع')||str_contains($title,'پرسش'))
      continue;
    $heads[]=$h2;
  }
  // Do not append a gallery at the end; weave images throughout actual sections.
  if(count($heads)<3)throw new RuntimeException('ARTICLE_HEADINGS_MISSING');
  $count=count($heads);
  $indexes=[0,(int)floor(($count-1)/2),$count-1];
  if(count(array_unique($indexes))!==3)throw new RuntimeException('ARTICLE_IMAGE_POSITIONS_INVALID');
  foreach($photos as $i=>$photo){
    $head=$heads[$indexes[$i]];
    $after=$head;
    $next=$head->nextSibling;
    while($next && !($next instanceof DOMElement))$next=$next->nextSibling;
    if($next instanceof DOMElement && in_array(strtolower($next->tagName),['p','ul','ol'],true))$after=$next;
    $para=$dom->createElement('p');
    $img=$dom->createElement('img');
    $img->setAttribute('src',$photo['url']);
    $img->setAttribute('alt',$photo['alt']);
    $img->setAttribute('loading','lazy');
    $para->appendChild($img);
    if($after->nextSibling)$root->insertBefore($para,$after->nextSibling);
    else $root->appendChild($para);
  }
  $out='';
  foreach($root->childNodes as $node)$out.=$dom->saveHTML($node);
  $sanitized=cms_html($out);
  if(substr_count($sanitized,'<img')!==3)throw new RuntimeException('ARTICLE_VISUAL_SANITIZATION_FAILED');
  return $sanitized;
}
function cms_editorial_visuals_install(PDO $db,int $adminId,bool $dryRun): array {
  $items=cms_editorial_visual_items();
  $webRoot=dirname(__DIR__);
  $seen=[];
  foreach($items as $photos)foreach($photos as $photo){
    $src=$photo['url'];
    if(isset($seen[$src]))continue;
    $seen[$src]=true;
    $path=$webRoot.$src;
    $info=is_file($path)?@getimagesize($path):false;
    if(!$info || !in_array((string)($info['mime']??''),['image/jpeg','image/webp','image/png'],true))
      throw new RuntimeException('INLINE_IMAGE_FILE_MISSING_OR_INVALID:'.$src);
  }
  $updated=0;$already=0;$notFound=[];$imagesAdded=0;
  $db->beginTransaction();
  try {
    foreach($items as $slug=>$photos){
      $q=$db->prepare("SELECT * FROM cms_articles WHERE slug=? AND status='published' FOR UPDATE");
      $q->execute([$slug]);$article=$q->fetch();
      if(!$article){$notFound[]=$slug;continue;}
      $old=(string)$article['body_html'];
      if(preg_match('~<img\\b~i',$old)){$already++;continue;}
      $new=cms_visuals_apply_body($old,$photos);
      if($new===$old){$already++;continue;}
      $updated++;$imagesAdded+=3;
      if(!$dryRun){
        $db->prepare('INSERT INTO cms_revisions(article_id,editor_id,snapshot) VALUES(?,?,?)')->execute([
          (int)$article['id'],$adminId,json_encode($article,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR)
        ]);
        $db->prepare('UPDATE cms_articles SET body_html=?,updated_at=UTC_TIMESTAMP() WHERE id=?')->execute([$new,(int)$article['id']]);
      }
    }
    if($dryRun)$db->rollBack();
    else $db->commit();
  }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
  if(!$dryRun)cms_log($adminId,'editorial_visuals_install','article',0);
  return ['dry_run'=>$dryRun,'updated_articles'=>$updated,'images_added'=>$imagesAdded,
    'already_has_images'=>$already,'not_found'=>$notFound,'verified_source_files'=>count($seen)];
}
