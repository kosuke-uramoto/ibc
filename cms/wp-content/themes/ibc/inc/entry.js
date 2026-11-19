(()=>{
const root=document.querySelector('.ibc-entry');if(!root)return;const form=root.querySelector('form.wpcf7-form');if(!form)return;
const input=form.querySelector('.entry-input'),confirm=form.querySelector('.entry-confirm'),list=confirm.querySelector('dl'),send=confirm.querySelector('.entry-send');let ready=false;
const step=n=>root.querySelectorAll('.entry-steps li').forEach((el,i)=>{if(i===n)el.setAttribute('aria-current','step');else el.removeAttribute('aria-current');});
form.querySelectorAll('[aria-required="true"]').forEach(el=>{if(el.matches('input,select,textarea'))el.required=true;});
for(const name of ['zip1','zip2']){const el=form.elements.namedItem(name);if(!el)continue;el.inputMode='numeric';el.pattern=name==='zip1'?'[0-9]{3}':'[0-9]{4}';}
form.elements.namedItem('your-name').pattern='[^\\x00-\\x7F]+[ 　]+[^\\x00-\\x7F]+';
form.elements.namedItem('kana').pattern='[ァ-ヶー・]+[ 　]+[ァ-ヶー・]+';
form.elements.namedItem('phone').pattern='[0-9()+ \\-]{10,20}';
function interview(){const field=form.querySelector('.ibc-interview');if(!field)return;const show=['高校３年','大学 ・ 短大生','社会人'].includes(form.elements.namedItem('grade').value);field.hidden=!show;field.querySelectorAll('input').forEach(el=>{el.disabled=!show;el.required=show;if(!show)el.checked=false;});}
form.elements.namedItem('grade')?.addEventListener('change',interview);interview();
function birthday(){if(!form.elements.namedItem('birth-year'))return;const y=Number(form.elements.namedItem('birth-year').value),m=Number(form.elements.namedItem('birth-month').value),d=Number(form.elements.namedItem('birth-day').value),date=new Date(y,m-1,d);form.elements.namedItem('birth-day').setCustomValidity(y&&m&&d&&(date.getFullYear()!==y||date.getMonth()!==m-1||date.getDate()!==d)?'正しい生年月日を選んでください。':'');}
['birth-year','birth-month','birth-day'].forEach(name=>form.elements.namedItem(name)?.addEventListener('change',birthday));
function edit(){ready=false;input.hidden=false;confirm.hidden=true;send.disabled=false;step(0);}
form.addEventListener('submit',event=>{if(ready)return;event.preventDefault();event.stopImmediatePropagation();birthday();if(!form.reportValidity())return;list.replaceChildren();form.querySelectorAll('[data-field-label]').forEach(block=>{if(block.hidden)return;const values=[...block.querySelectorAll('input,select,textarea')].filter(el=>!el.disabled&&(!['radio','checkbox'].includes(el.type)||el.checked)).map(el=>el.tagName==='SELECT'?el.selectedOptions[0]?.textContent:el.value).filter(Boolean);const dt=document.createElement('dt'),dd=document.createElement('dd');dt.textContent=block.dataset.fieldLabel;dd.textContent=values.join('、')||'未入力';list.append(dt,dd);});input.hidden=true;confirm.hidden=false;step(1);confirm.focus();},true);
confirm.querySelector('.entry-back').addEventListener('click',()=>{edit();form.querySelector('input:not([type=hidden]),select,textarea')?.focus();});
send.addEventListener('click',()=>{ready=true;send.disabled=true;form.requestSubmit();});
form.addEventListener('wpcf7invalid',()=>{edit();form.querySelector('.wpcf7-not-valid')?.focus();});
form.addEventListener('wpcf7mailfailed',edit);form.addEventListener('wpcf7spam',edit);
form.addEventListener('wpcf7mailsent',()=>{step(2);input.hidden=true;confirm.hidden=true;const done=document.createElement('section');done.className='status';done.tabIndex=-1;const h=document.createElement('h3');h.textContent=root.dataset.complete||'申込デモが完了しました';const p=document.createElement('p');p.textContent='実際の申し込みやメール送信は行われていません。';const a=document.createElement('a');a.href=root.dataset.return||'/oc/';a.textContent=root.dataset.returnLabel||'OC一覧へ戻る';done.append(h,p,a);form.after(done);form.hidden=true;done.focus();});
// CF7 dispatches submit for failures too. Recover controls after network errors.
form.addEventListener('wpcf7submit',event=>{if(event.detail.status!=='mail_sent')edit();});
})();
