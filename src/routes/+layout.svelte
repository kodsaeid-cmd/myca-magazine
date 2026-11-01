<script>
  import '../app.css';
  import { page } from '$app/stores';
  import { env } from '$env/dynamic/public';
  import { Menu, X, Search, BookOpen, Headphones, Sparkles } from '@lucide/svelte';

  const appUrl = env.PUBLIC_APP_URL || 'https://mycaapp.com';
  let mobileOpen = false;
  let searchOpen = false;

  const links = [
    { href: '/', label: 'خانه' },
    { href: '/category/elder-care', label: 'مراقبت سالمند' },
    { href: '/category/child-family', label: 'کودک و خانواده' },
    { href: '/category/health', label: 'سلامت و زندگی' },
    { href: '/category/equipment', label: 'تجهیزات مراقبتی' },
    { href: '/category/caregiver-skills', label: 'آموزش مراقبین' }
  ];
</script>

<a class="skip-link" href="#main-content">رفتن به محتوای اصلی</a>
<div class="site-shell">
  <div class="top-strip">
    <div class="container top-strip-inner">
      <span><Sparkles size={14} /> دانش برای مراقبت بهتر، آرامش برای زندگی</span>
      <span class="top-strip-note">مجله تخصصی مایکا · همراه خانواده‌ها</span>
    </div>
  </div>
  <header class="site-header">
    <div class="container header-inner">
      <div class="brand-wrap">
        <a class="brand" href="/" aria-label="صفحه اصلی مجله مایکا" onclick={() => (mobileOpen = false)}>
          <img src="/myca-logo.svg" width="116" height="43" alt="MYCA" />
          <span class="brand-divider"></span>
          <span class="brand-title">مجله <strong>مایکا</strong></span>
        </a>
      </div>
      <nav class="desktop-nav" aria-label="ناوبری اصلی">
        {#each links as link}
          <a href={link.href} class:current={$page.url.pathname === link.href}>{link.label}</a>
        {/each}
      </nav>
      <div class="header-actions">
        <form class:expanded={searchOpen} class="header-search" action="/search" method="GET" role="search">
          <input name="q" type="search" aria-label="جست‌وجوی مقالات" placeholder="جست‌وجو در مجله..." />
          <button type="submit" aria-label="جست‌وجو"><Search size={19} /></button>
        </form>
        <a class="header-cta" href={appUrl} target="_blank" rel="noopener noreferrer">درخواست خدمت </a>
        <button class="icon-action mobile-search-toggle" aria-label="نمایش جست‌وجو" aria-expanded={searchOpen} onclick={() => (searchOpen = !searchOpen)}><Search size={21} /></button>
        <button class="icon-action mobile-menu-toggle" aria-label={mobileOpen ? 'بستن منو' : 'بازکردن منو'} aria-expanded={mobileOpen} onclick={() => (mobileOpen = !mobileOpen)}>
          {#if mobileOpen}<X size={24} />{:else}<Menu size={24} />{/if}
        </button>
      </div>
    </div>
    {#if mobileOpen}
      <nav class="mobile-menu container" aria-label="فهرست موبایل">
        {#each links as link}
          <a href={link.href} onclick={() => (mobileOpen = false)}>{link.label}</a>
        {/each}
        <a href={appUrl} target="_blank" rel="noopener noreferrer">ورود به اپلیکیشن مایکا</a>
      </nav>
    {/if}
  </header>
  <main id="main-content">
    <slot />
  </main>
  <footer class="site-footer">
    <div class="container footer-inner">
      <div class="footer-about">
        <a class="footer-logo" href="/"><img src="/myca-logo.svg" alt="MYCA" width="112" height="42" /></a>
        <p>مجله مایکا؛ دانش کاربردی برای مراقبت مطمئن‌تر، زندگی آرام‌تر و تصمیم‌های آگاهانه‌تر خانواده‌ها.</p>
      </div>
      <div class="footer-links">
        <strong>دسترسی سریع</strong>
        <a href="/">صفحه نخست</a>
        <a href="/category/elder-care">مراقبت سالمند</a>
        <a href="/category/child-family">کودک و خانواده</a>
      </div>
      <div class="footer-links">
        <strong>از مایکا بیشتر بدانید</strong>
        <a href="/category/equipment">راهنمای تجهیزات</a>
        <a href="/category/caregiver-skills">آموزش مراقبین</a>
        <a href={appUrl} target="_blank" rel="noopener noreferrer">درخواست خدمات مایکا</a>
      </div>
      <div class="footer-contact">
        <strong>کنار شما هستیم</strong>
        <p>برای دریافت خدمات مراقبتی از اپلیکیشن مایکا استفاده کنید.</p>
        <a href={appUrl} class="footer-app" target="_blank" rel="noopener noreferrer"><Headphones size={18}/> ورود به مایکا </a>
      </div>
    </div>
    <div class="container footer-bottom"><span>© MYCA ۱۴۰۵ — تمامی حقوق محفوظ است.</span><span>با مراقبت، برای زندگی</span></div>
  </footer>
</div>
