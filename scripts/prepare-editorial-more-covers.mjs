// The new editorial collection uses stable, first-party article cover paths.
// Copy vetted existing photo assets after prepare-local-photos.mjs has built them.
// Build output contains all ten covers; no browser CDN or manual uploads needed.
import { mkdir, copyFile, stat } from 'node:fs/promises';
import { join } from 'node:path';
const root=process.cwd();
const input=join(root,'static','media-check');
const target=join(root,'static','media','editorial-more');
await mkdir(target,{recursive:true});
const shots=[
 ['11-elder-discharge','walker-care'],
 ['12-daily-care-report','caregiver-conversation'],
 ['13-pressure-prevention','home-safety'],
 ['14-elderly-sleep','active-aging'],
 ['15-social-connection','care-main'],
 ['16-physical-activity','walker-care'],
 ['17-dementia-activities','caregiver-conversation'],
 ['18-oral-health','senior-nutrition'],
 ['19-child-medication-safety','child-home-safety'],
 ['20-child-family-routine','child-play']
];
for(const [name,src] of shots) {
  const from=join(input,'photo-'+src+'.jpg');
  if((await stat(from)).size<5000)throw new Error('Missing verified article photo: '+from);
  await copyFile(from,join(target,'cover-'+name+'.jpg'));
}
console.log('MYCA: 10 stable editorial article covers ready.');
