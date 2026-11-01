<script>
  import { page } from '$app/stores';
  import { Clock3 } from '@lucide/svelte';
  import { imageFor } from '$lib/images.js';
  export let data;
</script>

<svelte:head>
  <title>{data.category.label} | مجله مایکا</title>
  <meta name="description" content={data.category.description} />
  <link rel="canonical" href={$page.url.origin + '/category/' + data.category.slug} />
</svelte:head>

<div class="container page-wrap">
  <nav class="breadcrumb" aria-label="مسیر صفحه"><a href="/">مجله مایکا</a><span class="breadcrumb-separator" aria-hidden="true">/</span><span>{data.category.label}</span></nav>
  <header class="page-hero">
    <h1>{data.category.label}</h1>
    <p>{data.category.description}</p>
  </header>
  <div class="section-heading"><h2>مقالات و راهنماها</h2><span class="eyebrow">{data.articles.length.toLocaleString('fa-IR')} مطلب</span></div>
  {#if data.articles.length > 0}
    <div class="results-grid">
      {#each data.articles as article}
        <a class="article-card" href={'/article/' + article.slug} aria-label={'مطالعه ' + article.title}>
          <div class="article-image">
            <img src={imageFor(article.image, 980)} alt={article.imageAlt} loading="lazy" decoding="async" />
            <span class="image-label">{data.category.label}</span>
          </div>
          <div class="article-details">
            <h3>{article.title}</h3>
            <p>{article.excerpt}</p>
            <div class="article-card-bottom">
              <span class="story-meta"><Clock3 size={13} aria-hidden="true" />{article.minutes} دقیقه مطالعه</span>
              <strong class="text-link">مطالعه مقاله</strong>
            </div>
          </div>
        </a>
      {/each}
    </div>
  {:else}
    <div class="empty-state"><h2>مطلبی در این دسته ثبت نشده است</h2><p>برای مطالعه سایر مطالب به صفحه اصلی برگردید.</p><a href="/" class="text-link">صفحه اصلی</a></div>
  {/if}
</div>
