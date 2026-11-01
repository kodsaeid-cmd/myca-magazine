<script>
  import { page } from '$app/stores';
  import { env } from '$env/dynamic/public';
  import { Clock3, CalendarDays, BookOpen, ShieldCheck } from '@lucide/svelte';
  import { categoryName } from '$lib/content.js';
  export let data;
  const appUrl = env.PUBLIC_APP_URL || 'https://mycaapp.com';
  $: a = data.article;
  $: canonical = $page.url.origin + '/article/' + a.slug;
  $: schema = JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'Article',
    headline: a.title,
    description: a.excerpt,
    image: [a.image],
    inLanguage: 'fa-IR',
    mainEntityOfPage: canonical,
    publisher: { '@type': 'Organization', name: 'MYCA' }
  }).replace(/</g, '\\u003c');
</script>

<svelte:head>
  <title>{a.title} | مجله مایکا</title>
  <meta name="description" content={a.excerpt} />
  <link rel="canonical" href={canonical} />
  <meta property="og:type" content="article" />
  <meta property="og:title" content={a.title} />
  <meta property="og:description" content={a.excerpt} />
  <meta property="og:image" content={a.image} />
  {@html '<script type="application/ld+json">' + schema + '<\/script>'}
</svelte:head>

<div class="container page-wrap">
  <nav class="breadcrumb" aria-label="مسیر صفحه">
    <a href="/">مجله مایکا</a><span class="breadcrumb-separator" aria-hidden="true">/</span>
    <a href={'/category/' + a.category}>{categoryName(a.category)}</a><span class="breadcrumb-separator" aria-hidden="true">/</span>
    <span>{a.title}</span>
  </nav>
  <div class="article-shell">
    <article class="article-body">
      <span class="eyebrow">{categoryName(a.category)}</span>
      <h1>{a.title}</h1>
      <p class="article-lead">{a.excerpt}</p>
      <div class="story-meta"><span><Clock3 size={14}/>{a.minutes} دقیقه مطالعه</span><span><CalendarDays size={14}/>{a.date}</span></div>
      <img class="article-cover" src={a.image} alt={a.imageAlt} fetchpriority="high" />
      <h2>راهنمای کاربردی</h2>
      {#each a.paragraphs as paragraph}
        <p class="article-paragraph">{paragraph}</p>
      {/each}
      <div class="article-note"><ShieldCheck size={19} style="display:inline;vertical-align:middle;margin-left:8px"/>این مطلب صرفاً اطلاعات عمومی و نمونه محتوای مجله است و جایگزین ارزیابی یا توصیه متخصص دارای صلاحیت نیست.</div>
      <div class="editorial-bottom" style="margin-top:26px">
        <a href={'/category/' + a.category} class="text-link">مطالب بیشتر این موضوع </a>
        <a href={appUrl} class="btn-primary" target="_blank" rel="noopener noreferrer">خدمات مایکا </a>
      </div>
    </article>
    <aside class="panel article-related">
      <div class="panel-heading"><h2>مطالب مرتبط</h2><BookOpen size={19} color="#00a4d4"/></div>
      {#if data.related.length}
        {#each data.related as related}
          <a class="compact-story" href={'/article/' + related.slug}>
            <img src={related.image} alt={related.imageAlt} loading="lazy" />
            <div class="compact-story-info"><strong>{related.title}</strong><small>{related.minutes} دقیقه مطالعه</small></div>
          </a>
        {/each}
      {:else}
        <p class="article-lead" style="font-size:12px">به‌زودی مطالب بیشتری در این موضوع منتشر می‌شود.</p>
      {/if}
      <div class="editorial-side-banner" style="margin-top:25px"><strong>به مراقب نیاز دارید؟</strong><p>برای مشاهده خدمات و ثبت درخواست به اپلیکیشن مایکا سر بزنید.</p><a class="text-link" href={appUrl} target="_blank" rel="noopener noreferrer">ورود به مایکا </a></div>
    </aside>
  </div>
</div>
