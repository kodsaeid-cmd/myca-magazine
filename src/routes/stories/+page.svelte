<script>
  import { onMount } from 'svelte';
  import { BookOpen, Search, ArrowLeft } from '@lucide/svelte';
  let entries = [];
  let loading = true;
  let offline = false;
  let query = '';
  onMount(async () => {
    try {
      const res = await fetch('/api/index.php?route=published', { credentials: 'same-origin' });
      if (!res.ok) throw new Error('Backend unavailable');
      const json = await res.json();
      if (!json.ok) throw new Error('Backend unavailable');
      entries = json.articles || [];
    } catch { offline = true; }
    finally { loading = false; }
  });
  $: filtered = entries.filter((a) => !query || (a.title + ' ' + a.excerpt).includes(query));
</script>
<svelte:head>
  <title>مقالات تازه | مجله مایکا</title>
  <meta name="robots" content="noindex,follow" />
  <meta name="description" content="مقالات کاربردی مراقبت سالمند و خانواده در مجله مایکا" />
</svelte:head>
<section class="container cms-stories-fallback" dir="rtl">
  <div class="cms-stories-top"><span>MYCA · EDITORIAL</span><h1>تازه‌ترین مقالات مجله مایکا</h1><p>راهنماهای تخصصی مراقبت، سلامت و زندگی بهتر برای خانواده‌ها</p></div>
  <label class="cms-story-search"><Search size={18}/><input bind:value={query} aria-label="جست‌وجوی مقالات" placeholder="جست‌وجوی مقالات…"/></label>
  {#if loading}<p class="cms-muted">در حال دریافت تازه‌ترین مطالب…</p>
  {:else if offline}<div class="cms-story-offline"><BookOpen size={32}/><h2>بخش انتشار در حال راه‌اندازی است</h2><p>مقالات قبلی مجله از منوی بالای سایت در دسترس‌اند.</p><a href="/">بازگشت به صفحه اصلی</a></div>
  {:else}<div class="cms-stories-grid">
    {#each filtered as article (article.slug)}
      <a class="cms-story-link" href={'/stories/' + article.slug + '/'}>
        {#if article.cover_url}<img src={article.cover_url} alt={article.cover_alt || article.title} loading="lazy"/>{/if}
        <div><span>مجله مایکا</span><h2>{article.title}</h2><p>{article.excerpt}</p><strong>مطالعه مقاله <ArrowLeft size={16}/></strong></div>
      </a>
    {:else}<p>هنوز مقاله منتشرشده‌ای برای نمایش وجود ندارد.</p>{/each}
  </div>{/if}
</section>
<style>
  .cms-stories-fallback{padding:35px 0 85px;min-height:55vh}
  .cms-stories-top{margin-bottom:24px}.cms-stories-top>span,.cms-story-link>div>span{font:700 11px Arial,sans-serif;color:#00a4d4}
  .cms-stories-top h1{font-size:clamp(23px,3vw,37px);margin:9px 0;color:#062e5c}
  .cms-stories-top p,.cms-muted,.cms-story-link p{color:#66758a}
  .cms-story-search{display:flex;align-items:center;gap:10px;background:white;border:1px solid #e2eaf0;max-width:440px;border-radius:12px;padding:0 13px;margin-bottom:23px;color:#548296}
  .cms-story-search input{min-height:48px;flex:1;border:0;outline:none;min-width:0}
  .cms-stories-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:17px}
  .cms-story-link{border:1px solid #e2eaf0;border-radius:19px;background:white;overflow:hidden;transition:transform .2s}
  .cms-story-link:hover{transform:translateY(-3px)}.cms-story-link img{width:100%;height:195px;object-fit:cover}
  .cms-story-link>div{padding:18px}.cms-story-link h2{font-size:18px;line-height:1.7}.cms-story-link p{font-size:13px;line-height:1.9}
  .cms-story-link strong{display:flex;align-items:center;gap:5px;color:#008db8;font-size:13px}
  .cms-story-offline{padding:45px;text-align:center;background:white;border-radius:20px;border:1px solid #e2eaf0}
  .cms-story-offline svg{color:#00a4d4}.cms-story-offline a{color:#008db8;font-weight:700}
  @media(max-width:900px){.cms-stories-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @media(max-width:550px){.cms-stories-grid{grid-template-columns:1fr}.cms-stories-fallback{padding-top:20px}}
</style>
