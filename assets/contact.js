(()=>{
/* Kontaktformular: schickt die Nachricht per E-Mail ans Studio (api/kontakt.php auf dem ALL-INKL-Server). */
const f=document.getElementById('cf');if(!f)return;
const API=(window.SUNNY_BOOKING&&window.SUNNY_BOOKING.mailApi)||'/api/kontakt.php';
const t0=Date.now(),err=f.querySelector('.bk-err'),btn=f.querySelector('button[type=submit]');
/* Kopie ins Studio-Postfach (/mail, Bereich Anfragen) über Firestore */
const FB={apiKey:'AIzaSyDuomvaRRV4VYJYXDs1qE8J0_81gpjRAAs',authDomain:'sunny-mails-beauty.firebaseapp.com',projectId:'sunny-mails-beauty',appId:'1:680567722635:web:524f2428c35df8c1ddba8e'};
const V='https://www.gstatic.com/firebasejs/10.12.2/';
const toInbox=d=>Promise.all([import(V+'firebase-app.js'),import(V+'firebase-firestore.js')]).then(([A,F])=>{
  const app=A.getApps().find(a=>a.name==='kontakt')||A.initializeApp(FB,'kontakt');
  return F.addDoc(F.collection(F.getFirestore(app),'anfragen'),{name:d.name,phone:d.phone,email:d.email,service:d.topic||'Nachricht',when:'Kontaktformular',note:d.message.slice(0,3000),createdAt:F.serverTimestamp(),status:'neu'});
});
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

/* Thema aus der Adresse übernehmen, z. B. termin.html?thema=Schulung#kontakt */
const th=new URLSearchParams(location.search).get('thema');
if(th){const o=[...f.topic.options].find(o=>o.text.toLowerCase().startsWith(th.toLowerCase()));if(o)f.topic.value=o.value}

const fail=(msg,el)=>{err.textContent=msg;if(el){el.setAttribute('aria-invalid','true');el.focus()}};
f.addEventListener('input',e=>e.target.removeAttribute('aria-invalid'));
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
    const inbox=toInbox(d).then(()=>true,()=>false);
    const r=await fetch(API,{method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({type:'contact',name:d.name,email:d.email,phone:d.phone,topic:d.topic,message:d.message,website:d.website||'',t:Date.now()-t0})});
    const j=await r.json().catch(()=>({}));
    if(!r.ok||!j.ok){if(j.error==='rate'||!(await inbox))throw new Error(j.error||r.status)}
    f.innerHTML=`<div class="bk-done" role="status" tabindex="-1"><span class="ok">✓</span><h3>Danke, ${esc(d.name)}!</h3>
      <p>Deine Nachricht ist bei uns angekommen. Wir melden uns so schnell wie möglich ${d.email?'per E-Mail':'telefonisch'} bei dir.</p></div>`;
    f.querySelector('.bk-done').focus();
  }catch(x){
    btn.disabled=false;btn.textContent='Nachricht senden';
    fail(String(x.message)==='rate'?'Du hast gerade schon mehrere Nachrichten geschickt. Bitte versuche es später noch einmal oder ruf uns an: 0173\u00a03098632.'
      :'Die Nachricht konnte nicht gesendet werden. Bitte versuche es erneut oder ruf uns an: 0173\u00a03098632.');
  }
});
})();
