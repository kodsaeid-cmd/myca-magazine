<script>
  import { onMount } from 'svelte';
  import {
    GripVertical, MoveUp, MoveDown, Eye, EyeOff, Save, Globe, RotateCcw,
    LayoutTemplate, Monitor, Smartphone, Plus, X, ExternalLink, RefreshCw
  } from '@lucide/svelte';
  import '$lib/styles/editorial-layout.css';

  export let request;
  export let categories = {};

  const templates = {
    hero: 'هرو / مقالات شاخص',
    categories: 'شبکه دسته‌بندی‌ها',
    editors: 'انتخاب سردبیر',
    latest: 'کارت‌های جدیدترین مطالب',
    banner: 'بنر خدمات مایکا'
  };
  let layout = null;
  let articles = [];
  let history = [];
  let version = 0;
  let publishedVersion = 0;
  let publishedAt = null;
  let dirty = false;
  let busy = false;
  let loading = true;
  let message = '';
  let error = '';
  let picker = {};
  let categoryPicker = {};
  let device = 'desktop';
  let draggedSection = null;
  let draggedArticle = null;

  $: articleMap = Object.fromEntries(articles.map((a) => [Number(a.id), a]));
  $: sections = layout?.sections || [];

  onMount(() => { loadLayout(); });

  async function loadLayout() {
    busy = true; error = ''; message = '';
    try {
      const [a,h] = await Promise.all([request('layout'),request('layout-history')]);
      layout = a.layout;
      articles = a.articles || [];
      version = a.version || 0;
      publishedVersion = a.published_version || 0;
      publishedAt = a.published_at || null;
      history = h.history || [];
      dirty = false;
    } catch(e) {error = e.message;}
    finally {loading = false;busy = false;}
  }
  function assign(next) {
    layout = { ...layout, sections: next };
    dirty = true;
    message = '';
  }
  function change(index, prop, value) {
    assign(sections.map((s,i)=>i===index?{...s,[prop]:value}:s));
  }
  function moveSection(from,to) {
    if(from===null||from<0||to<0||from>=sections.length||to>=sections.length||from===to)return;
    const next=[...sections];const [element]=next.splice(from,1);next.splice(to,0,element);
    assign(next);
  }
  function moveItem(index,from,to,prop='article_ids') {
    const items=[...(sections[index][prop]||[])];
    if(from===null||from<0||to<0||from>=items.length||to>=items.length||from===to)return;
    const [item]=items.splice(from,1);items.splice(to,0,item);
    change(index,prop,items);
  }
  function addArticle(index) {
    const id=Number(picker[sections[index].id]||0);
    if(!articleMap[id])return;
    const list=sections[index].article_ids||[];
    if(list.includes(id))return;
    change(index,'article_ids',[...list,id]);
    picker={...picker,[sections[index].id]:''};
  }
  function removeArticle(index,id) {
    change(index,'article_ids',sections[index].article_ids.filter(v=>v!==id));
  }
  function addCategory(index) {
    const slug=categoryPicker[sections[index].id];
    if(!slug||!categories[slug]||sections[index].category_slugs.includes(slug))return;
    change(index,'category_slugs',[...sections[index].category_slugs,slug]);
    categoryPicker={...categoryPicker,[sections[index].id]:''};
  }
  async function save() {
    if(!layout||busy)return;
    busy=true; error='';message='';
    try {
      const r=await request('layout-save',{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({version,layout})});
      version=r.version;dirty=false;message='پیش‌نویس چیدمان در MySQL ذخیره شد. برای نمایش عمومی، «انتشار ویترین» را بزنید.';
    } catch(e){error=e.message;}
    finally{busy=false;}
  }
  async function publish() {
    if(!layout||dirty||busy)return;
    if(!confirm('چیدمان ذخیره‌شده در صفحه اصلی مجله جایگزین خواهد شد. تأیید می‌کنید؟'))return;
    busy=true;error='';message='';
    try {
      const r=await request('layout-publish',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({version})});
      publishedVersion=r.published_version;publishedAt=new Date().toISOString();
      message='ویترین جدید منتشر شد. صفحه اصلی پس از حداکثر ۹۰ ثانیه از کش به‌روزرسانی می‌شود.';
      history=(await request('layout-history')).history;
    } catch(e){error=e.message;}
    finally{busy=false;}
  }
  async function restore(id) {
    if(busy||!confirm('این نسخه به‌عنوان پیش‌نویس بازیابی شود؟ برای انتشار مجدد باید دکمه انتشار را بزنید.'))return;
    busy=true;error='';message='';
    try {
      const r=await request('layout-restore',{method:'POST',headers:{'Content-Type':'application/json'},
        body:JSON.stringify({history_id:id,version})});
      version=r.version;layout=r.layout;dirty=false;
      message='چیدمان قدیمی به‌عنوان پیش‌نویس بازیابی شد. نسخه عمومی هنوز تغییر نکرده است.';
    } catch(e){error=e.message;}
    finally{busy=false;}
  }
  function onSectionDrop(event,target) {
    event.preventDefault();
    moveSection(draggedSection,target);
    draggedSection=null;
  }
  function onArticleDrop(event,index,target) {
    event.preventDefault();event.stopPropagation();
    if(draggedArticle?.section===index)moveItem(index,draggedArticle.position,target);
    draggedArticle=null;
  }
