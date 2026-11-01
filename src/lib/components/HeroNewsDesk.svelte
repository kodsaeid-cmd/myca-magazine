<script>
  import { onMount } from 'svelte';
  import { fade, fly } from 'svelte/transition';
  import { Clock3, Pause, Play } from '@lucide/svelte';
  import { categoryName } from '$lib/content.js';
  import { imageFor } from '$lib/images.js';

  export let items = [];

  let active = 0;
  let paused = false;
  let manuallySelected = false;
  let reducedMotion = false;
  let interval;

  $: current = items[active] || items[0];
  $: playIsPaused = paused || manuallySelected || reducedMotion;

  function show(index, manual = false) {
    if (index < 0 || index >= items.length) return;
    active = index;
    if (manual) manuallySelected = true;
  }

  function togglePlayback() {
    if (manuallySelected) {
      manuallySelected = false;
      paused = false;
    } else {
      paused = !paused;
    }
  }

  onMount(() => {
    reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    interval = window.setInterval(() => {
      if (!paused && !manuallySelected && !reducedMotion && items.length > 1) {
        active = (active + 1) % items.length;
      }
    }, 6500);
    return () => window.clearInterval(interval);
  });
</script>

<section
  class="news-desk"
  aria-label="تازه‌ترین مطالب مجله مایکا"
  onmouseenter={() => paused = true}
  onmouseleave={() => paused = false}
  onfocusin={() => paused = true}
  onfocusout={(event) => { if (!event.currentTarget.contains(event.relatedTarget)) paused = false; }}
>
  <div class="news-stage">
    {#if current}
      {#key current.slug}
        <img
          class="news-stage-photo"
          src={imageFor(current.image, 1900, 90)}
          alt={current.imageAlt}
          fetchpriority={active === 0 ? 'high' : 'auto'}
          decoding="async"
          in:fade={{ duration: 430 }}
        />
        <div class="news-stage-shade"></div>
        <div class="news-stage-copy" in:fly={{ y: 16, duration: 370, delay: 75 }}>
          <span class="news-category">{categoryName(current.category)}</span>
          <h1>{current.title}</h1>
          <p>{current.excerpt}</p>
          <div class="news-stage-footer">
            <a class="news-read" href={'/article/' + current.slug}>مطالعه مقاله</a>
            <span class="news-readtime"><Clock3 size={15} aria-hidden="true" /> {current.minutes} دقیقه مطالعه</span>
          </div>
        </div>
      {/key}
    {/if}
    <div class="news-stage-top" aria-hidden="true">
      <span class="news-stage-brand">مایکا، همراه زندگی</span>
      <span class="news-stage-index">{String(active + 1).padStart(2, '0')} / {String(items.length).padStart(2, '0')}</span>
    </div>
  </div>

  <aside class="news-desk-sidebar">
    <header class="news-desk-heading">
      <div>
        <span class="news-live-dot" aria-hidden="true"></span>
        <span class="news-desk-subtitle">مجله مایکا</span>
        <h2>تازه‌ترین مطالب</h2>
      </div>
      <button class="news-play-toggle" onclick={togglePlayback} aria-label={playIsPaused ? 'پخش خودکار مطالب' : 'توقف پخش خودکار مطالب'} title={playIsPaused ? 'پخش خودکار' : 'توقف پخش خودکار'}>
        {#if playIsPaused}<Play size={16} fill="currentColor"/>{:else}<Pause size={16} fill="currentColor"/>{/if}
      </button>
    </header>
    <div class="news-desk-items" aria-label="انتخاب مطلب برجسته">
      {#each items as item, index (item.slug)}
        <button
          type="button"
          class:news-item-active={active === index}
          class="news-item"
          aria-pressed={active === index}
          onclick={() => show(index, true)}
          onmouseenter={() => show(index, false)}
          onfocus={() => show(index, false)}
        >
          <span class="news-item-picture">
            <img src={imageFor(item.image, 320, 85)} alt="" loading={index === 0 ? 'eager' : 'lazy'} decoding="async"/>
          </span>
          <span class="news-item-text">
            <small>{categoryName(item.category)}</small>
            <strong>{item.title}</strong>
            <span class="news-item-meta">{item.minutes} دقیقه مطالعه</span>
          </span>
          {#if active === index}
            <span class:progress-paused={playIsPaused} class="news-item-progress" aria-hidden="true"></span>
          {/if}
        </button>
      {/each}
    </div>
  </aside>
</section>
