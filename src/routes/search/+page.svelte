<script>
  import { page } from '$app/stores';
  import { searchArticles } from '$lib/content.js';
  import { Search, Clock3, ArrowLeft, ChevronLeft } from '@lucide/svelte';
  import { categoryName } from '$lib/content.js';
  $: q = ($page.url.searchParams.get('q') || '').trim().slice(0, 120);\n  $: results = searchArticles(q);
</script>
<svelte:head>
  <title>{q ? 'نتایج «' + q + '»' : 'جست‌وجوی مقالات'} | مجله مایکا</title>
  <meta name="robots" content="noindex,follow"/>
</svelte:head>
<div class="container page-wrap">
  <div class="breadcrumb"><a href="/">مجله مایکا</a><ChevronLeft size={14}/><span>جست‌وجو</span></div>
  <header class="page-hero"><span class="eyebrow">SEARCH THE MAGAZINE</span><h1>دنبال چه موضوعی هستید؟</h1><p>در مقالات و راهنماهای مراقبتی مایکا جست‌وجو کنید.</p></header>
  <form class="search-page-form" action="/search" method="GET" role="search">
    <input name="q" type="search" value={q} placeholder="مثلاً مراقبت از سالمند، خانه ایمن، کودک..." aria-label="عبارت جست‌وجو"/>
    <button class="btn-primary" type="submit"><Search size={18}/> جست‌وجو</button>
  </form>
  {#if q}
    <div class="section-heading"><h2>نتایج برای «{q}»</h2><span class="eyebrow">{results.length.toLocaleString('fa-IR')} نتیجه</span></div>
    {#if results.length}
      <div class="results-grid">
        {#each results as article}
          <article class="article-card">
            <a href={'/article/' + article.slug} class="article-image"><img src={article.image} alt={article.imageAlt} loading="lazy"/><span class="image-label">{categoryName(article.category)}</span></a>
            <div class="article-details"><h3><a href={'/article/' + article.slug}>{article.title}</a></h3><p>{article.excerpt}</p><div class="article-card-bottom"><span class="story-meta"><Clock3 size={13}/>{article.minutes} دقیقه</span><a href={'/article/' + article.slug} class="text-link">مطالعه <ArrowLeft size={15}/></a></div></div>
          </article>
        {/each}
      </div>
    {:else}
      <div class="empty-state"><Search size={30} color="#00a4d4"/><h2>مطلبی با این عبارت پیدا نشد</h2><p>عبارت کوتاه‌تر یا موضوعی دیگر را امتحان کنید.</p><a href="/" class="text-link">بازگشت به مجله <ArrowLeft size={16}/></a></div>
    {/if}
  {:else}
    <div class="empty-state"><h2>موضوعی را جست‌وجو کنید</h2><p>برای شروع، یک کلمه یا عبارت در کادر بالا بنویسید.</p></div>
  {/if}
</div>
