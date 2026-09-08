(() => {
'use strict';
const make=(tag,text,cls)=>{const el=document.createElement(tag);if(text!==undefined)el.textContent=text;if(cls)el.className=cls;return el;};
const safeURL=(value)=>{try{const u=new URL(value,location.origin);return ['http:','https:'].includes(u.protocol)&&u.origin===location.origin?u.href:null;}catch{return null;}};
const dates=new Intl.DateTimeFormat('ja-JP',{timeZone:'Asia/Tokyo',month:'long',day:'numeric',weekday:'short'});
const times=new Intl.DateTimeFormat('ja-JP',{timeZone:'Asia/Tokyo',hour:'2-digit',minute:'2-digit',hour12:false});
const labels={open:'開催予定',closed:'受付終了',cancelled:'中止'};
function card(e,course){const el=make('article',undefined,'event');el.append(make('span',labels[e.state]||'開催予定','badge'),make('p',dates.format(new Date(e.start))+' '+e.session,'date'),make('h3',e.title),make('p',times.format(new Date(e.start))+'–'+times.format(new Date(e.end))+' ／ '+e.audience));const dl=make('dl');for(const p of e.programs.filter(p=>!course||p.key===course)){dl.append(make('dt',p.label),make('dd',p.text));}el.append(dl);const url=safeURL(e.url);if(url){const a=make('a','開催内容を見る →','button');a.href=url;el.append(a);}return el;}
async function load(){
 const controller=new AbortController();const timer=setTimeout(()=>controller.abort(),10000);
 try{const res=await fetch('/cms/?rest_route=/ibc/v1/feed',{cache:'no-store',signal:controller.signal});if(!res.ok)throw Error('feed');const data=await res.json();if(!Array.isArray(data.events)||!Array.isArray(data.news))throw Error('shape');
 for(const region of document.querySelectorAll('[data-oc]')){const course=region.dataset.course;let events=data.events.filter(e=>!course||e.programs.some(p=>p.key===course));if(region.dataset.oc==='next')events=events.filter(e=>e.state!=='cancelled').slice(0,1);region.replaceChildren();if(!events.length)region.append(make('p','次回開催は準備中です。','status'));else events.forEach(e=>region.append(card(e,course)));}
 for(const region of document.querySelectorAll('[data-news]')){region.replaceChildren();if(!data.news.length)region.append(make('p','お知らせは準備中です。','status'));for(const n of data.news){const url=safeURL(n.url);if(!url)continue;const a=make('a',undefined,'news-item');a.href=url;a.append(make('time',n.date),make('span',n.title));region.append(a);}}
 const cta=document.querySelector('.mobile-cta');if(cta){const e=data.events.find(e=>e.state==='open'&&(!document.body.dataset.course||e.programs.some(p=>p.key===document.body.dataset.course)));if(e&&safeURL(e.url)){cta.href=safeURL(e.url);cta.replaceChildren(make('span','次回 '+dates.format(new Date(e.start))),make('strong','OC詳細を見る →'));cta.hidden=false;}else cta.hidden=true;}
 }catch{for(const region of document.querySelectorAll('[data-oc],[data-news]')){region.replaceChildren(make('p','情報を読み込めませんでした。時間をおいて再読み込みしてください。','status'));}}finally{clearTimeout(timer);}
}
load();
})();