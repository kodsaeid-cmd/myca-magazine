// MYCA Magazine — compress and self-host all editorial photos during build.
// No browser request depends on Pexels or Unsplash. Assets are intentionally
// bounded in dimensions and bytes for mobile users and constrained networks.
import { mkdir, writeFile, stat } from 'node:fs/promises';
import { join } from 'node:path';
import sharp from 'sharp';

const photos = [
  ['care-main', 29372727],
  ['home-safety', 29372532],
  ['walker-care', 39005418],
  ['child-play', 23224845],
  ['senior-nutrition', 16782671],
  ['caregiver-conversation', 18429310],
  ['active-aging', 8899984],
  ['child-home-safety', 10554830],
  ['family-care', 7951621]
];

const folder = join(process.cwd(), 'static', 'images');
await mkdir(folder, { recursive: true });

async function fileReady(path) {
  try { return (await stat(path)).size > 8000; }
  catch { return false; }
}

async function fetchOriginal(id) {
  const url = `https://images.pexels.com/photos/${id}/pexels-photo-${id}.jpeg?auto=compress&cs=tinysrgb&w=1800`;
  let lastError;
  for (let attempt = 1; attempt <= 3; attempt++) {
    try {
      const response = await fetch(url, {
        headers: { Accept: 'image/jpeg,image/*;q=0.9', 'User-Agent': 'MYCA-Magazine/1.1' },
        signal: AbortSignal.timeout(22000)
      });
      if (!response.ok) throw new Error('Image source returned HTTP ' + response.status);
      if (!(response.headers.get('content-type') ?? '').startsWith('image/')) {
        throw new Error('The source returned non-image data');
      }
      const buffer = Buffer.from(await response.arrayBuffer());
      if (buffer.length < 12000) throw new Error('Image source returned an incomplete photo');
      const meta = await sharp(buffer).metadata();
      if (meta.width < 650 || meta.height < 400) throw new Error('Photo is smaller than expected');
      return buffer;
    } catch (error) {
      lastError = error;
      if (attempt < 3) await new Promise(resolve => setTimeout(resolve, attempt * 700));
    }
  }
  throw new Error(`Cannot download photo ${id}: ${lastError?.message}`);
}

async function optimize(input, { thumbnail = false, quality = 76 } = {}) {
  const maxWidth = thumbnail === 'small' ? 390 : thumbnail === 'medium' ? 900 : 1440;
  const maxHeight = thumbnail === 'small' ? 390 : thumbnail === 'medium' ? 1100 : 1600;
  return sharp(input, { failOn: 'error' })
    .rotate()
    .resize({ width: maxWidth, height: maxHeight, fit: 'inside', withoutEnlargement: true })
    .jpeg({ quality, mozjpeg: true, progressive: true })
    .toBuffer();
}

async function mirror(name, id) {
  const large = join(folder, name + '.jpg');
  const medium = join(folder, name + '-md.jpg');
  const small = join(folder, name + '-sm.jpg');
  if (await fileReady(large) && await fileReady(medium) && await fileReady(small)) {
    console.log('Cached photo variants:', name);
    return;
  }
  const source = await fetchOriginal(id);
  let largeBuffer = await optimize(source);
  // Keep every full image below 390 KB (well below the previous multi-megabyte images).
  for (const quality of [71, 66, 60]) {
    if (largeBuffer.length <= 390000) break;
    largeBuffer = await optimize(source, { quality });
  }
  if (largeBuffer.length > 390000) {
    throw new Error(`Oversized photo ${name}: ${largeBuffer.length} bytes`);
  }
  let mediumBuffer = await optimize(source, { thumbnail: 'medium', quality: 72 });
  if (mediumBuffer.length > 195000) mediumBuffer = await optimize(source, { thumbnail: 'medium', quality: 62 });
  if (mediumBuffer.length > 195000) throw new Error('Oversized medium image ' + name);
  let thumbnail = await optimize(source, { thumbnail: 'small', quality: 69 });
  if (thumbnail.length > 65000) {
    thumbnail = await optimize(source, { thumbnail: 'small', quality: 58 });
  }
  if (thumbnail.length > 65000) {
    throw new Error(`Oversized thumbnail ${name}: ${thumbnail.length} bytes`);
  }
  await Promise.all([writeFile(large, largeBuffer), writeFile(medium, mediumBuffer), writeFile(small, thumbnail)]);
  console.log(`Optimized ${name}: main ${Math.round(largeBuffer.length / 1024)} KB, medium ${Math.round(mediumBuffer.length / 1024)} KB, thumb ${Math.round(thumbnail.length / 1024)} KB`);
}

for (let i = 0; i < photos.length; i += 3) {
  await Promise.all(photos.slice(i, i + 3).map(([name, id]) => mirror(name, id)));
}
console.log('MYCA: all photos optimized and stored locally for static hosting.');
