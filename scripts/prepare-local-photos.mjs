// Source originals are the SAME Unsplash photos used by the previous homepage.
// Download once during CI, then serve ordinary JPEGs from the PROVEN
// /media-check/ path; end users never need access to Unsplash or Pexels.
// Reuse the same static-file path semantics as /media-check/sample.jpg.
import { mkdir, writeFile, stat } from 'node:fs/promises';
import { join } from 'node:path';

const destination = join(process.cwd(), 'static', 'media-check');
await mkdir(destination, { recursive: true });

const photos = [
  ['care-main', 'photo-1765896387387-0538bc9f997e', 29372727],
  ['home-safety', 'photo-1600607687920-4e2a09cf159d', 29372532],
  ['walker-care', 'photo-1773227055624-07b515ba87c5', 39005418],
  ['child-play', 'photo-1758598737547-666bce663667', 23224845],
  ['senior-nutrition', 'photo-1547592180-85f173990554', 16782671],
  ['caregiver-conversation', 'photo-1762955911431-4c44c7c3f408', 18429310],
  ['active-aging', 'photo-1773227060422-ee506b865417', 8899984],
  ['child-home-safety', 'photo-1758874961117-e40e35b67331', 10554830]
];

async function ready(path) {
  try { return (await stat(path)).size > 5000; }
  catch { return false; }
}

function candidateUrl(id, fallbackId, width, quality, fallback = false) {
  return fallback
    ? 'https://images.pexels.com/photos/' + fallbackId + '/pexels-photo-' + fallbackId + '.jpeg?auto=compress&cs=tinysrgb&w=' + width
    : 'https://images.unsplash.com/' + id + '?fit=crop&w=' + width + '&q=' + quality + '&fm=jpg';
}

async function jpeg(url, maxBytes) {
  const response = await fetch(url, {
    signal: AbortSignal.timeout(23000),
    headers: { Accept: 'image/jpeg,image/*;q=0.8', 'User-Agent': 'Mozilla/5.0 (compatible; MYCA-Magazine-Build/1.0)' }
  });
  if (!response.ok) throw new Error('HTTP ' + response.status);
  const type = response.headers.get('content-type') || '';
  if (!type.startsWith('image/jpeg')) throw new Error('Expected JPEG, got ' + type);
  const bytes = Buffer.from(await response.arrayBuffer());
  if (bytes.length < 5000 || bytes.length > maxBytes || bytes[0] !== 0xff || bytes[1] !== 0xd8) {
    throw new Error('Invalid JPEG size: ' + bytes.length);
  }
  return bytes;
}

async function generate(name, original, backupId, variant) {
  const thumb = variant === 'sm';
  const path = join(destination, 'photo-' + name + (thumb ? '-sm' : '') + '.jpg');
  if (await ready(path)) return;
  const width = thumb ? 380 : 1200;
  const attempts = thumb
    ? [[75, false], [65, false], [72, true]]
    : [[78, false], [68, false], [75, true]];
  let lastError;
  for (const [quality, fallback] of attempts) {
    try {
      const url = candidateUrl(original, backupId, width, quality, fallback);
      const data = await jpeg(url, thumb ? 65000 : 350000);
      await writeFile(path, data);
      console.log('MYCA local photo ' + name + (thumb ? '-sm' : '') + ': ' + Math.round(data.length/1024) + 'KB from ' + (fallback ? 'Pexels backup' : 'original Unsplash'));
      return;
    } catch (error) { lastError = error; }
  }
  throw new Error('Cannot build photo ' + name + '/' + variant + ': ' + lastError?.message);
}

for (let i = 0; i < photos.length; i += 3) {
  await Promise.all(photos.slice(i, i + 3).flatMap(([name, id, fallbackId]) => [
    generate(name, id, fallbackId, 'full'),
    generate(name, id, fallbackId, 'sm')
  ]));
}
const cards = photos.map(([name]) =>
  '<article><img loading="lazy" src="/media-check/photo-' + name + '.jpg?v=7" alt="' + name + '">' +
  '<p>' + name + '</p></article>'
).join('');
const gallery = '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
  '<title>گالری آزمایشی عکس‌های مایکا</title>' +
  '<style>body{font:16px Tahoma,Arial;background:#eef8fb;color:#063957;padding:22px}main{max-width:1100px;margin:auto}section{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px}article{background:white;border-radius:16px;padding:12px}img{width:100%;height:220px;object-fit:cover;border-radius:9px}p{padding:5px}</style>' +
  '<main><h1>تصاویر واقعی مجله مایکا از همان دامنه</h1><p>این عکس‌ها از فایل مستقل روی دامنه فعلی ارائه می‌شوند.</p><section>' + cards + '</section></main></html>';
