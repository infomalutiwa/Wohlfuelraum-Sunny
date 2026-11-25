(()=>{
/* Reiter: Termin buchen oder Nachricht schreiben (nur ein Formular sichtbar) */
(()=>{
  const tabs=[...document.querySelectorAll('.fx-tabs [role=tab]')];if(!tabs.length)return;
  const h1=document.querySelector('.book-page h1'),lead=document.querySelector('.book-page .lead'),ticks=document.querySelector('.book-page .ticks');
  if(lead)lead.dataset.bk=lead.textContent;
  const show=(id,focus)=>{
    tabs.forEach(t=>{const on=t.getAttribute('aria-controls')===id;t.setAttribute('aria-selected',on);t.tabIndex=on?0:-1;document.getElementById(t.getAttribute('aria-controls')).hidden=!on});
    if(h1)h1.textContent=h1.dataset[id];if(lead)lead.textContent=lead.dataset[id];if(ticks)ticks.style.display=id==='cf'?'none':'';
    if(focus)document.getElementById('tab-'+id).focus();
  };
  tabs.forEach((t,i)=>{t.onclick=()=>show(t.getAttribute('aria-controls'));
    t.onkeydown=e=>{if(e.key==='ArrowRight'||e.key==='ArrowLeft'){const n=tabs[(i+(e.key==='ArrowRight'?1:tabs.length-1))%tabs.length];show(n.getAttribute('aria-controls'),true)}}});
  const fromHash=()=>{if(location.hash==='#kontakt'){show('cf');document.getElementById('termin').scrollIntoView({block:'start'})}};
  document.querySelectorAll('a[href="#kontakt"]').forEach(a=>a.onclick=e=>{e.preventDefault();show('cf');document.getElementById('termin').scrollIntoView({behavior:'smooth',block:'start'})});
  addEventListener('hashchange',fromHash);fromHash();
})();

/* Kontaktformular: schickt die Nachricht per E-Mail ans Studio (api/kontakt.php auf dem ALL-INKL-Server). */
const f=document.getElementById('cf');if(!f)return;
const API=(window.SUNNY_BOOKING&&window.SUNNY_BOOKING.mailApi)||'/api/kontakt.php';
let t0=0;const err=f.querySelector('.bk-err'),btn=f.querySelector('button[type=submit]');
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

/* Thema aus der Adresse übernehmen, z. B. termin.html?thema=Schulung#kontakt */
const th=new URLSearchParams(location.search).get('thema');
if(th){const o=[...f.topic.options].find(o=>o.text.toLowerCase().startsWith(th.toLowerCase()));if(o)f.topic.value=o.value}

const fail=(msg,el)=>{err.textContent=msg;if(el){el.setAttribute('aria-invalid','true');el.focus()}};
f.addEventListener('input',e=>{if(!t0)t0=Date.now();e.target.removeAttribute('aria-invalid')});
f.addEventListener('submit',async e=>{
  e.preventDefault();err.textContent='';
  const d=Object.fromEntries(new FormData(f));
  for(const k of ['name','email','phone','message'])d[k]=String(d[k]||'').trim();
  if(!d.name)return fail('Bitte gib deinen Namen an.',f.name);
  if(d.email&&!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(d.email))return fail('Bitte prüfe deine E-Mail-Adresse.',f.email);
  if(!d.email&&d.phone.replace(/\D/g,'').length<6)return fail('Bitte gib deine E-Mail-Adresse oder Telefonnummer an, damit wir dir antworten können.',f.email);
  if(d.message.length<5)return fail('Bitte schreib uns kurz, worum es geht.',f.message);
  if(!f.ok.checked)return fail('Bitte bestätige die Datenschutzerklärung.',f.ok);
  btn.disabled=true;btn.textContent='Wird gesendet …';
  try{
    const r=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({type:'contact',name:d.name,email:d.email,phone:d.phone,topic:d.topic,message:d.message,website:d.hp_web||'',t:t0?Date.now()-t0:0})});
    const j=await r.json().catch(()=>({}));
    if(!r.ok||!j.ok)throw new Error(j.error||r.status);
    f.style.minHeight=Math.min(f.offsetHeight,420)+'px';
    f.innerHTML=`<div class="bk-done" role="status" tabindex="-1"><span class="ok">✓</span><h3>Danke, ${esc(d.name)}!</h3>
      <p>Deine Nachricht ist bei uns angekommen. Wir melden uns so schnell wie möglich ${d.email?'per E-Mail':'telefonisch'} bei dir.</p></div>`;
    f.scrollIntoView({behavior:'smooth',block:'start'});
    f.querySelector('.bk-done').focus({preventScroll:true});
  }catch(x){
    btn.disabled=false;btn.textContent='Nachricht senden';
    fail(String(x.message)==='fast'?'Das ging sehr schnell. Bitte prüfe deine Angaben und sende die Nachricht noch einmal.':String(x.message)==='rate'?'Du hast gerade schon mehrere Nachrichten geschickt. Bitte versuche es später noch einmal oder ruf uns an: 0173\u00a03098632.'
      :'Die Nachricht konnte nicht gesendet werden. Bitte versuche es erneut oder ruf uns an: 0173\u00a03098632.');
  }
});
})();
