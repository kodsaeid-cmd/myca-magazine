<script>
  import { onMount, onDestroy, tick } from 'svelte';
  import {
    LayoutDashboard, FileText, Image as ImageIcon, Search, LogOut, Plus, Save, Globe,
    ArrowRight, Eye, EyeOff, Upload, RefreshCw, Bold, Italic, Heading2, Heading3,
    List, ListOrdered, Quote, Undo2, Redo2, Table2, Link2, CheckCircle2,
    Clock3, ShieldCheck, ExternalLink, LockKeyhole, BookOpen, X, Menu, Users, LayoutTemplate
  } from '@lucide/svelte';
  import '$lib/styles/admin.css';
  import EditorialLayoutStudio from '$lib/components/EditorialLayoutStudio.svelte';

  const endpoint = '/api/index.php?route=';
  const empty = () => ({
    id: 0, title: '', slug: '', excerpt: '', body_html: '<p></p>',
    category_slug: 'elder-care', status: 'draft', cover_url: '', cover_alt: '',
    meta_title: '', meta_description: '', canonical_url: '', robots: 'index,follow',
    focus_keyword: '', faq_json: [], featured: false
  });
  const statusNames = { draft: 'پیش‌نویس', review: 'در انتظار بازبینی', published: 'منتشرشده', archived: 'بایگانی' };
  let initialized = false, loading = true, user = null, csrf = '', serviceError = '';
  let authMode = 'login', authEmail = '', authPassword = '', authName = '', setupToken = '';
  let view = 'dashboard', navOpen = false, busy = false, notice = '', error = '';
  let articles = [], media = [], counts = {}, categories = {}, revisions = [], users = [];
  let newUserName = '', newUserEmail = '', newUserPassword = '', newUserRole = 'author';
  let showLoginPassword = false, showSetupToken = false, showNewUserPassword = false;
  let editUserId = 0, editUserName = '', editUserEmail = '', editUserRole = 'author', editUserActive = true;
  let editUserPassword = '', editUserCurrentPassword = '', showEditUserPassword = false, showEditCurrentPassword = false;
  let imageAudit = null, imageAuditLoading = false;
  let search = '', statusFilter = '', form = empty(), faqText = '';
  let editorHost, uploadInput, editor, editorLoading = false;
  let selectedImageAlt = '';
  $: canPublish = user && (user.role === 'admin' || user.role === 'editor');
  $: visibleArticles = articles.filter((a) =>
    (!statusFilter || a.status === statusFilter) &&
    (!search || (a.title + ' ' + a.slug).toLocaleLowerCase().includes(search.toLocaleLowerCase()))
  );
  $: articleTotal = Object.values(counts).reduce((sum, count) => sum + Number(count || 0), 0);

  async function request(route, options = {}) {
    const headers = { 'X-CSRF-Token': csrf, ...(options.headers || {}) };
    const response = await fetch(endpoint + encodeURIComponent(route) + (options.params || ''), {
      method: options.method || 'GET',
      credentials: 'same-origin',
      headers,
      body: options.body
    });
    let result;
    try { result = await response.json(); }
    catch { throw new Error('پاسخ بک‌اند قابل خواندن نیست. اتصال PHP/MySQL را بررسی کن.'); }
    if (!response.ok || result.ok !== true) throw new Error(result.error || 'درخواست ناموفق بود.');
    return result;
  }
  function clearMessages() { error = ''; notice = ''; }
  async function loadSession() {
    try {
      const r = await request('me');
      user = r.user || null;
      csrf = r.csrf;
      initialized = r.initialized;
      // The form is determined by server state. Without this, the first-admin
      // screen incorrectly submits a login request and always rejects it.
      authMode = initialized ? 'login' : 'setup';
      if (user) await loadDashboard();
    } catch (e) { serviceError = e.message; }
    finally { loading = false; }
  }
  async function authenticate() {
    clearMessages();
    busy = true;
    try {
      const body = authMode === 'setup'
        ? { email: authEmail, password: authPassword, display_name: authName, setup_token: setupToken }
        : { email: authEmail, password: authPassword };
      const r = await request(authMode === 'setup' ? 'setup' : 'login', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body)
      });
      if (authMode === 'setup') {
        initialized = true; authMode = 'login'; authPassword = ''; setupToken = '';
        notice = 'مدیر اولیه ساخته شد. اکنون با رمز خودت وارد شو و کلید راه‌اندازی را در فایل تنظیمات سرور عوض کن.';
      } else {
        user = r.user;
        csrf = r.csrf;
        authPassword = '';
        await loadDashboard();
      }
    } catch (e) { error = e.message; }
    finally { busy = false; }
  }
  async function logout() {
    try { await request('logout', { method: 'POST' }); }
    catch (e) { error = e.message; }
    user = null; counts = {}; articles = []; view = 'dashboard';
    await loadSession();
  }
  async function loadDashboard() {
    try {
      const [m, c] = await Promise.all([request('metrics'), request('categories')]);
      counts = Object.fromEntries(m.counts.map((x) => [x.status, Number(x.count)]));
      categories = c.categories;
    } catch (e) { error = e.message; }
  }
  async function loadArticles() {
    try { articles = (await request('articles')).articles; }
    catch (e) { error = e.message; }
  }
  async function installMoreEditorialArticles() {
    clearMessages();
    if (busy || user?.role !== 'admin') return;
    if (!confirm('۱۰ مقاله جدید به مجله اضافه شود و سکشن تازه‌ترین مطالب برای نمایش تا ۱۳ مقاله گسترش یابد؟ محتوای قبلی حذف نمی‌شود.')) return;
    busy = true;
    try {
      const r = await request('editorial-more-install', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ confirm_install: true, expand_layout: true })
      });
      notice = '۱۰ مقاله جدید بررسی و نصب شدند: ' + r.result.created +
        ' مقاله افزوده و ' + r.result.already_present + ' مقاله از قبل موجود بود.' +
        (r.result.layout_published ? ' ویترین ۲۰ مقاله‌ای به‌روزرسانی شد.' : '');
      await loadDashboard();
      await loadArticles();
    } catch(e) { error = e.message; }
    finally { busy = false; }
  }
  async function installEditorialArticles() {
    clearMessages();
    if (busy || user?.role !== 'admin') return;
    if (!confirm('۱۰ مقاله تخصصی با تصاویر شاخص و تنظیمات سئو به دیتابیس افزوده و ویترین فعلی مجله با آن‌ها جایگزین می‌شود. مقاله‌های موجود پاک یا بازنویسی نخواهند شد. ادامه می‌دهید؟')) return;
    busy = true;
    try {
      const r = await request('editorial-pack-install', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ confirm_install: true, replace_layout: true })
      });
      notice = 'بسته تحریریه مایکا نصب و ویترین منتشر شد: ' +
        r.result.created + ' مقاله جدید و ' + r.result.already_present + ' مقاله موجود. ' +
        'تصاویر را در مسیر /media/editorial/ بررسی کنید.';
      await loadDashboard();
      await loadArticles();
    } catch (e) { error = e.message; }
    finally { busy = false; }
  }
  async function loadMedia() {
    try { media = (await request('media')).media; }
    catch (e) { error = e.message; }
  }
  async function loadUsers() {
    try { users = (await request('users')).users; }
    catch (e) { error = e.message; }
  }
  function publicImageUrl(url) {
    if (typeof url !== 'string') return '';
    const safe = /^\/(?:media\/(?:editorial|editorial-more|editorial-inline|uploads)|media-check)\/[A-Za-z0-9_./-]+\.(?:jpe?g|png|webp)$/.test(url);
    return safe && !url.includes('..') ? '/api/image.php?p=' + encodeURIComponent(url) : url;
  }
  async function installAllInlineImages() {
    if (busy || user?.role !== 'admin') return;
    clearMessages();
    if (!confirm('افزودن ۳ تصویر موضوعی به هر یک از ۲۰ مقاله اصلی مجله؟ تصاویر فعلی و متن‌ها حفظ می‌شوند و پیش از تغییر، نسخه پشتیبان مقاله ذخیره خواهد شد.')) return;
    busy = true;
    try {
      const payload = { confirm_install: true, dry_run: true };
      const pre = await request('editorial-visuals-install', {
        method: 'POST', headers: { 'Content-Type':'application/json' }, body: JSON.stringify(payload)
      });
      if (pre.result.updated_articles === 0) {
        notice = 'همه مقالات موجود از قبل عکس در متن دارند یا در بسته ۲۰ مقاله اصلی نیستند؛ تغییری لازم نبود.';
      } else {
        const done = await request('editorial-visuals-install', {
          method: 'POST', headers: { 'Content-Type':'application/json' },
          body: JSON.stringify({ confirm_install: true, dry_run: false })
        });
        notice = done.result.updated_articles + ' مقاله با ' + done.result.images_added +
          ' تصویر درون‌متنی به‌روزرسانی شدند. نسخه قبلی مقالات در تاریخچه ویرایش محفوظ است.';
      }
      imageAudit = await request('media-audit');
    } catch(e) { error = e.message; }
    finally { busy = false; }
  }
  async function auditArticleImages() {
    clearMessages(); imageAuditLoading = true;
    try { imageAudit = await request('media-audit'); }
    catch(e) { error = e.message; }
    finally { imageAuditLoading = false; }
  }
  function beginUserEdit(item) {
    clearMessages();
    editUserId = Number(item.id);
    editUserName = item.display_name;
    editUserEmail = item.email;
    editUserRole = item.role;
    editUserActive = Number(item.active) === 1;
    editUserPassword = '';
    editUserCurrentPassword = '';
    showEditUserPassword = false;
    showEditCurrentPassword = false;
  }
  function cancelUserEdit() {
    editUserId = 0;
    editUserPassword = '';
    editUserCurrentPassword = '';
    showEditUserPassword = false;
    showEditCurrentPassword = false;
  }
  async function saveUserEdit() {
    if (!editUserId || busy) return;
    clearMessages(); busy = true;
    try {
      const updated = await request('user-edit', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          id: editUserId, display_name: editUserName, email: editUserEmail,
          role: editUserRole, active: editUserActive,
          new_password: editUserPassword,
          current_password: editUserCurrentPassword
        })
      });
      if (Number(editUserId) === Number(user.id)) {
        user = { ...user, display_name: editUserName, email: editUserEmail };
      }
      if (updated.password_changed && Number(editUserId) === Number(user.id)) {
        cancelUserEdit();
        await logout();
        notice = 'رمز حساب مدیر تغییر کرد. نشست قبلی باطل شد؛ با رمز جدید دوباره وارد شو.';
        return;
      }
      notice = updated.password_changed ? 'اطلاعات و رمز جدید کاربر ذخیره شد؛ نشست‌های قبلی او باطل شدند.' : 'اطلاعات کاربر به‌روزرسانی شد.';
      cancelUserEdit();
      await loadUsers();
    } catch(e) { error = e.message; }
    finally { busy = false; }
  }
  async function createUser() {
    clearMessages();busy = true;
    try {
      await request('user-create', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ display_name: newUserName, email: newUserEmail, password: newUserPassword, role: newUserRole })
      });
      newUserName = ''; newUserEmail = ''; newUserPassword = ''; newUserRole = 'author'; showNewUserPassword = false;
      notice = 'کاربر جدید در پایگاه داده ایجاد شد.';
      await loadUsers();
    } catch (e) { error = e.message; }
    finally { busy = false; }
  }
  async function updateUser(id, role, active) {
    clearMessages();busy=true;
    try {
      await request('user-update', { method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id, role, active }) });
      notice='دسترسی کاربر به‌روزرسانی شد.';
      await loadUsers();
    } catch(e) { error=e.message; await loadUsers(); }
    finally { busy=false; }
  }
  function teardownEditor() {
    if (editor) { editor.destroy(); editor = null; }
  }
  async function openView(next) {
    clearMessages();
    if (view === 'editor' && next !== 'editor') teardownEditor();
    view = next; navOpen = false;
    if (next !== 'users') cancelUserEdit();
    if (next === 'articles') await loadArticles();
    if (next === 'media') await loadMedia();
    if (next === 'users' && user?.role === 'admin') await loadUsers();
    if (next === 'dashboard') await loadDashboard();
  }
  async function openNew() {
    clearMessages(); teardownEditor(); form = empty(); faqText = ''; revisions = [];
    view = 'editor'; navOpen = false;
    await tick(); await startEditor();
  }
  async function openArticle(id) {
    clearMessages(); busy = true;
    try {
      const { article } = await request('article', { params: '&id=' + encodeURIComponent(id) });
      teardownEditor();
      form = { ...empty(), ...article, featured: Number(article.featured || 0) === 1 };
      faqText = (article.faq_json || []).map((f) => f.question + ' | ' + f.answer).join('\n');
      revisions = [];
      view = 'editor'; navOpen = false;
      await tick(); await startEditor();
    } catch (e) { error = e.message; }
    finally { busy = false; }
  }
  async function startEditor() {
    if (!editorHost) return;
    editorLoading = true;
    try {
      const [
        { Editor }, { default: StarterKit }, { default: Underline },
        { default: Link }, { default: Image }, { default: Table },
        { default: TableRow }, { default: TableHeader }, { default: TableCell },
        { default: TextAlign }
      ] = await Promise.all([
        import('@tiptap/core'), import('@tiptap/starter-kit'),
        import('@tiptap/extension-underline'), import('@tiptap/extension-link'),
        import('@tiptap/extension-image'), import('@tiptap/extension-table'),
        import('@tiptap/extension-table-row'), import('@tiptap/extension-table-header'),
        import('@tiptap/extension-table-cell'), import('@tiptap/extension-text-align')
      ]);
      editor = new Editor({
        element: editorHost,
        extensions: [
          StarterKit, Underline,
          Link.configure({ openOnClick: false, HTMLAttributes: { rel: 'noopener noreferrer' } }),
          Image.configure({ allowBase64: false }),
          Table.configure({ resizable: false }), TableRow, TableHeader, TableCell,
          TextAlign.configure({ types: ['paragraph', 'heading'] })
        ],
        editorProps: { attributes: { class: 'cms-prosemirror', dir: 'rtl', spellcheck: 'true' } },
        content: form.body_html || '<p></p>',
        onUpdate: ({ editor: instance }) => { form.body_html = instance.getHTML(); }
      });
    } catch (e) { error = 'ویرایشگر بارگذاری نشد: ' + e.message; }
    finally { editorLoading = false; }
  }
  function command(type) {
    if (!editor) return;
    const chain = editor.chain().focus();
    if (type === 'bold') chain.toggleBold().run();
    if (type === 'italic') chain.toggleItalic().run();
    if (type === 'underline') chain.toggleUnderline().run();
    if (type === 'h2') chain.toggleHeading({ level: 2 }).run();
    if (type === 'h3') chain.toggleHeading({ level: 3 }).run();
    if (type === 'bullets') chain.toggleBulletList().run();
    if (type === 'numbers') chain.toggleOrderedList().run();
    if (type === 'quote') chain.toggleBlockquote().run();
    if (type === 'undo') chain.undo().run();
    if (type === 'redo') chain.redo().run();
    if (type === 'table') chain.insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run();
    if (type === 'link') {
      const old = editor.getAttributes('link').href || '';
      const url = window.prompt('آدرس کامل لینک (https://...)', old);
      if (url === null) return;
      if (!url) chain.unsetLink().run();
      else if (/^https:\/\//i.test(url) || (url.startsWith('/') && !url.startsWith('//'))) chain.setLink({ href: url }).run();
      else error = 'فقط لینک https یا مسیر داخلی مجاز است.';
    }
  }
  function parseFaq() {
    return faqText.split('\n').map((line) => {
      const pos = line.indexOf('|');
      return pos === -1 ? null : { question: line.slice(0, pos).trim(), answer: line.slice(pos + 1).trim() };
    }).filter((x) => x && x.question && x.answer).slice(0, 20);
  }
  function slugFromTitle() {
    if (!form.slug && form.title) notice = 'برای آدرس مقاله، یک Slug انگلیسی مانند elderly-home-care وارد کن.';
  }
  async function saveArticle(status) {
    clearMessages(); busy = true;
    try {
      const payload = { ...form, status, body_html: editor?.getHTML() || form.body_html, faq_json: parseFaq() };
      const result = await request('save', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
      });
      form.id = result.id; form.status = result.status;
      notice = result.status === 'published' ? 'مقاله واقعاً منتشر شد؛ لینک عمومی قابل مشاهده است.' : 'مقاله در پایگاه داده ذخیره شد.';
      await loadDashboard();
      await loadArticles();
    } catch (e) { error = e.message; }
    finally { busy = false; }
  }
  async function listRevisions() {
    if (!form.id) return;
    try { revisions = (await request('revisions', { params: '&id=' + form.id })).revisions; }
    catch (e) { error = e.message; }
  }
  async function restoreRevision(revisionId) {
    if (!window.confirm('نسخه انتخابی بازیابی و مقاله به پیش‌نویس تبدیل شود؟')) return;
    try {
      await request('restore', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: form.id, revision_id: revisionId }) });
      await openArticle(form.id);
      notice = 'نسخه قبلی بازیابی شد؛ انتشار دوباره نیاز به تأیید دارد.';
    } catch (e) { error = e.message; }
  }
  async function uploadFile(e) {
    const file = e.currentTarget.files?.[0];
    if (!file) return;
    clearMessages(); busy = true;
    try {
      const body = new FormData();
      body.append('file', file);
      body.append('alt', selectedImageAlt || form.cover_alt || '');
      const r = await request('upload', { method: 'POST', body });
      if (view === 'editor') {
        if (!form.cover_url) form.cover_url = r.path;
        if (editor) editor.chain().focus().setImage({ src: r.path, alt: r.alt }).run();
      }
      await loadMedia();
      notice = 'تصویر روی سرور ذخیره شد.';
    } catch (e) { error = e.message; }
    finally { busy = false; if (uploadInput) uploadInput.value = ''; }
  }
  function newTab() { window.open('/stories/' + form.slug + '/', '_blank', 'noopener,noreferrer'); }

  onMount(loadSession);
  onDestroy(teardownEditor);
