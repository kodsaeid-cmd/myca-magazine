<script>
  import { page } from '$app/stores';
  import { House } from '@lucide/svelte';
</script>
<svelte:head><title>صفحه پیدا نشد | مجله مایکا</title><meta name="robots" content="noindex"/></svelte:head>
<div class="container page-wrap">
  <div class="empty-state" style="margin:40px auto;max-width:700px">
    <House color="#00a4d4" size={44}/>
    <h1>{$page.status === 404 ? 'این صفحه پیدا نشد' : 'مشکلی پیش آمده است'}</h1>
    <p>{$page.status === 404 ? 'ممکن است نشانی صفحه تغییر کرده باشد.' : 'لطفاً کمی بعد دوباره تلاش کنید.'}</p>
    <a href="/" class="btn-primary">بازگشت به صفحه اصلی </a>
  </div>
</div>
