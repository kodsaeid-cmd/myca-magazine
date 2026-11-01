<script>
  import '$lib/styles/magazine-home.css';
  import { env } from '$env/dynamic/public';
  import { page } from '$app/stores';
  import { Clock3, HeartHandshake, Baby, HeartPulse, Accessibility, GraduationCap, House, Headphones } from '@lucide/svelte';
  import HeroNewsDesk from '$lib/components/HeroNewsDesk.svelte';
  import { categories, articles, categoryName } from '$lib/content.js';
  import { imageFor } from '$lib/images.js';

  const appUrl = env.PUBLIC_APP_URL || 'https://mycaapp.com';
  const iconMap = {
    'heart-handshake': HeartHandshake,
    baby: Baby,
    'heart-pulse': HeartPulse,
    accessibility: Accessibility,
    'graduation-cap': GraduationCap,
    house: House
  };

  const latestStories = articles.slice(0, 4);
  const themes = categories.map((category) => ({
    ...category,
    image: articles.find((article) => article.category === category.slug)?.image || articles[0].image
  }));
  const editorPick = articles[6];
  const editorSide = [articles[5], articles[2]];
  const moreStories = articles.slice(3, 9);
</script>

<svelte:head>
  <title>مجله مایکا | دانش برای مراقبت بهتر، زندگی آرام‌تر</title>
  <meta name="description" content="مجله مایکا؛ تازه‌ترین راهنماهای مراقبت سالمند، کودک و خانواده، سلامت، ایمنی خانه و تجهیزات مراقبتی." />
  <link rel="canonical" href={$page.url.origin + '/'} />
  <meta property="og:type" content="website" />
  <meta property="og:title" content="مجله مایکا | دانش برای مراقبت بهتر" />
  <meta property="og:description" content="راهنماهای کاربردی و خواندنی برای زندگی آرام‌تر خانواده‌ها" />
</svelte:head>

<div class="container page-wrap magazine-home">
  <HeroNewsDesk items={latestStories} />

  <section class="mag-section" aria-labelledby="themes-title">
    <div class="mag-section-title">
      <div><h2 id="themes-title">آنچه به آن نیاز دارید</h2><p>راهنماهای کاربردی در هر مرحله از زندگی</p></div>
    </div>
    <div class="mag-topics">
      {#each themes as category}
        <a class="mag-topic" href={'/category/' + category.slug} aria-label={'مشاهده ' + category.label}>
          <div class="mag-topic-photo"><img src={imageFor(category.image, 780)} alt="" loading="lazy" decoding="async" /></div>
          <span class="mag-topic-gradient"></span>
          <div class="mag-topic-copy">
            <span class="mag-topic-glyph"><svelte:component this={iconMap[category.icon]} size={21} strokeWidth={1.8} /></span>
            <strong>{category.label}</strong>
            <span class="mag-topic-description">{category.description}</span>
          </div>
        </a>
      {/each}
    </div>
  </section>

  <section class="mag-section" aria-labelledby="selected-title">
    <div class="mag-section-title">
      <div><h2 id="selected-title">برای لحظه‌های مهم زندگی</h2><p>مطلب‌هایی که کمک می‌کنند با آگاهی بیشتری مراقبت کنیم</p></div>
      <a class="mag-section-link" href="/category/elder-care">همه مطالب مراقبتی</a>
    </div>
    <div class="mag-editorial-grid">
      <a class="mag-feature-main" href={'/article/' + editorPick.slug}>
        <img src={imageFor(editorPick.image, 1500)} alt={editorPick.imageAlt} loading="lazy" decoding="async"/>
        <div class="mag-feature-tint"></div>
        <div class="mag-feature-copy">
          <span class="mag-phototag">{categoryName(editorPick.category)}</span>
          <h3>{editorPick.title}</h3>
          <p>{editorPick.excerpt}</p>
          <span class="mag-feature-cta">مطالعه این مطلب</span>
        </div>
      </a>
      <div class="mag-feature-pair">
        {#each editorSide as article}
          <a class="mag-feature-small" href={'/article/' + article.slug}>
            <img src={imageFor(article.image, 900)} alt={article.imageAlt} loading="lazy" decoding="async"/>
            <span class="mag-feature-small-copy">
              <small>{categoryName(article.category)}</small>
              <strong>{article.title}</strong>
              <span>{article.minutes} دقیقه مطالعه</span>
            </span>
          </a>
        {/each}
      </div>
    </div>
  </section>

  <section class="mag-section" aria-labelledby="recent-title">
    <div class="mag-section-title">
      <div><h2 id="recent-title">تازه‌ترین خواندنی‌ها</h2><p>از مراقبت سالمند تا دنیای کودک و خانواده</p></div>
      <a class="mag-section-link" href="/search?q=مراقبت">جست‌وجو در مقالات</a>
    </div>
    <div class="mag-stories-grid">
      {#each moreStories as article}
        <a class="mag-story-card" href={'/article/' + article.slug}>
          <div class="mag-story-media">
            <img src={imageFor(article.image, 940)} alt={article.imageAlt} loading="lazy" decoding="async" />
            <span>{categoryName(article.category)}</span>
          </div>
          <div class="mag-story-body">
            <h3>{article.title}</h3>
            <p>{article.excerpt}</p>
            <div class="mag-story-bottom"><span><Clock3 size={14} aria-hidden="true" /> {article.minutes} دقیقه مطالعه</span><strong>مطالعه مقاله</strong></div>
          </div>
        </a>
      {/each}
    </div>
  </section>

  <a href={appUrl} target="_blank" rel="noopener noreferrer" class="mag-care-banner" aria-label="ورود به اپلیکیشن و درخواست خدمات مراقبتی مایکا">
    <img src={imageFor(articles[0].image, 1350)} alt="" loading="lazy" decoding="async" />
    <span class="mag-care-overlay"></span>
    <span class="mag-care-copy">
      <span class="mag-care-icon"><Headphones size={25} strokeWidth={1.8} /></span>
      <strong>برای مراقبت، تنها نیستید.</strong>
      <span>در کنار راهنماهای مجله، خدمات مراقبتی مایکا نیز همراه خانواده شماست.</span>
      <span class="mag-care-action">آشنایی با خدمات مایکا</span>
    </span>
  </a>
</div>