await writeFile(join(destination, 'gallery.html'), gallery, 'utf8');
console.log('MYCA: first-party gallery and all ' + photos.length + ' article images ready.');


// Fallback photo delivery through JavaScript. The user confirmed that the
// /media-check/sample.js transport works without VPN. This bundle is only
// downloaded if regular local JPEGs are slow or fail to load.
import { readFile } from 'node:fs/promises';
const fallback = {};
let rawBytes = 0;
for (const [name] of photos) {
  const jpg = await readFile(join(destination, 'photo-' + name + '-sm.jpg'));
  if (jpg.length < 5000 || jpg.length > 65000) throw new Error('Invalid rescue photo: ' + name);
  rawBytes += jpg.length;
  fallback[name] = 'data:image/jpeg;base64,' + jpg.toString('base64');
}
if (rawBytes > 270000) throw new Error('Image rescue budget exceeded: ' + rawBytes);

function photoRescue(photoData) {
  function getPhotoId(img) {
    const manual = img.getAttribute('data-myca-photo');
    if (manual && Object.prototype.hasOwnProperty.call(photoData, manual)) return manual;
    const value = img.getAttribute('src') || '';
    const match = value.match(/\/media-check\/photo-([a-z-]+?)(?:-sm)?\.jpg(?:\?|$)/);
    return match && Object.prototype.hasOwnProperty.call(photoData, match[1]) ? match[1] : null;
  }
  function rescue() {
    for (const img of document.querySelectorAll('img')) {
      const id = getPhotoId(img);
      if (!id || (img.complete && img.naturalWidth > 0)) continue;
      if ((img.getAttribute('src') || '').startsWith('data:')) continue;
      img.src = photoData[id];
      img.dataset.mycaImageRescued = id;
    }
  }
  let queued = false;
  const observer = new MutationObserver(() => {
    if (queued) return;
    queued = true;
    queueMicrotask(() => { queued = false; rescue(); });
  });
  function start() {
    window.__mycaPhotoRescueReady = true;
    rescue();
    observer.observe(document.documentElement, {
      subtree: true, childList: true, attributes: true, attributeFilter: ['src']
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
  else start();
}
const bundle = '(' + photoRescue.toString() + ')(' + JSON.stringify(fallback) + ');\n';
await writeFile(join(destination, 'sample-pack.js'), bundle, 'utf8');
const testCards = photos.map(([name]) =>
  '<article><h2>' + name + '</h2><img src="/media-check/not-found.jpg" data-myca-photo="' + name + '" alt="' + name + '">' +
  '<small>Only JavaScript image data can repair these intentionally broken URLs</small></article>'
).join('');
const testPage = '<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
  '<title>آزمون تصاویر متفاوت مایکا</title><style>body{background:#eef8fb;color:#093e60;font:16px Tahoma,Arial;padding:20px}' +
  'main{max-width:1050px;margin:auto}section{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px}' +
  'article{background:white;border-radius:14px;padding:12px}img{width:100%;height:190px;object-fit:cover;border-radius:9px}small{display:block}</style>' +
  '<script defer src="/media-check/sample-pack.js?v=9"></script><main><h1>آزمون تصاویر متفاوت بدون VPN</h1>' +
  '<p>این تصاویر عمداً URL ناموجود دارند و فقط توسط فایل JavaScript همان دامنه نمایش داده می‌شوند.</p>' +
  '<section>' + testCards + '</section></main></html>';
await writeFile(join(destination, 'pack-test.html'), testPage, 'utf8');
console.log('MYCA rescue bundle: ' + Math.round(rawBytes / 1024) +
  'KB source pixels, ' + Math.round(Buffer.byteLength(bundle) / 1024) + 'KB JavaScript for eight distinct photos.');