</script>

<div class="els" dir="rtl">
  <div class="els-topbar">
    <div><span class="els-overline">MYCA EDITORIAL LAYOUT STUDIO</span>
      <h2>چیدمان و ویترین مجله</h2>
      <p>بخش‌ها و مقالات را با کشیدن، رها کردن یا کلیدهای جابه‌جایی مرتب کنید؛ سپس نسخه جدید را منتشر کنید.</p>
    </div>
    <div class="els-actions">
      <button class="els-secondary" onclick={loadLayout} disabled={busy}><RefreshCw size={16}/> بازخوانی</button>
      <button class="els-secondary" onclick={save} disabled={busy||!dirty}><Save size={16}/> ذخیره پیش‌نویس</button>
      <button class="els-primary" onclick={publish} disabled={busy||dirty||!layout}><Globe size={16}/> انتشار ویترین</button>
    </div>
  </div>

  {#if error}<div class="els-alert els-error" role="alert">{error}</div>{/if}
  {#if message}<div class="els-alert els-success" role="status">{message}</div>{/if}
  {#if dirty}<p class="els-unsaved">تغییرات ذخیره نشده‌اند. قبل از پیش‌نمایش سرور، ذخیره را انجام دهید.</p>{/if}
  <div class="els-status">
    <span>نسخه پیش‌نویس: {version}</span>
    <span>نسخه منتشرشده: {publishedVersion || 'منتشر نشده'}</span>
    {#if publishedAt}<span>آخرین انتشار: {publishedAt}</span>{/if}
  </div>

  {#if loading}
    <p class="els-empty">در حال دریافت ساختار ویترین…</p>
  {:else if layout}
    <div class="els-columns">
      <div class="els-sections">
        {#each sections as section, index (section.id)}
          <article class:els-inactive={!section.enabled} class="els-section"
            draggable="true"
            ondragstart={(e)=>{draggedSection=index;e.dataTransfer.effectAllowed='move';}}
            ondragover={(e)=>e.preventDefault()}
            ondrop={(e)=>onSectionDrop(e,index)}
            ondragend={()=>draggedSection=null}>
            <div class="els-section-head">
              <div class="els-grip" title="برای جابه‌جایی بخش بکشید"><GripVertical size={19}/></div>
              <div class="els-section-label">
                <small>{templates[section.id]} · بخش {index+1}</small>
                <strong>{section.title || templates[section.id]}</strong>
              </div>
              <div class="els-section-actions">
                <button title="بالاتر" aria-label="جابه‌جایی بخش به بالا" disabled={index===0} onclick={()=>moveSection(index,index-1)}><MoveUp size={17}/></button>
                <button title="پایین‌تر" aria-label="جابه‌جایی بخش به پایین" disabled={index===sections.length-1} onclick={()=>moveSection(index,index+1)}><MoveDown size={17}/></button>
                <button class:els-enabled={section.enabled} title="نمایش یا مخفی کردن" aria-label="نمایش یا مخفی کردن بخش" onclick={()=>change(index,'enabled',!section.enabled)}>
                  {#if section.enabled}<Eye size={17}/>{:else}<EyeOff size={17}/>{/if}
                </button>
              </div>
            </div>

            <div class="els-section-body">
              <label class="els-field">عنوان بخش
                <input value={section.title} maxlength="85" oninput={(e)=>change(index,'title',e.currentTarget.value)}/>
              </label>
              {#if ['hero','editors','latest'].includes(section.id)}
                <div class="els-field-grid">
                  <label class="els-field">نوع چینش
                    <select value={section.mode} onchange={(e)=>change(index,'mode',e.currentTarget.value)}>
                      <option value="auto">خودکار + مقالات تثبیت‌شده</option>
                      <option value="manual">کاملاً دستی</option>
                    </select>
                  </label>
                  <label class="els-field">تعداد کارت
                    <input type="number" min="1" max={section.id==='hero'?4:section.id==='editors'?3:12}
                      value={section.limit} onchange={(e)=>change(index,'limit',Number(e.currentTarget.value))}/>
                  </label>
                </div>
                {#if section.mode==='auto'}
                  <label class="els-field">فیلتر خودکار
                    <select value={section.category_slug} onchange={(e)=>change(index,'category_slug',e.currentTarget.value)}>
                      <option value="">همه دسته‌ها، جدیدترین ابتدا</option>
                      {#each Object.entries(categories) as [slug,name]}
                        <option value={slug}>{name}</option>
                      {/each}
                    </select>
                  </label>
                {/if}
                <div class="els-picker">
                  <select aria-label="انتخاب مقاله برای تثبیت" value={picker[section.id]||''}
                    onchange={(e)=>picker={...picker,[section.id]:e.currentTarget.value}}>
                    <option value="">انتخاب مقاله منتشرشده…</option>
                    {#each articles as a (a.id)}<option value={a.id}>{a.title}</option>{/each}
                  </select>
                  <button disabled={!picker[section.id]} onclick={()=>addArticle(index)}><Plus size={16}/> افزودن</button>
                </div>
                <small class="els-help">{section.mode==='auto'?'این مقالات اول قرار می‌گیرند و بقیه جایگاه‌ها با تازه‌ترین مقالات پر می‌شود.':'فقط مقالات انتخابی زیر نمایش داده می‌شوند.'}</small>
                <div class="els-pins">
                  {#each section.article_ids as id,position (id)}
                    <div class="els-pin" draggable="true"
                      ondragstart={(e)=>{e.stopPropagation();draggedArticle={section:index,position};e.dataTransfer.effectAllowed='move';}}
                      ondragover={(e)=>{e.stopPropagation();e.preventDefault();}}
                      ondrop={(e)=>onArticleDrop(e,index,position)}
                      ondragend={()=>draggedArticle=null}>
                      <GripVertical size={16}/><span>{position+1}. {articleMap[id]?.title || 'مقاله حذف‌شده'}</span>
                      <button aria-label="بالاتر" disabled={position===0} onclick={()=>moveItem(index,position,position-1)}><MoveUp size={15}/></button>
                      <button aria-label="پایین‌تر" disabled={position===section.article_ids.length-1} onclick={()=>moveItem(index,position,position+1)}><MoveDown size={15}/></button>
                      <button aria-label="حذف از بخش" onclick={()=>removeArticle(index,id)}><X size={15}/></button>
                    </div>
                  {:else}
                    <p class="els-help">مقاله تثبیت‌شده‌ای انتخاب نشده است.</p>
                  {/each}
                </div>
              {:else if section.id==='categories'}
                <p class="els-help">دسته‌بندی‌ها را انتخاب کنید و ترتیب نمایش را تغییر دهید.</p>
                <div class="els-picker">
                  <select aria-label="افزودن دسته‌بندی" value={categoryPicker[section.id]||''} onchange={(e)=>categoryPicker={...categoryPicker,[section.id]:e.currentTarget.value}}>
                    <option value="">انتخاب دسته‌بندی…</option>
                    {#each Object.entries(categories) as [slug,name]}<option value={slug}>{name}</option>{/each}
                  </select>
                  <button onclick={()=>addCategory(index)} disabled={!categoryPicker[section.id]}><Plus size={16}/> افزودن</button>
                </div>
                <div class="els-pins">
                  {#each section.category_slugs as slug,position (slug)}
                    <div class="els-pin"><span>{position+1}. {categories[slug]||slug}</span>
                      <button aria-label="بالاتر" disabled={position===0} onclick={()=>moveItem(index,position,position-1,'category_slugs')}><MoveUp size={15}/></button>
                      <button aria-label="پایین‌تر" disabled={position===section.category_slugs.length-1} onclick={()=>moveItem(index,position,position+1,'category_slugs')}><MoveDown size={15}/></button>
                      <button aria-label="حذف" onclick={()=>change(index,'category_slugs',section.category_slugs.filter(x=>x!==slug))}><X size={15}/></button>
                    </div>
                  {/each}
                </div>
              {:else}
                <p class="els-help">بنر دعوت به خدمات مایکا با شماره رسمی ۴۱۱۲۴. می‌توانید عنوان و محل قرارگیری آن را تغییر دهید.</p>
              {/if}
            </div>
          </article>
        {/each}
      </div>

      <aside class="els-preview-panel">
        <div class="els-preview-title"><div><h3>پیش‌نمایش چیدمان</h3><small>نمای شماتیک تغییرات کنونی</small></div>
          <div class="els-switch">
            <button class:active={device==='desktop'} title="دسکتاپ" onclick={()=>device='desktop'}><Monitor size={17}/></button>
            <button class:active={device==='mobile'} title="موبایل" onclick={()=>device='mobile'}><Smartphone size={17}/></button>
          </div>
        </div>
        <div class:els-mobile={device==='mobile'} class="els-preview-frame">
          <div class="els-frame-header"><img src="/myca-logo.svg" alt="مایکا" width="70"/><span>مجله مایکا</span></div>
          {#each sections.filter(s=>s.enabled) as sec (sec.id)}
            <div class="els-preview-section">
              <strong>{sec.title}</strong>
              {#if ['hero','latest','editors'].includes(sec.id)}
                <div class:els-preview-hero={sec.id==='hero'} class="els-preview-cards">
                  {#each sec.article_ids.slice(0,sec.limit) as id (id)}
                    <div class="els-preview-card">{articleMap[id]?.title||'مقاله'}</div>
                  {/each}
                  {#if sec.mode==='auto'}<div class="els-preview-card els-auto">مقالات جدید به‌صورت خودکار…</div>{/if}
                </div>
              {:else if sec.id==='categories'}
                <div class="els-preview-categories">{#each sec.category_slugs as slug (slug)}<span>{categories[slug]||slug}</span>{/each}</div>
              {:else}
                <div class="els-preview-banner">خدمات و پشتیبانی MYCA · ۴۱۱۲۴</div>
              {/if}
            </div>
          {/each}
        </div>
        <p class="els-help">برای دیدن HTML واقعیِ سرور، ابتدا پیش‌نویس را ذخیره کرده و از پیش‌نمایش خصوصی استفاده کنید.</p>
        <a class="els-preview-link" href="/editorial-preview/" target="_blank" rel="noopener noreferrer"><ExternalLink size={16}/> مشاهده پیش‌نمایش واقعی</a>
        <a class="els-preview-link els-public-link" href="/" target="_blank" rel="noopener noreferrer"><Eye size={16}/> مشاهده نسخه منتشرشده</a>
        <div class="els-history">
          <h3>تاریخچه انتشار</h3>
          {#each history as rev (rev.id)}
            <div class="els-history-row"><div><strong>نسخه {rev.version}</strong><small>{rev.created_at} · {rev.publisher}</small></div>
              <button onclick={()=>restore(rev.id)} disabled={busy}><RotateCcw size={15}/> بازیابی</button></div>
          {:else}<p class="els-help">هنوز نسخه منتشرشده‌ای وجود ندارد.</p>{/each}
        </div>
      </aside>
    </div>
  {:else}<div class="els-empty">چیدمان بارگذاری نشد. اتصال دیتابیس و مهاجرت SQL را بررسی کنید.</div>{/if}
</div>
