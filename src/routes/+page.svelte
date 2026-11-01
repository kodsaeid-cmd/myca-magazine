<script>
  import { env } from '$env/dynamic/public';
  import { page } from '$app/stores';
  import {
    HeartHandshake, Baby, HeartPulse, Accessibility, GraduationCap, House,
    ArrowLeft, ArrowUpLeft, CalendarDays, Clock3, BookOpen, ShieldCheck,
    Sparkles, Headphones, PlayCircle, ChevronLeft, Heart, UsersRound
  } from '@lucide/svelte';
  import { categories, articles, categoryName } from '$lib/content.js';

  const appUrl = env.PUBLIC_APP_URL || 'https://mycaapp.com';
  const iconMap = {
    'heart-handshake': HeartHandshake,
    baby: Baby,
    'heart-pulse': HeartPulse,
    accessibility: Accessibility,
    'graduation-cap': GraduationCap,
    house: House
  };
  const hero = articles[0];
  const spotlight = articles[1];
  const mostRead = [articles[2], articles[4], articles[5]];
  const latest = [articles[3], articles[4], articles[6], articles[7]];
</script>

<svelte:head>
  <title>مجله مایکا | دانش برای مراقبت بهتر، زندگی آرام‌تر</title>
  <meta name="description" content="مجله مایکا؛ راهنماهای کاربردی مراقبت سالمند، کودک و خانواده، ایمنی خانه، سلامت و تجهیزات مراقبتی. همراه شما برای انتخاب‌های آگاهانه‌تر." />
  <link rel="canonical" href={$page.url.origin + '/'} />
  <meta property="og:type" content="website" />
  <meta property="og:title" content="مجله مایکا | دانش برای مراقبت بهتر" />
  <meta property="og:description" content="آموزش‌ها و راهنماهای کاربردی برای خانواده و مراقبین" />
</svelte:head>

