# MYCA Magazine — مجله مایکا

وب‌سایت فارسی، راست‌به‌چپ و واکنش‌گرای مجله مایکا با **SvelteKit**، براساس آلترناتیو ۳ (Premium Editorial).

## اجرای محلی
- Node.js 20.19+ یا 22.12+
- `npm install`
- `npm run dev`
- `npm run check`
- `npm run build`

## صفحات
- `/` صفحه نخست، هرو، دسته‌ها، منتخب سردبیر و جدیدترین مطالب
- `/category/[slug]` صفحات دسته‌بندی
- `/article/[slug]` صفحات مقالات با متادیتای سئو و داده ساخت‌یافته
- `/search?q=...` جست‌وجوی محتوای نمونه
- `/sitemap.xml` و `/robots.txt`

## توجه پیش از انتشار عمومی
مقاله‌ها و تاریخ‌های انتشار فعلاً **نمونه نمایشی** هستند، نه محتوای منتشرشده یا تأییدشده مایکا. داده‌ها در `src/lib/content.js` ذخیره شده‌اند؛ اتصال به CMS در فاز بعد انجام می‌شود. تصاویر نسخه آزمایشی از Pexels در مرحله Build دریافت و به‌صورت فایل داخلی سایت منتشر می‌شوند؛ مراجعه‌کننده برای نمایش تصاویر به دامنه تصاویر خارجی وابسته نیست.

برای تغییر دامنه در سئو، مقدار `PUBLIC_SITE_URL` را در محیط تعریف کنید. برای لینک خدمات نیز `PUBLIC_APP_URL` را تنظیم کنید (پیش‌فرض `https://mycaapp.com`).

## ویژگی‌ها
صفحات استاتیک پیش‌ساخته، متادیتای معنادار، سایت‌مپ، RTL، طراحی متناسب با موبایل و دسکتاپ، فونت Vazir، منوی موبایل و جست‌وجوی کاربردی.

## معماری
این پروژه مستقل از اپلیکیشن اصلی است. ایجاد حساب کاربری، پرداخت، CRM و سیستم مدیریت محتوای واقعی در این نسخه پیاده‌سازی نشده است. لینک درخواست خدمات به اپلیکیشن اصلی هدایت می‌شود.

## توسعه آینده
Directus یا CMS headless، مدل نویسنده و بازبین تخصصی، صفحه شهرها، دسته‌بندی کالاها، تحلیل رویدادها، و اتصال محتوایی به فروشگاه مایکا.
**ایندکس موتور جست‌وجو:** فایل robots.txt تا زمانی که `PUBLIC_ALLOW_INDEXING=true` تنظیم نشده باشد، ربات‌ها را مسدود می‌کند؛ برای جلوگیری از ایندکس ناخواسته محتوای نمایشی.

## Cloudflare Workers: staging روی mycablog.dandoonii.ir

این مخزن برای پیش‌نمایش فعلی از خروجی **Static Prerender** در SvelteKit استفاده می‌کند؛ SSR و اتصال CMS در فاز تولید جداگانه بازگردانده می‌شود.

- Worker مجزا: `myca-magazine-stage`، جدا از Worker سایت اعتماد و آسایش `etemaad-asayesh-stage`.
- دامنه آزمایشی: `mycablog.dandoonii.ir`.
- فایل `wrangler.jsonc` و دستور `npm run deploy` برای Workers Static Assets آماده است.
- از Cloudflare > Workers & Pages > Create application > Import Git repository مخزن `kodsaeid-cmd/myca-magazine` را انتخاب کنید.
- Production branch: `main`، Build command: `npm run build`، Deploy command: `npx wrangler deploy`، Node.js: 22 یا بالاتر.
- ابتدا آدرس `*.workers.dev` را بررسی کنید. سپس در Worker > Settings > Domains & Routes > Add Custom Domain، فقط `mycablog.dandoonii.ir` را متصل کنید.
- هرگز Worker فعلی `etemaad-asayesh-stage` یا DNS رکورد `stage.dandoonii.ir` را جایگزین نکنید.

مقالات آزمایشی‌اند؛ `robots.txt` و هدر `X-Robots-Tag` ایندکس را ممنوع می‌کنند. برای محدودکردن دسترسی واقعی، Cloudflare Access لازم است.


