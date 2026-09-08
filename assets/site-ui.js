(() => {
'use strict';
const menu=document.querySelector('#site-menu');
const toggle=document.querySelector('.menu-toggle');
const info=document.querySelector('#nav-info');
function lock(){document.body.classList.toggle('dialog-open',Boolean(document.querySelector('dialog[open]')));}
if(menu&&toggle){toggle.addEventListener('click',()=>{menu.showModal();toggle.setAttribute('aria-expanded','true');lock();});menu.addEventListener('close',()=>{toggle.setAttribute('aria-expanded','false');lock();});}
document.querySelectorAll('[data-info]').forEach(button=>button.addEventListener('click',()=>{info.querySelector('#info-heading').textContent=button.dataset.info;info.showModal();lock();}));
document.querySelectorAll('dialog').forEach(dialog=>{dialog.querySelector('[data-close]').addEventListener('click',()=>dialog.close());dialog.addEventListener('close',lock);dialog.addEventListener('click',event=>{const box=dialog.getBoundingClientRect();if(event.target===dialog&&(event.clientX<box.left||event.clientX>box.right||event.clientY<box.top||event.clientY>box.bottom))dialog.close();});});
const slider=document.querySelector('.firstview');if(!slider)return;
const slides=[...slider.querySelectorAll('.fv-slide')];const dots=[...slider.querySelectorAll('[data-slide]')];const pause=slider.querySelector('[data-pause]');const motion=matchMedia('(prefers-reduced-motion: reduce)');let current=0,stopped=motion.matches,hover=false,timer=null;
function schedule(){clearInterval(timer);if(!stopped&&!hover&&!document.hidden&&!slider.contains(document.activeElement))timer=setInterval(()=>show(current+1),5500);}
function show(index){current=(index+slides.length)%slides.length;slides.forEach((slide,i)=>{slide.classList.toggle('is-active',i===current);slide.setAttribute('aria-hidden',String(i!==current));slide.inert=i!==current;});dots.forEach((dot,i)=>dot.setAttribute('aria-pressed',String(i===current)));slider.querySelector('.slide-counter').textContent=String(current+1).padStart(2,'0')+' / 02';}
function label(){pause.textContent=stopped?'自動再生':'一時停止';pause.setAttribute('aria-label',stopped?'スライドの自動再生を開始':'スライドの自動再生を停止');}
pause.addEventListener('click',()=>{stopped=!stopped;label();schedule();});
slider.querySelector('[data-prev]').addEventListener('click',()=>{show(current-1);schedule();});slider.querySelector('[data-next]').addEventListener('click',()=>{show(current+1);schedule();});dots.forEach(dot=>dot.addEventListener('click',()=>{show(Number(dot.dataset.slide));schedule();}));
slider.addEventListener('mouseenter',()=>{hover=true;schedule();});slider.addEventListener('mouseleave',()=>{hover=false;schedule();});slider.addEventListener('focusin',()=>{stopped=true;label();schedule();});slider.addEventListener('focusout',()=>setTimeout(schedule,0));
document.addEventListener('visibilitychange',schedule);motion.addEventListener('change',()=>{if(motion.matches)stopped=true;label();schedule();});
let touch=null;slider.addEventListener('touchstart',event=>{touch=event.touches[0];},{passive:true});slider.addEventListener('touchend',event=>{if(!touch)return;const dx=event.changedTouches[0].clientX-touch.clientX,dy=event.changedTouches[0].clientY-touch.clientY;if(Math.abs(dx)>50&&Math.abs(dx)>Math.abs(dy)){show(current+(dx<0?1:-1));schedule();}touch=null;},{passive:true});
label();schedule();
})();