<div class="container page-wrap">
  <section class="hero-grid" aria-label="مطالب برجسته مجله">
    <div class="hero-main">
      <img class="hero-photo" src={hero.image} alt={hero.imageAlt} fetchpriority="high" />
      <div class="hero-content">
        <span class="hero-kicker"><Sparkles size={15} /> روایت ویژه مجله مایکا</span>
        <h1>مراقبت بهتر،<br />زندگی آرام‌تر</h1>
        <p>با دانش، تجربه و راهنمایی کاربردی، برای عزیزانمان محیطی امن‌تر و روزهایی آرام‌تر بسازیم.</p>
        <a class="hero-button" href={'/article/' + hero.slug}>مطالعه مطلب ویژه <ArrowLeft size={17}/></a>
      </div>
      <div class="hero-pagination" aria-hidden="true"><span></span><span></span><span></span></div>
    </div>

    <div class="hero-side">
      <div class="side-feature">
        <span class="mini-tag">فراتر از یک مجله</span>
        <BookOpen size={145} strokeWidth={1.1} class="side-feature-symbol" aria-hidden="true" />
        <h2>دانشِ مراقبت،<br />در کنار شما</h2>
        <p>از اولین پرسش تا تصمیم آگاهانه؛ مطالبی برای زندگی روزمره خانواده‌ها.</p>
        <a href="/category/elder-care" class="text-link">مسیر مطالعه را شروع کنید <ArrowLeft size={16}/></a>
      </div>
      <div class="side-stats">
        <div class="stat"><BookOpen size={23}/><strong>۶</strong><span>حوزه موضوعی</span></div>
        <div class="stat"><Heart size={23}/><strong>۱</strong><span>هدف مشترک: آرامش</span></div>
      </div>
    </div>
  </section>

  <section class="topic-section" aria-labelledby="topics-title">
    <div class="section-heading">
      <div><span class="eyebrow">EXPLORE TOPICS</span><h2 id="topics-title">دنیایی از دانستنی‌های مراقبتی</h2><p>آنچه لازم دارید، در مسیرهای روشن و قابل دسترس</p></div>
    </div>
    <div class="topic-grid">
      {#each categories as category}
        <a class="topic-card" href={'/category/' + category.slug}>
          <span class="topic-icon"><svelte:component this={iconMap[category.icon]} size={29} strokeWidth={1.8}/></span>
          <span>{category.label}</span>
        </a>
      {/each}
    </div>
  </section>

  <section class="editorial-section" aria-labelledby="editor-picks">
    <div class="section-heading">
      <div><span class="eyebrow">CURATED FOR YOU</span><h2 id="editor-picks">انتخاب تحریریه مایکا</h2><p>چند راهنمای خواندنی برای امروز و فردای خانواده</p></div>
      <a href="/search?q=مراقبت" class="text-link">مطالب بیشتر <ArrowLeft size={17}/></a>
    </div>
    <div class="editorial-grid">
      <aside class="panel editorial-card" aria-label="پربازدیدهای پیشنهادی">
        <div class="panel-heading"><h2>پیشنهادهای خواندنی</h2><BookOpen size={19} color="#00a4d4"/></div>
        <div class="compact-list">
          {#each mostRead as article}
            <a class="compact-story" href={'/article/' + article.slug}>
              <img src={article.image} alt={article.imageAlt} loading="lazy" />
              <div class="compact-story-info"><strong>{article.title}</strong><small>{article.minutes} دقیقه مطالعه</small></div>
              <ChevronLeft size={16} class="story-arrow" />
            </a>
          {/each}
        </div>
        <div class="editorial-side-banner">
          <strong>مراقبت، از آگاهی شروع می‌شود.</strong>
          <p>با خواندن یک راهنمای درست، تصمیم‌های بهتری بگیریم.</p>
          <a href="/category/caregiver-skills" class="text-link">آموزش مراقبین <ArrowLeft size={15}/></a>
        </div>
      </aside>
      <article class="panel editorial-main">
        <a class="editorial-image" href={'/article/' + spotlight.slug}>
          <img src={spotlight.image} alt={spotlight.imageAlt} loading="lazy" />
          <span class="media-label">{categoryName(spotlight.category)}</span>
          <span class="media-bottom"><ShieldCheck size={16}/> انتخاب تحریریه</span>
        </a>
        <div class="editorial-content">
          <span class="eyebrow">FEATURED STORY</span>
          <h3><a href={'/article/' + spotlight.slug}>{spotlight.title}</a></h3>
          <p>{spotlight.excerpt}</p>
          <div class="editorial-bottom">
            <div class="story-meta"><span><Clock3 size={13}/>{spotlight.minutes} دقیقه</span><span><CalendarDays size={13}/>{spotlight.date}</span></div>
            <a class="btn-primary" href={'/article/' + spotlight.slug}>مطالعه مقاله <ArrowLeft size={16}/></a>
          </div>
        </div>
      </article>
      <aside class="panel editorial-card" aria-label="راهنماهای مجله">
        <div class="panel-heading"><h2>از کجا شروع کنیم؟</h2><Sparkles size={19} color="#00a4d4"/></div>
        <div class="feature-stack">
          <div class="small-feature">
            <HeartHandshake color="#00a4d4" size={26}/>
            <strong>برای سالمند عزیزتان</strong>
            <p>راهنماهای مراقبت، ایمنی و آرامش در خانه</p>
            <a href="/category/elder-care" class="text-link">مشاهده راهنماها <ArrowLeft size={15}/></a>
          </div>
          <div class="small-feature">
            <UsersRound color="#00a4d4" size={26}/>
            <strong>برای همه خانواده</strong>
            <p>ایده‌هایی برای مراقبت روزمره و زندگی بهتر</p>
            <a href="/category/child-family" class="text-link">مطالب خانواده <ArrowLeft size={15}/></a>
          </div>
        </div>
      </aside>
    </div>
  </section>

  <section class="latest-section" aria-labelledby="newest-title">
    <div class="section-heading">
      <div><span class="eyebrow">FRESH PERSPECTIVES</span><h2 id="newest-title">تازه‌ترین مطالب</h2><p>خواندنی‌های تازه برای روزمره‌ای آگاهانه‌تر</p></div>
      <a href="/search?q=مراقبت" class="text-link">مشاهده مطالب <ArrowLeft size={16}/></a>
    </div>
    <div class="latest-grid">
      {#each latest as article}
        <article class="article-card">
          <a href={'/article/' + article.slug} class="article-image">
            <img src={article.image} alt={article.imageAlt} loading="lazy" />
            <span class="image-label">{categoryName(article.category)}</span>
          </a>
          <div class="article-details">
            <h3><a href={'/article/' + article.slug}>{article.title}</a></h3>
            <p>{article.excerpt}</p>
            <div class="article-card-bottom">
              <span class="story-meta"><Clock3 size={13}/>{article.minutes} دقیقه</span>
              <a href={'/article/' + article.slug} class="text-link">ادامه مطلب <ArrowLeft size={14}/></a>
            </div>
          </div>
        </article>
      {/each}
    </div>
  </section>

  <section class="service-banner" aria-label="خدمات مراقبتی مایکا">
    <div class="service-text">
      <span class="service-kicker">MYCA CARE ECOSYSTEM</span>
      <h2>در کنار دانستن، کنار شما هم هستیم.</h2>
      <p>اگر برای سالمند، کودک یا یکی از عزیزانتان به مراقب نیاز دارید، خدمات مایکا در دسترس شماست.</p>
    </div>
    <a class="btn-primary" href={appUrl} target="_blank" rel="noopener noreferrer">ورود به خدمات مایکا <ArrowUpLeft size={18}/></a>
  </section>
</div>