### رفع خطای Cloudflare: assets.directory does not exist

اگر در Workers Builds فقط `npx wrangler deploy` اجرا شود، پوشه `build/` وجود ندارد، چون این پوشه توسط `vite build` ساخته می‌شود و در Git ثبت نمی‌شود.

در Cloudflare → Workers & Pages → `myca-magazine-stage` → Settings → Build → Build Configuration، یکی از این دو حالت را انتخاب کنید:

**حالت پیشنهادی استاندارد**
- Build command: `npm run build`
- Deploy command: `npx wrangler deploy`
- Root directory: ریشه مخزن (`/` یا خالی)
- Branch: `main`

**حالت تک‌دستوری (اگر Build command را خالی گذاشته‌اید)**
- Build command: خالی
- Deploy command: `npm run deploy`

اسکریپت `npm run deploy` در `package.json` ابتدا `npm run build` و سپس `wrangler deploy` را اجرا می‌کند؛ هر دو روش را هم‌زمان تنظیم نکنید تا ساخت تکراری نشود. بعد از ذخیره تنظیمات از Deployment → Build History گزینه Retry build را بزنید.

توجه: برای استقرار Worker، نام Worker باید با `name` در `wrangler.jsonc` هماهنگ باشد (`myca-magazine-stage`). در این مرحله دامنه اصلی و Worker موجود سایت اعتماد و آسایش را تغییر ندهید.


## تصاویر نسخه آزمایشی — میزبانی داخلی بدون عکس‌های شکسته

برای رفع مشکل نمایش تصاویر در برخی مرورگرها و شبکه‌ها، تمام عکس‌های صفحه اول و مقالات از مسیرهای محلی `/images/*.jpg` لود می‌شوند. اسکریپت `scripts/prepare-photography.mjs` قبل از `npm run build` (و در حالت توسعه قبل از `npm run dev`) تصاویر را به پوشه `static/images` انتقال می‌دهد. فایل‌های خروجی در Cloudflare Workers Static Assets میزبانی می‌شوند؛ بارگذاری عکس در مرورگر وابسته به دامنه‌های خارجی نیست.

**منابع عکس‌های رایگان (Pexels License):**

- [مراقبت سالمند در خانه — Jsme MILA](https://www.pexels.com/photo/elderly-caregiver-assisting-senior-woman-at-home-29372727/)
- [کمک به سالمند در حمام — Jsme MILA](https://www.pexels.com/photo/elderly-woman-receiving-home-health-care-assistance-29372532/)
- [همراهی سالمند با وسیله کمکی — Caleb Oquendo](https://www.pexels.com/photo/caregiver-supports-elderly-woman-in-public-place-39005418/)
- [بازی مادر و فرزند — Vitaly Gariev](https://www.pexels.com/photo/mother-and-son-playing-with-blocks-23224845/)
- [آشپزی سالمند در منزل — Israel Torres](https://www.pexels.com/photo/elderly-woman-preparing-food-in-a-kitchen-16782671/)
- [گفت‌وگوی مراقب با سالمند — Jsme MILA](https://www.pexels.com/photo/caregiver-talking-to-an-elderly-woman-on-a-wheelchair-18429310/)
- [گفت‌وگوی سالمند با نوه — SHVETS production](https://www.pexels.com/photo/an-elderly-man-talking-to-his-granddaughter-while-sitting-on-the-couch-8899984/)
- [بازی مادر و دختر — Ron Lach](https://www.pexels.com/photo/mother-and-daughter-playing-with-building-blocks-10554830/)
- [خانواده چندنسلی در خانه — RDNE Stock project](https://www.pexels.com/photo/a-family-sitting-on-the-couch-7951621/)

برای اطمینان از اینکه تصویر خراب دوباره به سایت منتشر نمی‌شود، اسکریپت در صورت خطای دریافت عکس یا دریافت فایل غیرعکس، Build را متوقف می‌کند. لازم است تصاویر تأییدشده و اختصاصی مایکا در مرحله انتشار نهایی جایگزین شوند.

صفحه نخست از داده‌های نمونه در `src/lib/content.js` استفاده می‌کند. هنوز CMS زنده و جریان خودکار خبر وجود ندارد.
