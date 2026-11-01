// Build-time image mirroring. Once deployed, browsers fetch photos from MYCA's own domain.
// Photo originals are freely reusable under the Pexels License; sources in README.
// This script runs in CI and Cloudflare Builds before Vite, never in the browser.
import { mkdir, writeFile, stat } from 'node:fs/promises';
import { join } from 'node:path';

const assets = [
  { file: 'care-main.jpg', id: 29372727 },
  { file: 'home-safety.jpg', id: 29372532 },
  { file: 'walker-care.jpg', id: 39005418 },
  { file: 'child-play.jpg', id: 23224845 },
  { file: 'senior-nutrition.jpg', id: 16782671 },
  { file: 'caregiver-conversation.jpg', id: 18429310 },
  { file: 'active-aging.jpg', id: 8899984 },
  { file: 'child-home-safety.jpg', id: 10554830 },
  { file: 'family-care.jpg', id: 7951621 }
];

const dest = join(process.cwd(), 'static', 'images');
await mkdir(dest, { recursive: true });

async function fetchImage({file, id}) {
  const output = join(dest, file);
  try {
    const existing = await stat(output);
    if (existing.size > 20000) {
      console.log('Image cached:', file);
      return;
    }
  } catch {
    // Not present yet.
  }

  const url = `https://images.pexels.com/photos/${id}/pexels-photo-${id}.jpeg?auto=compress&cs=tinysrgb&w=1800`;
  let lastError;

  for (let attempt = 1; attempt <= 3; attempt++) {
    try {
      const response = await fetch(url, {
        headers: { 'Accept': 'image/jpeg,image/*;q=0.9', 'User-Agent': 'MYCA-Magazine-Build/1.0' },
        signal: AbortSignal.timeout(30000)
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const type = response.headers.get('content-type') || '';
      if (!type.startsWith('image/')) throw new Error(`Expected image, received ${type}`);
      const data = Buffer.from(await response.arrayBuffer());
      if (data.length < 20000 || data[0] !== 0xff || data[1] !== 0xd8) {
        throw new Error('Downloaded file is not a valid full-size JPEG');
      }
      await writeFile(output, data);
      console.log(`Mirrored ${file}: ${(data.length / 1024).toFixed(0)} KB`);
      return;
    } catch (error) {
      lastError = error;
      console.warn(`Image ${file} attempt ${attempt}/3: ${error.message}`);
      if (attempt < 3) await new Promise(resolve => setTimeout(resolve, attempt * 1200));
    }
  }
  throw new Error(`Cannot build MYCA photo ${file}: ${lastError?.message}. Refusing to deploy blank imagery.`);
}

// Small batches avoid provider throttling while keeping CI reasonably fast.
for (let i = 0; i < assets.length; i += 3) {
  await Promise.all(assets.slice(i, i + 3).map(fetchImage));
}
console.log('All MYCA photographic assets are ready for first-party hosting.');
