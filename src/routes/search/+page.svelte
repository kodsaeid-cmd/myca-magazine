<script>
  import { page } from '$app/stores';
  import { browser } from '$app/environment';
  import { Search, Clock3 } from '@lucide/svelte';
  import { searchArticles, categoryName } from '$lib/content.js';
  import { imageFor } from '$lib/images.js';

  $: q = browser ? ($page.url.searchParams.get('q') || '').trim().slice(0, 120) : '';
  $: results = searchArticles(q);
</script>

<svelte:head>
  <title>{q ? 'نتایج «' + q + '»' : 'جست‌وجوی مقالات'} | مجله مایکا</title>
  <meta name="robots" content="noindex,follow"/>
</svelte:head>

<div class="container page-wrap">
  <nav class="breadcrumb" aria-label="مسیر صفحه"><a href="/">مجله مایکا</a><span class="breadcrumb-separator" aria-hidden="true">/</span><span>جست‌وجو</span></nav>
  <header class="page-hero"><h1>دنبال چه موضوعی هستید؟</h1><p>در مقالات و راهنماهای مراقبتی مایکا جست‌وجو کنید.</p></header>
  <form class="search-page-form" action="/search" method="GET" role="search">
    <input name="q" type="search" value={q} placeholder="مثلاً مراقبت از سالمند، خانه ایمن، کودک..." aria-label="عبارت جست‌وجو"/>
    <button class="btn-primary" type="submit"><Search size={18} aria-hidden="true"/> جست‌وجو</button>
  </form>
  {#if q}
    <div class="section-heading"><h2>نتایج برای «{q}»</h2><span class="eyebrow">{results.length.toLocaleString('fa-IR')} نتیجه</span></div>
    {#if results.length}
      <div class="results-grid">
        {#each results as article}
          <a class="article-card" href={'/article/' + article.slug} aria-label={'مطالعه ' + article.title}>
            <div class="article-image">
              <img src={imageFor(article.image, 980)} alt={article.imageAlt} loading="lazy" decoding="async"/>
              <span class="image-label">{categoryName(article.category)}</span>
            </div>
            <div class="article-details"><h3>{article.title}</h3><p>{article.excerpt}</p>
              <div class="article-card-bottom"><span class="story-meta"><Clock3 size={13} aria-hidden="true"/>{article.minutes} دقیقه</span><strong class="text-link">مطالعه مقاله</strong></div>
            </div>
          </a>
        {/each}
      </div>
    {:else}
      <div class="empty-state"><Search size={30} color="#00a4d4"/><h2>مطلبی با این عبارت پیدا نشد</h2><p>عبارت کوتاه‌تر یا موضوعی دیگر را امتحان کنید.</p><a href="/" class="text-link">بازگشت به مجله</a></div>
    {/if}
  {:else}
    <div class="empty-state"><h2>موضوعی را جست‌وجو کنید</h2><p>برای شروع، یک کلمه یا عبارت در کادر بالا بنویسید.</p></div>
  {/if}
</div>