</script>

<svelte:head>
  <title>MYCA Content Studio | مدیریت مجله مایکا</title>
  <meta name="robots" content="noindex,nofollow" />
  <meta name="description" content="پنل مدیریت خصوصی مجله مایکا" />
</svelte:head>

<div class="cms-shell" dir="rtl">
  {#if loading}
    <div class="cms-auth-screen"><div class="cms-auth-card"><img src="/myca-logo.svg" alt="مایکا" width="105"><p>در حال اتصال به مرکز مدیریت محتوا…</p></div></div>
  {:else if serviceError}
    <div class="cms-auth-screen"><div class="cms-auth-card"><LockKeyhole size={38} color="#00a4d4"/><h1>راه‌اندازی بک‌اند مایکا</h1><p>اتصال واقعی PHP/MySQL هنوز آماده نیست.</p><div class="cms-error">{serviceError}</div><p>ابتدا پایگاه داده و فایل تنظیمات خصوصی سرور را مطابق راهنمای CMS تنظیم کنید.</p><button onclick={() => { serviceError = ''; loading = true; loadSession(); }}>تلاش مجدد</button></div></div>
  {:else if !user}
    <div class="cms-auth-screen">
      <form class="cms-auth-card" onsubmit={(e) => { e.preventDefault(); authenticate(); }}>
        <img src="/myca-logo.svg" alt="مایکا" width="112">
        <span class="cms-overline">MYCA · CONTENT STUDIO</span>
        <h1>{!initialized ? 'ایجاد مدیر اولیه' : 'ورود به مرکز مدیریت'}</h1>
        <p>مدیریت محتوای مجله مایکا، انتشار و سئو</p>
        {#if !initialized}
          <label>نام مدیر<input bind:value={authName} required placeholder="نام و نام خانوادگی" /></label>
          <label>کلید راه‌اندازی محرمانه
            <span class="cms-password-wrap"><input bind:value={setupToken} type={showSetupToken ? 'text' : 'password'} required autocomplete="off" placeholder="کلید فایل تنظیمات سرور" />
              <button type="button" class="cms-password-toggle" aria-label={showSetupToken ? 'مخفی‌کردن کلید' : 'نمایش کلید'} aria-pressed={showSetupToken} title={showSetupToken ? 'مخفی‌کردن' : 'نمایش'} onclick={() => showSetupToken = !showSetupToken}>{#if showSetupToken}<EyeOff size={19}/>{:else}<Eye size={19}/>{/if}</button>
            </span>
          </label>
        {/if}
        <label>ایمیل<input type="email" bind:value={authEmail} required autocomplete="username" placeholder="email@example.com" dir="ltr" /></label>
        <label>رمز عبور
          <span class="cms-password-wrap"><input type={showLoginPassword ? 'text' : 'password'} bind:value={authPassword} required autocomplete={!initialized ? 'new-password' : 'current-password'} minlength={!initialized ? 14 : undefined} />
            <button type="button" class="cms-password-toggle" aria-label={showLoginPassword ? 'مخفی‌کردن رمز' : 'نمایش رمز'} aria-pressed={showLoginPassword} title={showLoginPassword ? 'مخفی‌کردن' : 'نمایش'} onclick={() => showLoginPassword = !showLoginPassword}>{#if showLoginPassword}<EyeOff size={19}/>{:else}<Eye size={19}/>{/if}</button>
          </span>
        </label>
        {#if error}<div class="cms-error">{error}</div>{/if}
        {#if notice}<div class="cms-success">{notice}</div>{/if}
        <button class="cms-primary full" type="submit" disabled={busy}>{busy ? 'در حال بررسی…' : !initialized ? 'ساخت مدیر اولیه' : 'ورود امن به پنل'}</button>
        <a class="cms-return" href="/">بازگشت به مجله <ArrowRight size={15}/></a>
      </form>
    </div>
  {:else}
    <div class="cms-layout">
      <aside class:open={navOpen} class="cms-sidebar">
        <div class="cms-sidebar-brand"><img src="/myca-logo.svg" alt="مایکا" width="90"><div><strong>Content Studio</strong><small>مرکز فرماندهی محتوای مایکا</small></div></div>
        <div class="cms-side-label">مدیریت مجله</div>
        <nav aria-label="منوی پنل مدیریت">
          <button class:active={view === 'dashboard'} onclick={() => openView('dashboard')}><LayoutDashboard size={19}/> داشبورد</button>
          <button class:active={view === 'articles' || view === 'editor'} onclick={() => openView('articles')}><FileText size={19}/> مدیریت مقالات</button>
          <button class:active={view === 'media'} onclick={() => openView('media')}><ImageIcon size={19}/> کتابخانه رسانه</button>
          {#if canPublish}<button class:active={view === 'layout'} onclick={() => openView('layout')}><LayoutTemplate size={19}/> چیدمان ویترین مجله</button>{/if}
          <button class:active={view === 'seo'} onclick={() => openView('seo')}><Search size={19}/> SEO Studio</button>
          {#if user.role === 'admin'}<button class:active={view === 'users'} onclick={() => openView('users')}><Users size={19}/> کاربران و دسترسی‌ها</button>{/if}
        </nav>
        <div class="cms-side-label">حساب کاربری</div>
        <div class="cms-sidebar-user"><span class="cms-avatar">{user.display_name.slice(0, 1)}</span><div><strong>{user.display_name}</strong><small>{user.role === 'admin' ? 'مدیر کل' : user.role === 'editor' ? 'سردبیر' : 'نویسنده'}</small></div></div>
        <button class="cms-logout" onclick={logout}><LogOut size={17}/> خروج امن</button>
      </aside>
      {#if navOpen}<button class="cms-backdrop" aria-label="بستن منو" onclick={() => navOpen = false}></button>{/if}
      <div class="cms-workspace">
        <header class="cms-header">
          <div class="cms-header-intro"><button class="cms-mobile-menu" aria-label="بازکردن منو" onclick={() => navOpen = !navOpen}><Menu size={22}/></button><div><span class="cms-overline">MYCA CONTENT OPERATIONS</span><h1>{view === 'dashboard' ? 'داشبورد تحریریه' : view === 'articles' ? 'مدیریت مقالات' : view === 'editor' ? (form.id ? 'ویرایش مقاله' : 'مقاله جدید') : view === 'layout' ? 'چیدمان ویترین مجله' : view === 'seo' ? 'SEO Studio' : view === 'users' ? 'کاربران و دسترسی‌ها' : 'کتابخانه رسانه'}</h1></div></div>
          <div class="cms-header-actions"><a href="/" target="_blank" rel="noopener noreferrer" class="cms-outline"><Eye size={17}/> نمایش مجله</a><button class="cms-primary" onclick={openNew}><Plus size={18}/> مقاله جدید</button></div>
        </header>
        <main class="cms-main">
          {#if error}<div class="cms-error" role="alert">{error}<button aria-label="بستن خطا" onclick={() => error = ''}><X size={16}/></button></div>{/if}
          {#if notice}<div class="cms-success" role="status">{notice}<button aria-label="بستن پیام" onclick={() => notice = ''}><X size={16}/></button></div>{/if}
          {#if view === 'dashboard'}
            <div class="cms-page-heading"><div><h2>نمای کلی عملیات محتوا</h2><p>آمار واقعی مقالات ثبت‌شده در پایگاه داده مایکا</p></div><button class="cms-outline" onclick={loadDashboard}><RefreshCw size={17}/> به‌روزرسانی</button></div>
            <div class="cms-stat-grid">
              <div class="cms-stat"><span><FileText size={19}/></span><strong>{articleTotal.toLocaleString('fa-IR')}</strong><small>کل مقالات</small></div>
              <div class="cms-stat"><span><CheckCircle2 size={19}/></span><strong>{Number(counts.published || 0).toLocaleString('fa-IR')}</strong><small>منتشرشده</small></div>
              <div class="cms-stat"><span><Clock3 size={19}/></span><strong>{Number(counts.review || 0).toLocaleString('fa-IR')}</strong><small>منتظر بازبینی</small></div>
              <div class="cms-stat"><span><BookOpen size={19}/></span><strong>{Number(counts.draft || 0).toLocaleString('fa-IR')}</strong><small>پیش‌نویس</small></div>
            </div>
            <div class="cms-panel cms-welcome"><div><span class="cms-overline">EDITORIAL WORKFLOW</span><h2>از ایده تا مقاله قابل ایندکس</h2><p>ایجاد مقاله ← ویرایش تصویری ← تنظیم SEO ← بررسی سردبیر ← انتشار HTML و Sitemap</p><button class="cms-primary" onclick={openNew}><Plus size={18}/> شروع نوشتن</button></div><div class="cms-welcome-icon"><BookOpen size={80}/></div></div>
            {#if user.role === 'admin'}
              <div class="cms-panel">
                <div class="cms-panel-head"><h3>گسترش مجله به ۲۰ مقاله</h3><button class="cms-primary" onclick={installMoreEditorialArticles} disabled={busy}><BookOpen size={18}/> افزودن ۱۰ مقاله جدید و تکمیل ویترین</button></div>
                <p>۱۰ راهنمای تازه درباره ترخیص از بیمارستان، گزارش مراقب، زخم فشاری، خواب، ارتباط اجتماعی، فعالیت سالمند، دمانس، سلامت دهان و مراقبت کودک. همه از دیتابیس قابل ویرایش‌اند؛ تصاویر از پوشه محلی سایت بارگذاری می‌شوند. این عملیات سکشن «تازه‌ترین خواندنی‌ها» را تا ۱۳ کارت گسترش می‌دهد و چیدمان قبلی را در تاریخچه نگه می‌دارد.</p>
              </div>
            {/if}
            {#if user.role === 'admin'}
              <div class="cms-panel">
                <div class="cms-panel-head"><h3>بسته ۱۰ مقاله تخصصی مایکا</h3><button class="cms-primary" onclick={installEditorialArticles} disabled={busy}><BookOpen size={18}/> نصب مقاله‌ها و جایگزینی ویترین</button></div>
                <p>مجموعه نخست مجله شامل ۱۰ راهنمای اصلی مراقبت سالمند با متن کامل، لینک به منابع معتبر، تصویر اختصاصی، متای سئو و پرسش‌های متداول است. با نصب، مقاله‌ها در MySQL منتشر می‌شوند و قابلیت ویرایششان از «مدیریت مقالات» باقی می‌ماند. ویترین فعلی با چیدمان ۴ + ۳ + ۳ مقاله جایگزین می‌شود و نسخه قبلی در تاریخچه چیدمان حفظ خواهد شد.</p>
                <p>پیش از نصب، پوشه ۱۰ تصویر را در مسیر <code>/media/editorial/</code> آپلود کن. کل دامنه آزمایشی dandoonii.ir همچنان Noindex باقی می‌ماند؛ آماده‌بودن متادیتا به معنی ایندکس‌شدن واقعی در این دامنه نیست.</p>
              </div>
            {/if}
            <div class="cms-panel">
              <div class="cms-panel-head"><h3>بررسی تصاویر مقالات</h3><button class="cms-outline" onclick={auditArticleImages} disabled={imageAuditLoading || busy}><ImageIcon size={17}/> {imageAuditLoading ? 'در حال بررسی…' : 'اسکن فایل‌های تصاویر'}</button></div>
              {#if user.role === 'admin'}
                <button class="cms-primary" onclick={installAllInlineImages} disabled={busy}><ImageIcon size={17}/> {busy ? 'در حال آماده‌سازی تصاویر…' : 'افزودن تصاویر به تمام ۲۰ مقاله اصلی'}</button>
                <p class="cms-help">با یک کلیک پس از بررسی فایل‌ها، حداکثر ۶۰ تصویر مرتبط از کتابخانه مایکا بین بخش‌های مقاله درج می‌شود. مقالات دارای تصویر فعلی بدون تغییر می‌مانند.</p>
              {/if}
              <p class="cms-help">تفاوت بین کاور مفقود روی هاست و نبود تصویر درون متن مقاله را جداگانه نشان می‌دهد.</p>
              {#if imageAudit}
                <p>از {imageAudit.total} مقاله منتشرشده، {imageAudit.missing_covers} مقاله کاور محلی گمشده/ثبت‌نشده دارند و {imageAudit.without_inline_images} مقاله تصویر درون‌متنی ندارند.</p>
                {#if imageAudit.missing_covers > 0}
                  <div class="cms-media-audit">{#each imageAudit.articles.filter((a) => !a.cover_url || (!a.cover_found && !a.cover_external)) as item (item.id)}
                    <div><strong>{item.title}</strong><code dir="ltr">{item.cover_url || 'کاور ثبت نشده'}</code></div>
                  {/each}</div>
                  <p class="cms-help">تصاویر ده مقاله نخست باید دقیقاً در /media/editorial/ روی هاست قرار بگیرند. ده کاور بعدی در /media/editorial-more/ هستند.</p>
                {/if}
              {/if}
            </div>
            <div class="cms-panel"><div class="cms-panel-head"><h3>مرکز انتشار</h3><button class="cms-link" onclick={() => openView('articles')}>فهرست مقالات <ArrowRight size={15}/></button></div><p>هر مطلب پس از انتشار با لینک مستقل در <a href="/stories/" target="_blank" rel="noopener noreferrer">بخش مقاله‌های پویا</a> و سایت‌مپ اختصاصی در دسترس است. این بخش به‌صورت HTML توسط PHP روی سرور رندر می‌شود.</p></div>
          {:else if view === 'articles'}
            <div class="cms-page-heading"><div><h2>آرشیو مقالات</h2><p>فهرست واقعی نوشته‌ها، وضعیت انتشار و آخرین ویرایش</p></div><button class="cms-primary" onclick={openNew}><Plus size={17}/> ایجاد مقاله</button></div>
            <div class="cms-panel"><div class="cms-filters"><div class="cms-search"><Search size={18}/><input bind:value={search} placeholder="عنوان یا اسلاگ را جست‌وجو کن…"/></div><select bind:value={statusFilter}><option value="">همه وضعیت‌ها</option>{#each Object.entries(statusNames) as [value, label]}<option {value}>{label}</option>{/each}</select><button class="cms-outline" onclick={loadArticles}><RefreshCw size={17}/> تازه‌سازی</button></div>
              <div class="cms-table-scroll"><table class="cms-table"><thead><tr><th>مقاله</th><th>دسته</th><th>وضعیت</th><th>آخرین ویرایش</th><th>عملیات</th></tr></thead><tbody>
                {#each visibleArticles as a (a.id)}<tr><td><div class="cms-table-title"><strong>{a.title}</strong><small dir="ltr">{a.slug}</small></div></td><td>{categories[a.category_slug] || a.category_slug}</td><td><span class={'cms-status status-' + a.status}>{statusNames[a.status]}</span></td><td class="cms-numeric">{a.updated_at}</td><td><button class="cms-link" onclick={() => openArticle(a.id)}>ویرایش</button>{#if a.status === 'published'}<a class="cms-link" href={'/stories/' + a.slug + '/'} target="_blank" rel="noopener noreferrer">نمایش</a>{/if}</td></tr>
                {:else}<tr><td colspan="5" class="cms-empty">مقاله‌ای با این مشخصات پیدا نشد.</td></tr>{/each}
              </tbody></table></div>
            </div>
          {:else if view === 'editor'}
            <div class="cms-editor-top"><button class="cms-outline" onclick={() => openView('articles')}><ArrowRight size={16}/> بازگشت به مقالات</button><div><span class={'cms-status status-' + form.status}>{statusNames[form.status]}</span><button class="cms-outline" disabled={busy} onclick={() => saveArticle('draft')}><Save size={17}/> ذخیره پیش‌نویس</button>{#if canPublish}<button class="cms-primary" disabled={busy} onclick={() => saveArticle('published')}><Globe size={17}/> انتشار مقاله</button>{:else}<button class="cms-primary" disabled={busy} onclick={() => saveArticle('review')}>ارسال برای بررسی</button>{/if}</div></div>
            <div class="cms-editor-grid">
              <section class="cms-editor-main">
                <div class="cms-panel cms-editor-card">
                  <span class="cms-overline">CONTENT STUDIO / EDITOR</span>
                  <label class="cms-field">عنوان مقاله<input class="cms-title-input" bind:value={form.title} onblur={slugFromTitle} maxlength="250" placeholder="عنوان جذاب و دقیق مقاله..." /></label>
                  <label class="cms-field">خلاصه مقاله / لید<textarea bind:value={form.excerpt} rows="3" maxlength="1500" placeholder="خلاصه‌ای روشن برای مخاطب و موتور جست‌وجو…"></textarea></label>
                  <div class="cms-editor-bar">
                    <button title="Bold" onclick={() => command('bold')}><Bold size={17}/></button><button title="Italic" onclick={() => command('italic')}><Italic size={17}/></button><button title="Underline" onclick={() => command('underline')}>U</button>
                    <span></span><button title="تیتر سطح دو" onclick={() => command('h2')}><Heading2 size={19}/></button><button title="تیتر سطح سه" onclick={() => command('h3')}><Heading3 size={19}/></button>
                    <span></span><button title="فهرست" onclick={() => command('bullets')}><List size={18}/></button><button title="فهرست شماره‌دار" onclick={() => command('numbers')}><ListOrdered size={18}/></button>
                    <button title="نقل قول" onclick={() => command('quote')}><Quote size={18}/></button><button title="لینک" onclick={() => command('link')}><Link2 size={18}/></button><button title="جدول" onclick={() => command('table')}><Table2 size={19}/></button>
                    <span></span><button title="بازگشت" onclick={() => command('undo')}><Undo2 size={18}/></button><button title="انجام مجدد" onclick={() => command('redo')}><Redo2 size={18}/></button>
                    <button title="درج تصویر" onclick={() => uploadInput?.click()}><ImageIcon size={18}/></button>
                  </div>
                  <div class="cms-editor-area" bind:this={editorHost}></div>
                  {#if editorLoading}<p class="cms-help">ویرایشگر در حال آماده‌سازی است…</p>{/if}
                  <p class="cms-help">ویرایشگر Tiptap/ProseMirror: تیترها، لیست‌ها، لینک، جدول، تصویر، Undo/Redo و خروجی HTML واقعی</p>
                </div>
                <div class="cms-panel cms-seo-panel"><div class="cms-panel-head"><div><span class="cms-overline">SEO STUDIO · TEXT</span><h3>تنظیمات سئوی مقاله</h3></div><Search size={23} color="#00a4d4"/></div>
                  <div class="cms-two-col"><label class="cms-field">عنوان SEO<input bind:value={form.meta_title} maxlength="250" placeholder={form.title || 'Meta title'} /><small>{form.meta_title.length.toLocaleString('fa-IR')} کاراکتر</small></label><label class="cms-field">Focus Keyword<input bind:value={form.focus_keyword} maxlength="190" placeholder="کلیدواژه اصلی" /></label></div>
                  <label class="cms-field">Meta Description<textarea bind:value={form.meta_description} maxlength="320" rows="3" placeholder="توضیحی برای نتایج جست‌وجو…"></textarea><small>{form.meta_description.length.toLocaleString('fa-IR')} کاراکتر</small></label>
                  <div class="cms-two-col"><label class="cms-field">Canonical URL<input dir="ltr" bind:value={form.canonical_url} placeholder="https://…" /></label><label class="cms-field">دسترسی ربات‌های جست‌وجو<select bind:value={form.robots}><option value="index,follow">Index, Follow</option><option value="noindex,follow">Noindex, Follow</option><option value="noindex,nofollow">Noindex, Nofollow</option></select></label></div>
                  <div class="cms-serp"><span>پیش‌نمایش نتایج جست‌وجو</span><strong>{form.meta_title || form.title || 'عنوان مقاله مایکا'}</strong><small>{'mycablog.dandoonii.ir/stories/' + (form.slug || 'article-slug') + '/'}</small><p>{form.meta_description || form.excerpt || 'توضیحات متای مقاله در این بخش نمایش داده می‌شود.'}</p></div>
                </div>
                <div class="cms-panel"><div class="cms-panel-head"><h3>سؤالات متداول / FAQ Schema</h3><span class="cms-help">هر خط: سؤال | پاسخ</span></div><textarea bind:value={faqText} rows="4" placeholder="مراقبت در منزل چیست؟ | مراقبت در منزل شامل…"></textarea><p class="cms-help">بعد از انتشار، FAQ به‌صورت HTML و داده ساختاریافته تولید می‌شود.</p></div>
                {#if form.id}<div class="cms-panel"><div class="cms-panel-head"><h3>تاریخچه نسخه‌ها</h3><button class="cms-outline" onclick={listRevisions}><RefreshCw size={16}/> نمایش نسخه‌ها</button></div>
                    {#each revisions as r}<div class="cms-revision"><span>نسخه {r.id} · {r.display_name} · {r.created_at}</span>{#if canPublish}<button class="cms-link" onclick={() => restoreRevision(r.id)}>بازیابی</button>{/if}</div>{:else}<p class="cms-help">برای دیدن و بازیابی ویرایش‌های پیشین روی «نمایش نسخه‌ها» کلیک کن.</p>{/each}
                  </div>{/if}
              </section>
              <aside class="cms-editor-aside">
                <div class="cms-panel"><h3>انتشار و نشانی</h3><label class="cms-field">اسلاگ انگلیسی مقاله<input dir="ltr" bind:value={form.slug} maxlength="160" placeholder="elderly-home-care" /></label><p class="cms-help">نشانی عمومی: /stories/{form.slug || 'article-slug'}/</p>{#if form.status === 'published'}<button class="cms-outline full" onclick={newTab}><ExternalLink size={16}/> مشاهده مقاله منتشرشده</button>{/if}</div>
                <div class="cms-panel"><h3>دسته‌بندی</h3><label class="cms-field">انتخاب دسته<select bind:value={form.category_slug}>{#each Object.entries(categories) as [slug,label]}<option value={slug}>{label}</option>{/each}</select></label><label class="cms-checkbox"><input type="checkbox" bind:checked={form.featured}/> انتخاب سردبیر (Featured)</label></div>
                <div class="cms-panel"><h3>تصویر شاخص</h3>{#if form.cover_url}<img class="cms-cover-preview" src={publicImageUrl(form.cover_url)} alt={form.cover_alt} />{/if}<label class="cms-field">آدرس تصویر<input dir="ltr" bind:value={form.cover_url} placeholder="/media/uploads/…" /></label><label class="cms-field">متن جایگزین (ALT)<input bind:value={form.cover_alt} maxlength="255" placeholder="توصیف دقیق تصویر" /></label><button class="cms-outline full" onclick={() => uploadInput?.click()}><Upload size={17}/> بارگذاری تصویر</button></div>
                <div class="cms-panel cms-publish-info"><ShieldCheck size={22} color="#00a4d4"/><h3>انتشار استاندارد سئو</h3><p>صفحه عمومی بعد از انتشار از MySQL به HTML سمت سرور تبدیل می‌شود و در سایت‌مپ پویا ثبت خواهد شد.</p></div>
              </aside>
            </div>
          {:else if view === 'media'}
            <div class="cms-page-heading"><div><h2>کتابخانه رسانه مایکا</h2><p>فایل‌های واقعی روی هاست ایران؛ نه لینک‌های موقت CDN</p></div><button class="cms-primary" onclick={() => uploadInput?.click()}><Upload size={17}/> آپلود تصویر</button></div>
            <div class="cms-panel"><label class="cms-field" style="max-width:450px">متن جایگزین تصویر هنگام آپلود<input bind:value={selectedImageAlt} placeholder="مثلاً مراقب در کنار سالمند در منزل" /></label><div class="cms-media-grid">{#each media as item}<div class="cms-media-card"><img src={publicImageUrl(item.path)} alt={item.alt}/><div><strong>{item.alt || 'تصویر بدون ALT'}</strong><small>{Math.round(item.bytes / 1024)} KB</small><button class="cms-link" onclick={() => { navigator.clipboard?.writeText(location.origin + item.path); notice = 'لینک تصویر کپی شد.'; }}>کپی آدرس</button></div></div>{:else}<div class="cms-empty">هنوز رسانه‌ای در پایگاه داده ثبت نشده است.</div>{/each}</div></div>
          {:else if view === 'users' && user.role === 'admin'}
            <div class="cms-page-heading"><div><h2>مدیریت کاربران تحریریه</h2><p>نویسنده، سردبیر و مدیر کل با کنترل دسترسی واقعی روی سرور</p></div><button class="cms-outline" onclick={loadUsers}><RefreshCw size={17}/> به‌روزرسانی</button></div>
            <div class="cms-panel"><h3>ایجاد حساب کاربری</h3>
              <form onsubmit={(e) => {e.preventDefault();createUser();}} class="cms-user-create">
                <label class="cms-field">نام نمایشی<input bind:value={newUserName} required maxlength="120" placeholder="نام نویسنده"/></label>
                <label class="cms-field">ایمیل<input bind:value={newUserEmail} dir="ltr" type="email" required placeholder="editor@example.com"/></label>
                <label class="cms-field">رمز موقت امن
                  <span class="cms-password-wrap"><input bind:value={newUserPassword} type={showNewUserPassword ? 'text' : 'password'} required minlength="14" autocomplete="new-password" placeholder="حداقل ۱۴ کاراکتر"/>
                    <button type="button" class="cms-password-toggle" aria-label={showNewUserPassword ? 'مخفی‌کردن رمز' : 'نمایش رمز'} aria-pressed={showNewUserPassword} onclick={() => showNewUserPassword = !showNewUserPassword}>{#if showNewUserPassword}<EyeOff size={19}/>{:else}<Eye size={19}/>{/if}</button>
                  </span>
                </label>
                <label class="cms-field">نقش<select bind:value={newUserRole}><option value="author">نویسنده</option><option value="editor">سردبیر</option><option value="admin">مدیر کل</option></select></label>
                <button class="cms-primary" type="submit" disabled={busy}><Plus size={17}/> ایجاد کاربر</button>
              </form>
              <p class="cms-help">رمز عبور فقط به‌صورت هش امن در دیتابیس ذخیره می‌شود. رمز اولیه را خارج از پنل و از یک مسیر امن به کاربر بده.</p>
            </div>
            <div class="cms-panel"><h3>حساب‌های موجود</h3>
              <div class="cms-table-scroll"><table class="cms-table"><thead><tr><th>کاربر</th><th>ایمیل</th><th>نقش</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
                {#each users as item (item.id)}<tr>
                  <td><strong>{item.display_name}</strong></td><td dir="ltr">{item.email}</td>
                  <td><select class="cms-table-select" value={item.role} disabled={busy || Number(item.id) === Number(user.id)} onchange={(e) => updateUser(item.id,e.currentTarget.value,Number(item.active) === 1)}><option value="author">نویسنده</option><option value="editor">سردبیر</option><option value="admin">مدیر کل</option></select></td>
                  <td><span class={'cms-status ' + (Number(item.active) === 1 ? 'status-published' : 'status-archived')}>{Number(item.active) === 1 ? 'فعال' : 'غیرفعال'}</span></td>
                  <td><div class="cms-user-actions"><button class="cms-outline" disabled={busy} onclick={() => beginUserEdit(item)}>ویرایش حساب</button><button class="cms-link" disabled={busy || Number(item.id) === Number(user.id)} onclick={() => updateUser(item.id,item.role,Number(item.active) !== 1)}>{Number(item.active) === 1 ? 'غیرفعال‌سازی' : 'فعال‌سازی'}</button></div></td>
                </tr>{:else}<tr><td colspan="5" class="cms-empty">کاربری ثبت نشده است.</td></tr>{/each}
              </tbody></table></div>
              {#if editUserId}
                <form class="cms-user-edit cms-panel" onsubmit={(e) => {e.preventDefault(); saveUserEdit();}}>
                  <div class="cms-panel-head"><h3>ویرایش حساب کاربری</h3><button type="button" class="cms-outline" onclick={cancelUserEdit}>انصراف <X size={15}/></button></div>
                  <div class="cms-user-create">
                    <label class="cms-field">نام نمایشی<input bind:value={editUserName} required maxlength="120" /></label>
                    <label class="cms-field">ایمیل<input type="email" dir="ltr" bind:value={editUserEmail} required /></label>
                    <label class="cms-field">نقش<select bind:value={editUserRole} disabled={Number(editUserId) === Number(user.id)}><option value="author">نویسنده</option><option value="editor">سردبیر</option><option value="admin">مدیر کل</option></select></label>
                    <label class="cms-field cms-user-active"><input type="checkbox" bind:checked={editUserActive} disabled={Number(editUserId) === Number(user.id)} /> حساب فعال باشد</label>
                    <label class="cms-field">رمز جدید (اختیاری)
                      <span class="cms-password-wrap"><input bind:value={editUserPassword} type={showEditUserPassword ? 'text' : 'password'} minlength="14" maxlength="256" autocomplete="new-password" placeholder="خالی بگذار = بدون تغییر رمز"/>
                        <button type="button" class="cms-password-toggle" aria-label={showEditUserPassword ? 'مخفی‌کردن رمز جدید' : 'نمایش رمز جدید'} aria-pressed={showEditUserPassword} onclick={() => showEditUserPassword = !showEditUserPassword}>{#if showEditUserPassword}<EyeOff size={19}/>{:else}<Eye size={19}/>{/if}</button>
                      </span>
                    </label>
                    {#if Number(editUserId) === Number(user.id) && editUserPassword}
                      <label class="cms-field">رمز فعلی خودت برای تأیید تغییر
                        <span class="cms-password-wrap"><input bind:value={editUserCurrentPassword} type={showEditCurrentPassword ? 'text' : 'password'} required autocomplete="current-password"/>
                          <button type="button" class="cms-password-toggle" aria-label={showEditCurrentPassword ? 'مخفی‌کردن رمز فعلی' : 'نمایش رمز فعلی'} aria-pressed={showEditCurrentPassword} onclick={() => showEditCurrentPassword = !showEditCurrentPassword}>{#if showEditCurrentPassword}<EyeOff size={19}/>{:else}<Eye size={19}/>{/if}</button>
                        </span>
                      </label>
                    {/if}
                    <button class="cms-primary" type="submit" disabled={busy}><Save size={17}/> ذخیره تغییرات</button>
                  </div>
                  <p class="cms-help">برای تغییر رمز دیگر کاربران، مدیر کل می‌تواند رمز تازه تعیین کند. برای تغییر رمز حساب خودت، رمز فعلی نیز لازم است. هیچ رمزی از سرور قابل مشاهده یا بازیابی نیست.</p>
                </form>
              {/if}
            </div>
          {:else if view === 'layout' && canPublish}
            <EditorialLayoutStudio {request} {categories}/>
          {:else if view === 'seo'}
            <div class="cms-page-heading"><div><h2>SEO Studio</h2><p>سئوی متنی، تصاویر، ساختار انتشار و خزش موتور جست‌وجو</p></div><a class="cms-outline" href="/sitemap-cms.xml" target="_blank" rel="noopener noreferrer"><ExternalLink size={16}/> نقشه سایت پویا</a></div>
            <div class="cms-seo-grid"><div class="cms-panel"><Search size={29} color="#00a4d4"/><h3>سئوی متنی</h3><p>عنوان و توضیح متا، Canonical، Robots، FAQ Schema و کلیدواژه هر مقاله در Content Studio قابل ویرایش است.</p><button class="cms-link" onclick={() => openView('articles')}>ویرایش مقالات</button></div>
            <div class="cms-panel"><ImageIcon size={29} color="#00a4d4"/><h3>سئوی تصویری</h3><p>تصاویر آپلودشده در هاست ایران ذخیره می‌شوند؛ هر تصویر شاخص عنوان جایگزین مستقل دارد.</p><button class="cms-link" onclick={() => openView('media')}>مدیریت تصاویر</button></div>
            <div class="cms-panel"><Link2 size={29} color="#00a4d4"/><h3>لینک‌سازی داخلی</h3><p>لینک‌های داخلی در ویرایشگر مقاله ساخته می‌شوند. موتور پیشنهاد لینک و گزارش لینک شکسته در نسخه توسعه بعدی قرار دارد.</p></div>
            <div class="cms-panel"><Globe size={29} color="#00a4d4"/><h3>انتشار و نمایه‌سازی</h3><p>مقاله‌های منتشرشده HTML سمت سرور، اسکیما و URL پایدار دارند؛ سایت‌مپ فقط صفحات منتشرشده و مجاز به ایندکس را برمی‌گرداند.</p><a class="cms-link" href="/stories/" target="_blank" rel="noopener noreferrer">مشاهده مطالب منتشرشده</a></div></div>
          {/if}
        </main>
      </div>
    </div>
  {/if}
  <input class="cms-file-input" type="file" accept="image/jpeg,image/png,image/webp" bind:this={uploadInput} onchange={uploadFile}/>
</div>
