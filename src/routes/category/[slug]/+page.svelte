<script>
  import { page } from '$app/stores';
  import { ArrowLeft, Clock3, ChevronLeft } from 'lucide-svelte';
  export let data;
</script>

<svelte:head>
  <title>{data.category.label} | مجله مایکا</title>
  <meta name="description" content={data.category.description} />
  <link rel="canonical" href={$page.url.origin + '/category/' + data.category.slug} />
</svelte:head>

<div class="container page-wrap">
  <div class="breadcrumb"><a href="/">مجله مایکا</a><ChevronLeft size={14}/><span>{data.category.label}</span></div>
  <header class="page-hero">
    <span class="eyebrow">MYCA MAGAZINE / TOPICS</span>
    <h1>{data.category.label}</h1>
    <p>{data.category.description}</p>
  </header>
  <div class="section-heading"><h2>مقالات و راهنماها</h2><span class="eyebrow">{data.articles.length.toLocaleString('fa-IR')} مطلب</span></div>
  {#if data.articles.length > 0}
    <div class="results-grid">
      {#each data.articles as article}
        <article class="article-card">
          <a class="article-image" href={'/article/' + article.slug}>
            <img src={article.image} alt={article.imageAlt} loading="lazy" />
            <span class="image-label">{data.category.label}</span>
          </a>
          <div class="article-details">
            <h3><a href={'/article/' + article.slug}>{article.title}</a></h3>
            <p>{article.excerpt}</p>
            <div class="article-card-bottom">
              <span class="story-meta"><Clock3 size={13}/>{article.minutes} دقیقه مطالعه</span>
              <a class="text-link" href={'/article/' + article.slug}>مطالعه <ArrowLeft size={16}/></a>
            </div>
          </div>
        </article>
      {/each}
    </div>
  {:else}
    <div class="empty-state"><h2>مطلبی در این دسته ثبت نشده است</h2><p>برای مطالعه سایر مطالب به صفحه اصلی برگردید.</p><a href="/" class="text-link">صفحه اصلی <ArrowLeft size={16}/></a></div>
  {/if}
</div>
