// Publish licensed Vazir WOFF2 fonts at stable, local same-origin URLs.
// This avoids hashed Vite asset paths in PHP-rendered pages.
import { mkdir, copyFile, stat } from 'node:fs/promises';
import { join } from 'node:path';
const origin=join(process.cwd(),'node_modules','vazir-font','dist');
const target=join(process.cwd(),'static','fonts');
await mkdir(target,{recursive:true});
for(const weight of ['Regular','Medium','Bold','Light']){
  const file='Vazir-'+weight+'.woff2';
  const input=join(origin,file);
  const size=(await stat(input)).size;
  if(size<5000)throw new Error('Invalid Vazir font: '+file);
  await copyFile(input,join(target,file));
}
console.log('Vazir: self-hosted WOFF2 files ready for the public magazine and admin CMS.');
