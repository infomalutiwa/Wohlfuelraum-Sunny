/* Wohlfühlraum Sunny – Studio-Kalender.
   Daten kommen von /api/studio.php (Zugang per Studio-Schlüssel aus dem Link in den Buchungs-Mails). */
(()=>{
'use strict';
const API='/api/studio.php';
const $=(s,r=document)=>r.querySelector(s),$$=(s,r=document)=>[...r.querySelectorAll(s)];
const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const pad=n=>String(n).padStart(2,'0');
const iso=d=>d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate());
const day=s=>{const [y,m,d]=s.split('-').map(Number);return new Date(y,m-1,d)};
const add=(d,n)=>{const x=new Date(d);x.setDate(x.getDate()+n);return x};
const monday=d=>add(d,-((d.getDay()+6)%7));
const fmtT=m=>Math.floor(m/60)+':'+pad(m%60);
const fmtDur=m=>{const h=Math.floor(m/60),r=m%60;return (h?h+' Std.':'')+(h&&r?' ':'')+(r?r+' Min.':'')||'0 Min.'};
const toMin=t=>{const [h,m]=String(t||'').split(':').map(Number);return Number.isFinite(h)?h*60+(m||0):NaN};
const hhmm=m=>pad(Math.floor(m/60))+':'+pad(m%60);
const F=(o)=>new Intl.DateTimeFormat('de-DE',o);
const fLong=F({weekday:'long',day:'numeric',month:'long'}),fLongY=F({weekday:'long',day:'numeric',month:'long',year:'numeric'});
const fShort=F({weekday:'short',day:'numeric',month:'short'}),fMonth=F({month:'long'}),fDM=F({day:'numeric',month:'short'});
const fWd=F({weekday:'short'}),fTime=F({hour:'2-digit',minute:'2-digit'});
const WD=['So','Mo','Di','Mi','Do','Fr','Sa'],WDL=['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'];
const KIND={urlaub:'Urlaub',geschlossen:'Geschlossen',feiertag:'Feiertag',sonstiges:'Sonstiges'};
const mq=matchMedia('(max-width:820px)');
const ICON={
  cal:'<svg viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="15.5" rx="3"/><path d="M3.5 10h17M8 3v4M16 3v4"/></svg>',
  check:'<svg viewBox="0 0 24 24"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>',
  x:'<svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>',
  msg:'<svg viewBox="0 0 24 24"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4h11A2.5 2.5 0 0 1 20 6.5v8a2.5 2.5 0 0 1-2.5 2.5H10l-4.5 3.5V17A2.5 2.5 0 0 1 4 14.5z"/></svg>',
  phone:'<svg viewBox="0 0 24 24"><path d="M6.6 3.8h2.6l1.4 4-2 1.4a11 11 0 0 0 6.2 6.2l1.4-2 4 1.4v2.6a2 2 0 0 1-2.2 2A16.5 16.5 0 0 1 4.6 6a2 2 0 0 1 2-2.2z"/></svg>',
  wa:'<svg viewBox="0 0 24 24"><path d="M4.5 19.5 5.6 16A8 8 0 1 1 8.4 18.6z"/><path d="M9.2 8.6c0 3.3 2.8 6.2 6.2 6.2l1-1.6-2-1-1 .8a4.5 4.5 0 0 1-2.4-2.4l.8-1-1-2z"/></svg>',
  mail:'<svg viewBox="0 0 24 24"><rect x="3.5" y="5.5" width="17" height="13" rx="2.5"/><path d="m4.5 7 7.5 6 7.5-6"/></svg>',
  sun:'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="4"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/></svg>',
  ban:'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="m6 6 12 12"/></svg>',
  star:'<svg viewBox="0 0 24 24"><path d="m12 4 2.4 5 5.4.6-4 3.7 1.1 5.3L12 16l-4.9 2.6 1.1-5.3-4-3.7 5.4-.6z"/></svg>',
  chev:'<svg class="chev" viewBox="0 0 24 24"><path d="m9.5 6 6 6-6 6"/></svg>',
  search:'<svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="6"/><path d="m20 20-4.5-4.5"/></svg>',
  warn:'<svg viewBox="0 0 24 24"><path d="M12 4 2.8 19.5h18.4z"/><path d="M12 10v4M12 16.8v.2"/></svg>',
  info:'<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8.5"/><path d="M12 11v5M12 8v.2"/></svg>',
  lock:'<svg viewBox="0 0 24 24"><rect x="5" y="10.5" width="14" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/></svg>'
};

/* ---------- Zugang ---------- */
const hp=new URLSearchParams(location.hash.slice(1));
let KEY=hp.get('k');
try{if(KEY)localStorage.setItem('sunny-studio-k',KEY);else KEY=localStorage.getItem('sunny-studio-k')}catch(e){}
const flashMsg={storniert:'Termin storniert',frei:'Zeit wieder freigegeben',blockiert:'Zeit blockiert'}[hp.get('msg')];
if(KEY&&hp.get('msg'))history.replaceState(null,'','#k='+KEY);
function lock(){$('#app').hidden=true;$('#locked').hidden=false}
if(!KEY){lock();return}

async function api(a,params={},body){
  const q=new URLSearchParams({a,...params});
  const r=await fetch(API+'?'+q,{method:body?'POST':'GET',cache:'no-store',headers:{'X-Studio-Key':KEY,...(body?{'Content-Type':'application/json'}:{})},body:body?JSON.stringify(body):undefined});
  const j=await r.json().catch(()=>({ok:false,error:'net'}));
  if(r.status===403&&j.error==='key'){try{localStorage.removeItem('sunny-studio-k')}catch(e){}lock();throw new Error('key')}
  if(!r.ok||!j.ok){const e=new Error(j.error||'net');e.data=j;e.status=r.status;throw e}
  return j;
}

/* ---------- Zustand ---------- */
const today=()=>{const d=new Date();d.setHours(0,0,0,0);return d};
const S={sec:'cal',view:mq.matches?'month':'week',cur:today(),sel:iso(today()),data:null,meta:null,inbox:null,clos:null,scrollTo:true};
const isMobile=()=>mq.matches;

/* ---------- Hilfen für Tage ---------- */
function info(ds,data=S.data){
  const d=day(ds),wd=d.getDay(),out={hours:(data?.hours||S.meta?.hours||{})[wd]||null,state:'open',label:'',kind:''};
  if(!out.hours){out.state='shut'}
  const h=(data?.holidays||[]).find(x=>x.date===ds);
  if(h&&!h.open){out.state='hol';out.label=h.name;out.kind='feiertag'}
  if(h&&h.open){out.openHoliday=h.name}
  const c=(data?.closures||[]).find(x=>x.from<=ds&&x.to>=ds);
  if(c){out.closure=c;out.kind=c.kind;out.state=c.kind==='urlaub'?'vac':c.kind==='feiertag'?'hol':'clo';out.label=c.label||KIND[c.kind]}
  return out;
}
const tagHtml=i=>i.state==='vac'||i.state==='hol'||i.state==='clo'?`<span class="tag ${i.state}">${esc(i.label)}</span>`:i.openHoliday?`<span class="tag opn">${esc(i.openHoliday)} · geöffnet</span>`:'';
const evCls=e=>e.kind==='block'?'block':e.source==='studio'?'studio':'online';
const evTitle=e=>e.kind==='block'?(e.note||'Blockiert'):e.name;
const evSub=e=>e.kind==='block'?'Blockierte Zeit':e.services.join(', ');
const byDate=()=>{const m={};for(const e of S.data?.events||[])(m[e.date]||(m[e.date]=[])).push(e);return m};
const waNum=p=>{let d=String(p||'').replace(/[^\d+]/g,'');if(d.startsWith('+'))d=d.slice(1);else if(d.startsWith('00'))d=d.slice(2);else if(d.startsWith('0'))d='49'+d.slice(1);return d};
const rel=s=>{
  const t=new Date(s),now=new Date(),min=Math.round((now-t)/60000);
  if(min<1)return 'gerade eben';if(min<60)return 'vor '+min+' Min.';
  const d0=today(),dd=new Date(t);dd.setHours(0,0,0,0);const diff=Math.round((d0-dd)/864e5);
  if(diff===0)return 'heute, '+fTime.format(t);if(diff===1)return 'gestern, '+fTime.format(t);
  if(diff<7)return WDL[t.getDay()]+', '+fTime.format(t);return fDM.format(t);
};

/* ---------- Toast ---------- */
let tt;function toast(msg,ms=2600){const t=$('#toast');t.textContent=msg;t.classList.add('on');clearTimeout(tt);tt=setTimeout(()=>t.classList.remove('on'),ms)}

/* ---------- Bereiche ---------- */
function go(sec){
  S.sec=sec;
  $$('[data-sec]').forEach(b=>{if(b.dataset.sec===sec)b.setAttribute('aria-current','page');else b.removeAttribute('aria-current')});
  ['cal','inbox','closures'].forEach(s=>$('#sec-'+s).hidden=s!==sec);
  if(sec==='inbox')loadInbox();
  if(sec==='closures')loadClosures();
  if(sec==='cal')load();
}
$$('[data-sec]').forEach(b=>b.addEventListener('click',()=>go(b.dataset.sec)));
function setBadge(n){S.badge=n;[$('#badge'),$('#badge2')].forEach(b=>{b.hidden=!n;b.textContent=n>99?'99+':n})}

/* ---------- Kalender laden ---------- */
function range(){
  const c=S.cur;
  if(S.view==='day')return [c,c];
  if(S.view==='week'){const m=monday(c);return [m,add(m,6)]}
  const first=new Date(c.getFullYear(),c.getMonth(),1),s=monday(first);return [s,add(s,41)];
}
let req=0;
async function load(){
  const [a,b]=range(),my=++req;
  try{
    const j=await api('data',{from:iso(a),to:iso(b)});
    if(my!==req)return;
    S.data=j;S.meta={hours:j.hours,services:j.services};setBadge(j.inbox);
    $('#app').removeAttribute('aria-busy');
    render();
  }catch(e){if(e.message!=='key'&&my===req){$('#view').innerHTML=`<div class="pad"><div class="note warn">${ICON.warn}<div>Der Kalender konnte nicht geladen werden. Prüfe die Internetverbindung und versuche es erneut.<br><button type="button" class="txt" id="retry">Erneut laden</button></div></div></div>`;$('#retry').onclick=load}}
}

function render(){
  const v=$('#view');
  $$('#views button').forEach(b=>b.setAttribute('aria-selected',b.dataset.view===S.view));
  const c=S.cur;
  if(S.view==='month')$('#title').innerHTML=esc(fMonth.format(c))+'<small>'+c.getFullYear()+'</small>';
  else if(S.view==='week'){const m=monday(c),e=add(m,6);$('#title').innerHTML=esc(m.getMonth()===e.getMonth()?fMonth.format(m):fDM.format(m)+' – '+fDM.format(e))+'<small>KW '+kw(m)+' · '+e.getFullYear()+'</small>'}
  else $('#title').innerHTML=esc(c.getDate()+'. '+fMonth.format(c))+'<small>'+WDL[c.getDay()]+' · '+c.getFullYear()+'</small>';
  const keep=$('.gscroll')?.scrollTop;
  if(S.view==='month')v.innerHTML=renderMonth()+(isMobile()?renderAgenda([day(S.sel)],true):'');
  else if(S.view==='week'&&isMobile()){const m=monday(c);v.innerHTML=renderAgenda([...Array(7)].map((_,i)=>add(m,i)))}
  else v.innerHTML=renderGrid(S.view==='day'?[c]:[...Array(7)].map((_,i)=>add(monday(c),i)));
  const sc=$('.gscroll');
  if(sc){if(S.scrollTo||keep==null){sc.scrollTop=Math.max(0,(firstHour()-S.minH)*hourPx()-8)}else sc.scrollTop=keep}
  S.scrollTo=false;
}
function kw(d){const t=new Date(Date.UTC(d.getFullYear(),d.getMonth(),d.getDate()));const n=t.getUTCDay()||7;t.setUTCDate(t.getUTCDate()+4-n);const y=new Date(Date.UTC(t.getUTCFullYear(),0,1));return Math.ceil(((t-y)/864e5+1)/7)}

/* Monat */
function renderMonth(){
  const [s]=range(),ev=byDate(),t=iso(today()),mob=isMobile(),month=S.cur.getMonth();
  let h='<div class="month"><div class="wd">'+['Mo','Di','Mi','Do','Fr','Sa','So'].map(x=>`<span>${x}</span>`).join('')+'</div><div class="mgrid">';
  for(let i=0;i<42;i++){
    const d=add(s,i),ds=iso(d),inf=info(ds),list=(ev[ds]||[]);
    const cls=['mc',d.getMonth()!==month?'out':'',inf.state!=='open'?inf.state:'',ds===t?'is-today':'',mob&&ds===S.sel?'sel':''].join(' ');
    const max=3,chips=list.slice(0,max).map(e=>`<button type="button" class="mchip ${evCls(e)}" data-id="${e.id}"><i></i><span class="t">${fmtT(e.start)}</span><span class="x">${esc(evTitle(e))}</span></button>`).join('');
    const label=fLong.format(d)+(list.length?', '+list.length+(list.length===1?' Eintrag':' Einträge'):'')+(inf.label?', '+inf.label:'');
    h+=`<div class="${cls}" data-d="${ds}"><button type="button" class="n" data-day="${ds}" aria-label="${esc(label)}">${d.getDate()}</button>${tagHtml(inf)}${chips}${list.length>max?`<span class="more">${list.length-max} weitere</span>`:''}<span class="mdots" aria-hidden="true">${list.slice(0,3).map(e=>`<i class="${evCls(e)}"></i>`).join('')}</span></div>`;
  }
  return h+'</div></div>';
}

/* Agenda-Liste (Handy) */
function renderAgenda(days,single){
  const ev=byDate();
  let h='<div class="agenda">';
  for(const d of days){
    const ds=iso(d),inf=info(ds),list=ev[ds]||[];
    h+=`<div class="ag-day">${esc(fLong.format(d))}${ds===iso(today())?' <small>Heute</small>':''} ${tagHtml(inf)}</div>`;
    if(list.length)h+='<div class="group">'+list.map(rowEvent).join('')+'</div>';
    else h+=`<div class="group"><div class="row"><div class="main-t"><span>${inf.state==='open'?'Noch keine Termine · '+inf.hours.map(r=>r[0]+'–'+r[1]+' Uhr').join(', '):inf.state==='shut'?'Regulär geschlossen':esc(inf.label)}</span></div></div></div>`;
    if(single)h+=`<div class="group"><button type="button" class="row" data-new="${ds}"><div class="main-t"><b style="color:var(--tint)">Termin an diesem Tag eintragen</b></div>${ICON.chev}</button><button type="button" class="row" data-closeday="${ds}"><div class="main-t"><b style="color:var(--tint)">${inf.closure?'Sperrzeit bearbeiten':'Diesen Tag sperren'}</b></div>${ICON.chev}</button></div>`;
  }
  return h+'</div>';
}
function rowEvent(e){
  return `<button type="button" class="row ind" data-id="${e.id}"><span class="lead ${evCls(e)}">${e.kind==='block'?ICON.lock:e.source==='studio'?ICON.star:ICON.cal}</span><span class="main-t"><b>${esc(evTitle(e))}${e.seen===0?' <span class="tag clo" style="font-size:10px;padding:0 5px;border-radius:4px;vertical-align:1px">Neu</span>':''}</b><span>${esc(evSub(e))}</span></span><span class="trail">${fmtT(e.start)}<br>${fmtT(e.end)}</span>${ICON.chev}</button>`;
}

/* Zeitraster */
const hourPx=()=>parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--hour'))||54;
function firstHour(){
  const n=new Date(),[a,b]=range();
  if(today()>=a&&today()<=b)return Math.max(S.minH,n.getHours()-1);
  const hs=Object.values(S.data?.hours||{}).flat();return hs.length?Math.min(...hs.map(r=>r[0])):8;
}
function renderGrid(days){
  const ev=byDate(),H=hourPx(),t=iso(today());
  let minH=7,maxH=21;
  for(const d of days)for(const e of ev[iso(d)]||[]){minH=Math.min(minH,Math.floor(e.start/60));maxH=Math.max(maxH,Math.ceil(e.end/60))}
  for(const r of Object.values(S.data?.hours||{}).flat()){minH=Math.min(minH,r[0]);maxH=Math.max(maxH,r[1])}
  S.minH=minH;
  const cols=`56px repeat(${days.length},minmax(0,1fr))`,hh=(maxH-minH)*H;
  let head=`<div class="ghead" style="grid-template-columns:${cols}"><div></div>`;
  let body=`<div class="gbody" style="grid-template-columns:${cols};height:${hh+12}px"><div class="gtimes">`;
  for(let h=minH+1;h<maxH;h++)body+=`<span style="top:${(h-minH)*H}px">${h}:00</span>`;
  body+='</div>';
  for(const d of days){
    const ds=iso(d),inf=info(ds),list=(ev[ds]||[]).slice().sort((a,b)=>a.start-b.start||b.end-a.end);
    head+=`<button type="button" class="gh ${ds===t?'is-today':''}" data-day="${ds}" ${days.length===1?'tabindex="-1"':''}>${WD[d.getDay()]}<b>${d.getDate()}</b>${tagHtml(inf)}</button>`;
    let col=`<div class="gcol ${['hol','vac','clo'].includes(inf.state)?inf.state:''}" data-d="${ds}">`;
    for(const r of inf.hours||[])col+=`<div class="open" style="top:${(r[0]-minH)*H}px;height:${(r[1]-r[0])*H}px"></div>`;
    for(let h=minH;h<maxH;h++){col+=`<div class="hl" style="top:${(h-minH)*H}px"></div><div class="hl half" style="top:${(h-minH+.5)*H}px"></div>`}
    layout(list);
    for(const e of list){
      const top=(e.start/60-minH)*H,ht=Math.max(20,(e.end-e.start)/60*H-2),w=100/e._n;
      const short=ht<38;
      col+=`<button type="button" class="ev ${evCls(e)}${e.seen===0?' new':''}" data-id="${e.id}" style="top:${top+1}px;height:${ht}px;left:calc(${e._c*w}% + 2px);width:calc(${w}% - 4px)" aria-label="${esc(fmtT(e.start)+' bis '+fmtT(e.end)+', '+evTitle(e)+', '+evSub(e))}">${short?`<b><span class="tm">${fmtT(e.start)}</span> ${esc(evTitle(e))}</b>`:`<b>${esc(evTitle(e))}</b><span class="tm">${fmtT(e.start)} – ${fmtT(e.end)}</span>${ht>60?`<span>${esc(evSub(e))}</span>`:''}`}</button>`;
    }
    if(ds===t){const n=new Date(),m=n.getHours()*60+n.getMinutes();if(m>=minH*60&&m<=maxH*60)col+=`<div class="nowl" style="top:${(m/60-minH)*H}px"></div>`}
    body+=col+'</div>';
  }
  let tools='';
  if(days.length===1){const ds=iso(days[0]),inf=info(ds);tools=`<div style="display:flex;gap:4px;justify-content:flex-end;margin:-6px 24px 6px"><button type="button" class="txt" data-closeday="${ds}">${inf.closure?'Sperrzeit bearbeiten':'Diesen Tag sperren'}</button></div>`}
  return tools+`<div class="grid-wrap">${head}</div><div class="gscroll">${body}</div></div></div>`;
}
function layout(list){
  let cluster=[],end=-1;
  const flush=()=>{const cols=[];for(const e of cluster){let i=cols.findIndex(x=>x<=e.start);if(i<0){i=cols.length;cols.push(0)}cols[i]=e.end;e._c=i}for(const e of cluster)e._n=cols.length;cluster=[]};
  for(const e of list){if(e.start>=end&&cluster.length){flush();end=-1}cluster.push(e);end=Math.max(end,e.end)}
  if(cluster.length)flush();
}

/* ---------- Kalender-Interaktion ---------- */
$('#views').addEventListener('click',e=>{const b=e.target.closest('[data-view]');if(!b)return;setView(b.dataset.view)});
function setView(v){if(S.view===v)return;S.view=v;S.scrollTo=true;if(v!=='month')S.cur=day(S.sel);load()}
function step(n){
  const c=S.cur;
  if(S.view==='day')S.cur=add(c,n);else if(S.view==='week')S.cur=add(c,7*n);else S.cur=new Date(c.getFullYear(),c.getMonth()+n,1);
  if(S.view!=='month')S.sel=iso(S.cur);
  else if(isMobile()){const s=day(S.sel);if(s.getMonth()!==S.cur.getMonth()||s.getFullYear()!==S.cur.getFullYear())S.sel=iso(S.cur)}
  S.scrollTo=true;load();
}
$('#prev').onclick=()=>step(-1);$('#next').onclick=()=>step(1);
$('#today').onclick=()=>{S.cur=today();S.sel=iso(S.cur);S.scrollTo=true;load()};
$('#add').onclick=()=>openEditor({date:S.view==='month'&&!isMobile()?iso(today()):S.sel});
$('#view').addEventListener('click',e=>{
  const evb=e.target.closest('[data-id]');
  if(evb){const ev=(S.data.events||[]).find(x=>x.id===evb.dataset.id);if(ev)openEvent(ev);return}
  const nw=e.target.closest('[data-new]');if(nw){openEditor({date:nw.dataset.new});return}
  const cd=e.target.closest('[data-closeday]');
  if(cd){const inf=info(cd.dataset.closeday);if(inf.closure)openClosure(inf.closure);else openClosure(null,cd.dataset.closeday);return}
  const dd=e.target.closest('[data-day]')||(S.view==='month'?e.target.closest('.mc'):null);
  if(dd){const ds=dd.dataset.day||dd.dataset.d;
    if(S.view==='month'&&isMobile()){S.sel=ds;const d=day(ds);if(d.getMonth()!==S.cur.getMonth()){S.cur=new Date(d.getFullYear(),d.getMonth(),1);load()}else render();return}
    S.sel=ds;S.cur=day(ds);S.view='day';S.scrollTo=true;load();return}
  const col=e.target.closest('.gcol');
  if(col){const r=col.getBoundingClientRect(),y=e.clientY-r.top,m=Math.floor((S.minH*60+y/hourPx()*60)/15)*15;openEditor({date:col.dataset.d,start:m})}
});
mq.addEventListener('change',()=>{if(S.sec==='cal')render()});
document.addEventListener('keydown',e=>{
  if($('#sheet').open||e.metaKey||e.ctrlKey||e.altKey||/INPUT|TEXTAREA|SELECT/.test(document.activeElement?.tagName)||S.sec!=='cal')return;
  const k=e.key;
  if(k==='ArrowLeft')step(-1);else if(k==='ArrowRight')step(1);else if(k==='t'||k==='h')$('#today').click();
  else if(k==='n'){e.preventDefault();$('#add').click()}else if(k==='d')setView('day');else if(k==='w')setView('week');else if(k==='m')setView('month');
});

/* ---------- Sheet ---------- */
const dlg=$('#sheet'),sin=$('#sheet-in');
function sheet(title,left,right,body){
  sin.innerHTML=`<header class="sh-bar"><span class="l">${left||''}</span><h2 id="sheet-t">${esc(title)}</h2><span class="r">${right||''}</span></header><div class="sh-body">${body}</div>`;
  if(!dlg.open)dlg.showModal();
  sin.querySelector('.sh-body').scrollTop=0;
}
function closeSheet(){if(dlg.open)dlg.close()}
dlg.addEventListener('click',e=>{if(e.target===dlg)closeSheet()});
sin.addEventListener('submit',e=>e.preventDefault());
const kv=(k,v)=>v?`<div class="kv"><dt>${esc(k)}</dt><dd>${v}</dd></div>`:'';

/* Termin ansehen */
function openEvent(ev,opts={}){
  const d=day(ev.date),isB=ev.kind==='block',cls=evCls(ev);
  if(ev.seen===0){api('seen',{},{ids:[ev.id]}).then(j=>{setBadge(j.inbox);ev.seen=1;if(S.sec==='cal')render();else if(S.sec==='inbox')drawInbox()}).catch(()=>{})}
  const src=isB?'Blockierte Zeit':ev.source==='studio'?'Selbst eingetragen':'Online gebucht';
  const tel=String(ev.phone||'').replace(/[^\d+]/g,'');
  const cancelled=ev.status==='storniert';
  let b=`<div class="hero-ev"><div class="k ${cls}"><i class="dot ${cls}"></i>${esc(src)}${cancelled?' · <span class="danger">von der Kundin abgesagt</span>':''}</div><h3>${esc(evTitle(ev))}</h3><p>${esc(fLongY.format(d))}<br><span class="tnum">${fmtT(ev.start)} – ${fmtT(ev.end)} Uhr</span> · ${fmtDur(ev.end-ev.start)}</p></div>`;
  if(!isB&&(tel||ev.email))b+=`<div class="acts">${tel?`<a class="act" href="tel:${esc(tel)}">${ICON.phone}Anrufen</a><a class="act" href="https://wa.me/${esc(waNum(ev.phone))}" target="_blank" rel="noopener">${ICON.wa}WhatsApp</a>`:''}${ev.email?`<a class="act" href="mailto:${esc(ev.email)}">${ICON.mail}E-Mail</a>`:''}</div>`;
  if(!isB)b+=`<dl class="group">${kv('Leistungen',esc(ev.services.join('\n')))}${kv('Preis',ev.price!=null?ev.price+' €':'nach Aufwand')}${kv('Telefon',tel?`<a href="tel:${esc(tel)}">${esc(ev.phone)}</a>`:'')}${kv('E-Mail',ev.email?`<a href="mailto:${esc(ev.email)}">${esc(ev.email)}</a>`:'')}${kv('Notiz',esc(ev.note))}${ev.source==='online'?kv('Gebucht',esc(rel(ev.created))):''}</dl>`;
  else b+=`<p class="gft" style="margin:0 4px 22px">In dieser Zeit ist online keine Buchung möglich.</p>`;
  if(!cancelled)b+=`<button type="button" class="btn-row danger" id="ev-cancel">${isB?'Blocker löschen':'Termin stornieren'}</button>`;
  sheet(isB?'Blocker':'Termin','<button type="button" data-close>Schließen</button>',cancelled?'':'<button type="button" id="ev-edit">Bearbeiten</button>',b);
  sin.querySelector('[data-close]').onclick=closeSheet;
  $('#ev-edit')&&($('#ev-edit').onclick=()=>openEditor(null,ev));
  $('#ev-cancel')&&($('#ev-cancel').onclick=()=>isB?doCancel(ev,false):confirmCancel(ev));
}
function confirmCancel(ev){
  const has=!!ev.email,tel=String(ev.phone||'').replace(/[^\d+]/g,'');
  sheet('Stornieren','<button type="button" id="cc-back">Zurück</button>','',
    `<div class="hero-ev"><h3>Termin von ${esc(ev.name)} stornieren?</h3><p>${esc(fLong.format(day(ev.date)))}, ${fmtT(ev.start)} Uhr · ${esc(ev.services.join(', '))}</p></div>
    ${has?`<div class="group"><label class="fld"><span style="width:auto;flex:1">Kundin per E-Mail informieren</span><input type="checkbox" class="sw" id="cc-mail" checked></label></div><p class="gft">Sie bekommt eine kurze Nachricht mit Link zur Neubuchung.</p>`
      :`<div class="note info">${ICON.info}<div>Keine E-Mail hinterlegt. Bitte gib der Kundin telefonisch Bescheid${tel?`: <a href="tel:${esc(tel)}" style="color:inherit;font-weight:600">${esc(ev.phone)}</a>`:''}.</div></div>`}
    <button type="button" class="btn-row danger" id="cc-go">Termin stornieren</button>`);
  $('#cc-back').onclick=()=>openEvent(ev);
  $('#cc-go').onclick=()=>doCancel(ev,has&&$('#cc-mail').checked);
}
async function doCancel(ev,notify){
  const b=$('#cc-go')||$('#ev-cancel');if(b){b.disabled=true;b.textContent='Einen Moment …'}
  try{const j=await api('cancel',{},{id:ev.id,notify});closeSheet();toast(ev.kind==='block'?'Blocker gelöscht, Zeit wieder buchbar':'Termin storniert'+(j.mailed?' · Kundin informiert':''));refresh()}
  catch(e){if(b){b.disabled=false;b.textContent='Erneut versuchen'}toast('Das hat nicht geklappt. Bitte erneut versuchen.')}
}

/* Termin anlegen / bearbeiten */
function nextFree(ds){
  // erste freie Stunde innerhalb der Öffnungszeiten (heute erst ab jetzt), sonst Beginn der Öffnungszeit
  const inf=info(ds),hrs=inf.hours||[[9,18]],evs=rangeHas(ds)?(S.data?.events||[]).filter(e=>e.date===ds):[];
  const n=new Date(),nowM=ds===iso(today())?Math.ceil((n.getHours()*60+n.getMinutes())/15)*15:0;
  for(const r of hrs)for(let m=Math.max(r[0]*60,nowM);m+60<=r[1]*60;m+=15)if(!evs.some(e=>e.start<m+60&&e.end>m))return m;
  return hrs[0][0]*60;
}
let dayCache={};
async function dayData(ds){
  if(S.data&&S.data.events&&rangeHas(ds))return S.data;
  if(dayCache[ds])return dayCache[ds];
  try{return dayCache[ds]=await api('data',{from:ds,to:ds})}catch(e){return null}
}
function rangeHas(ds){const [a,b]=range();return ds>=iso(a)&&ds<=iso(b)}
function openEditor(pre,ev){
  const svcs=S.meta?.services||[];
  const E=ev?{id:ev.id,kind:ev.kind,date:ev.date,start:ev.start,end:ev.end,name:ev.name,phone:ev.phone,email:ev.email,note:ev.note,price:ev.price,
      services:ev.services.map(n=>svcs.find(s=>s.name===n)?.id).filter(Boolean),custom:ev.services.filter(n=>!svcs.some(s=>s.name===n)).join(', '),manualEnd:true,orig:ev}
    :{kind:pre?.kind||'termin',date:pre?.date||iso(today()),start:pre?.start??null,end:null,name:pre?.name||'',phone:pre?.phone||'',email:pre?.email||'',note:pre?.note||'',price:null,services:[],custom:'',manualEnd:false};
  if(E.start==null)E.start=nextFree(E.date);
  if(E.end==null)E.end=E.start+60;
  let force=false;
  const svcMin=()=>E.services.reduce((a,id)=>a+(svcs.find(s=>s.id===id)?.minutes||0),0);
  const autoPrice=()=>{const l=E.services.map(id=>svcs.find(s=>s.id===id));if(!l.length||E.custom||l.some(s=>s?.price==null))return null;return l.reduce((a,s)=>a+s.price,0)};

  function draw(){
    const isB=E.kind==='block';
    const body=`
      ${ev?'':`<div class="seg full" role="tablist"><button type="button" role="tab" data-k="termin" aria-selected="${!isB}">Termin</button><button type="button" role="tab" data-k="block" aria-selected="${isB}">Zeit blockieren</button></div>`}
      <div id="ed-warn"></div>
      ${isB?`
      <div class="group"><label class="fld"><span>Grund</span><input id="f-note" value="${esc(E.note)}" placeholder="z. B. Pause, Arzttermin" maxlength="80"></label></div>`:`
      <div class="ghd">Kundin</div>
      <div class="group">
        <label class="fld"><span>Name</span><input id="f-name" value="${esc(E.name)}" placeholder="Vor- und Nachname" autocomplete="off" maxlength="80"></label>
        <label class="fld"><span>Telefon</span><input id="f-phone" type="tel" value="${esc(E.phone)}" placeholder="optional" autocomplete="off" maxlength="40"></label>
        <label class="fld"><span>E-Mail</span><input id="f-email" type="email" value="${esc(E.email)}" placeholder="optional" autocomplete="off" maxlength="120"></label>
      </div>
      <div class="ghd">Leistungen</div>
      <div class="group">
        <div class="svc-pick"><div class="tokens" id="f-tok"></div>
          <div class="search">${ICON.search}<input id="f-q" type="search" placeholder="Leistung suchen" autocomplete="off" aria-label="Leistung suchen"></div>
          <div class="svc-list" id="f-svc"></div></div>
        <label class="fld"><span>Sonstiges</span><input id="f-custom" value="${esc(E.custom)}" placeholder="z. B. Beratung" maxlength="80"></label>
      </div>`}
      <div class="ghd">Zeit</div>
      <div class="group">
        <label class="fld"><span>Datum</span><input id="f-date" type="date" value="${E.date}" required></label>
        <label class="fld"><span>Beginn</span><input id="f-start" type="time" step="900" value="${hhmm(E.start)}" required></label>
        <label class="fld"><span>Ende</span><input id="f-end" type="time" step="900" value="${hhmm(Math.min(E.end,1439))}" required></label>
        ${isB?`<label class="fld"><span style="width:auto;flex:1">Ganzer Tag</span><input type="checkbox" class="sw" id="f-all" ${E.start<=360&&E.end>=1320?'checked':''}></label>`:`<div class="fld"><span>Dauer</span><span class="hint" id="f-dur"></span></div>`}
      </div>
      ${isB?`<p class="gft" style="margin-top:-14px">Blockierte Zeiten sind online nicht buchbar. Für Urlaub oder ganze Schließtage nutze besser „Sperrzeiten“.</p>`:`
      <div class="group">
        <label class="fld"><span>Preis</span><input id="f-price" type="number" inputmode="numeric" min="0" max="9999" value="${E.price!=null&&ev?E.price:''}" placeholder="automatisch"><span class="hint">€</span></label>
        <label class="fld tall"><span>Notiz</span><textarea id="f-note" rows="2" maxlength="300" placeholder="z. B. Shellac in Nude">${esc(E.note)}</textarea></label>
      </div>
      <div id="f-notify-wrap"></div>`}
      <p class="err" id="f-err" role="alert"></p>
      ${ev?`<button type="button" class="btn-row danger" id="f-del">${isB?'Blocker löschen':'Termin stornieren'}</button>`:''}`;
    sheet(ev?(isB?'Blocker bearbeiten':'Termin bearbeiten'):(isB?'Zeit blockieren':'Neuer Termin'),'<button type="button" data-close>Abbrechen</button>',`<button type="button" id="f-save">${ev?'Sichern':'Hinzufügen'}</button>`,body);
    sin.querySelector('[data-close]').onclick=()=>ev?openEvent(ev):closeSheet();
    $$('.seg.full [data-k]',sin).forEach(b=>b.onclick=()=>{read();E.kind=b.dataset.k;if(E.kind==='block'&&!E.manualEnd)E.end=E.start+60;draw()});
    $('#f-save').onclick=save;
    $('#f-del')&&($('#f-del').onclick=()=>isB?doCancel(ev,false):confirmCancel(ev));
    if(!isB){drawSvc('');$('#f-q').oninput=e=>drawSvc(e.target.value);$('#f-email').oninput=()=>{read();notifyRow()};notifyRow()}
    $('#f-date').onchange=()=>{read();check()};
    $('#f-start').onchange=()=>{const d=E.end-E.start;read();const s=toMin($('#f-start').value);if(!isNaN(s)){E.start=s;E.end=Math.min(1440,s+d);$('#f-end').value=hhmm(Math.min(E.end,1439))}dur();check();notifyRow()};
    $('#f-end').onchange=()=>{E.manualEnd=true;read();dur();check()};
    $('#f-all')&&($('#f-all').onchange=e=>{if(e.target.checked){E.start=360;E.end=1320}else{E.start=480;E.end=540}$('#f-start').value=hhmm(E.start);$('#f-end').value=hhmm(E.end);check()});
    dur();check();
    if(!ev&&!isB)setTimeout(()=>$('#f-name')?.focus({preventScroll:true}),isMobile()?380:60);
  }
  function read(){
    const v=id=>{const el=$('#'+id);return el?el.value.trim():null};
    if(v('f-name')!==null)E.name=v('f-name');if(v('f-phone')!==null)E.phone=v('f-phone');if(v('f-email')!==null)E.email=v('f-email');
    if(v('f-note')!==null)E.note=v('f-note');if(v('f-custom')!==null)E.custom=v('f-custom');
    if(v('f-price')!==null)E.price=v('f-price')===''?null:Number(v('f-price'));
    if(v('f-date'))E.date=v('f-date');
    const s=toMin(v('f-start')),en=toMin(v('f-end'));if(!isNaN(s))E.start=s;if(!isNaN(en))E.end=en===1439?1440:en;
  }
  function dur(){const el=$('#f-dur');if(el)el.textContent=E.end>E.start?fmtDur(E.end-E.start)+(svcMin()&&svcMin()!==E.end-E.start?' (Leistungen: '+fmtDur(svcMin())+')':''):'–';const p=$('#f-price');if(p)p.placeholder=autoPrice()!=null?autoPrice()+' (automatisch)':'automatisch'}
  function drawSvc(q){
    const box=$('#f-svc'),tok=$('#f-tok');q=q.toLowerCase();
    tok.innerHTML=E.services.map(id=>{const s=svcs.find(x=>x.id===id);return `<button type="button" class="tok" data-rm="${id}" aria-label="${esc(s.name)} entfernen">${esc(s.name)}${ICON.x}</button>`}).join('');
    const cats=[...new Set(svcs.map(s=>s.cat))];
    box.innerHTML=cats.map(c=>{const l=svcs.filter(s=>s.cat===c&&(!q||s.name.toLowerCase().includes(q)||c.toLowerCase().includes(q)));return l.length?`<div class="svc-cat">${esc(c)}</div>`+l.map(s=>`<button type="button" class="svc-o" data-s="${s.id}" aria-pressed="${E.services.includes(s.id)}"><span class="ck">${ICON.check}</span>${esc(s.name)}<small>${fmtDur(s.minutes)}${s.price!=null?' · '+s.price+' €':''}</small></button>`).join(''):''}).join('')||'<p class="empty" style="padding:14px">Nichts gefunden. Trag die Leistung unter „Sonstiges“ ein.</p>';
    box.onclick=e=>{const b=e.target.closest('[data-s]');if(!b)return;toggle(b.dataset.s);b.setAttribute('aria-pressed',E.services.includes(b.dataset.s));drawTok()};
    tok.onclick=e=>{const b=e.target.closest('[data-rm]');if(!b)return;toggle(b.dataset.rm);drawSvc($('#f-q').value)};
  }
  function drawTok(){const tok=$('#f-tok');tok.innerHTML=E.services.map(id=>{const s=svcs.find(x=>x.id===id);return `<button type="button" class="tok" data-rm="${id}" aria-label="${esc(s.name)} entfernen">${esc(s.name)}${ICON.x}</button>`}).join('')}
  function toggle(id){
    read();E.services=E.services.includes(id)?E.services.filter(x=>x!==id):[...E.services,id];
    if(!E.manualEnd&&svcMin()){E.end=Math.min(1440,E.start+Math.ceil(svcMin()/15)*15);$('#f-end').value=hhmm(Math.min(E.end,1439))}
    dur();check();
  }
  function notifyRow(){
    const w=$('#f-notify-wrap');if(!w)return;
    const valid=/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(E.email||'');
    const moved=ev&&(E.date!==ev.date||E.start!==ev.start);
    if(!valid||(ev&&!moved)){w.innerHTML='';return}
    const was=$('#f-notify')?$('#f-notify').checked:true;
    w.innerHTML=`<div class="group"><label class="fld"><span style="width:auto;flex:1">${ev?'Kundin über neue Zeit informieren':'Bestätigung per E-Mail senden'}</span><input type="checkbox" class="sw" id="f-notify" ${was?'checked':''}></label></div><p class="gft">Mit Kalendereintrag und Link zum Absagen.</p>`;
  }
  let ck=0;
  async function check(){
    force=false;$('#f-save').textContent=ev?'Sichern':'Hinzufügen';$('#f-err').textContent='';
    const my=++ck,ds=E.date,w=$('#ed-warn');if(!ds)return;
    const data=await dayData(ds);if(my!==ck||!data)return;
    const inf=info(ds,data),notes=[];
    if(['vac','hol','clo'].includes(inf.state))notes.push(['warn',`An diesem Tag ist <b>${esc(inf.label)}</b> eingetragen. Du kannst den Eintrag trotzdem speichern.`]);
    else if(inf.state==='shut')notes.push(['info',`${WDL[day(ds).getDay()]}s ist regulär geschlossen. Online kann an diesem Tag niemand buchen.`]);
    else if(E.kind==='termin'&&!inf.hours.some(r=>E.start>=r[0]*60&&E.end<=r[1]*60))notes.push(['info','Außerhalb der Öffnungszeiten ('+inf.hours.map(r=>r[0]+'–'+r[1]+' Uhr').join(', ')+').']);
    const clash=(data.events||[]).filter(x=>x.date===ds&&x.id!==E.id&&x.start<E.end&&x.end>E.start);
    if(clash.length)notes.push(['warn','Überschneidet sich mit:<ul>'+clash.map(x=>`<li>${fmtT(x.start)}–${fmtT(x.end)} ${esc(evTitle(x))}</li>`).join('')+'</ul>']);
    w.innerHTML=notes.map(([t,h])=>`<div class="note ${t}">${t==='warn'?ICON.warn:ICON.info}<div>${h}</div></div>`).join('');
  }
  async function save(){
    read();const err=$('#f-err'),btn=$('#f-save');err.textContent='';
    if(!E.date){err.textContent='Bitte ein Datum wählen.';return}
    if(!(E.end>E.start)){err.textContent='Das Ende muss nach dem Beginn liegen.';return}
    if(E.kind==='termin'){
      if(!E.name){err.textContent='Bitte den Namen der Kundin eintragen.';$('#f-name').focus();return}
      if(!E.services.length&&!E.custom){err.textContent='Bitte mindestens eine Leistung wählen oder unter „Sonstiges“ eintragen.';return}
      if(E.email&&!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(E.email)){err.textContent='Die E-Mail-Adresse sieht unvollständig aus.';return}
    }
    btn.disabled=true;
    try{
      const j=await api('save',{},{id:E.id||'',kind:E.kind,date:E.date,start:E.start,end:E.end,name:E.name,phone:E.phone,email:E.email,note:E.note,
        services:E.services,custom:E.custom,price:E.price,notify:!!$('#f-notify')?.checked,force});
      closeSheet();dayCache={};
      toast((E.kind==='block'?'Zeit blockiert':ev?'Termin gesichert':'Termin eingetragen')+(j.mailed?' · E-Mail gesendet':''));
      if(S.sec!=='cal')go('cal');
      const d=day(E.date);if(!rangeHas(E.date)){S.cur=d;S.sel=E.date}
      refresh();
    }catch(e){
      btn.disabled=false;
      if(e.message==='conflict'){
        force=true;btn.textContent='Trotzdem sichern';
        err.innerHTML='Diese Zeit ist schon belegt: '+e.data.conflicts.map(c=>`${fmtT(c.start)}–${fmtT(c.end)} ${esc(c.name)}`).join(', ')+'. Tippe auf „Trotzdem sichern“, wenn das so passt.';
      }else err.textContent={name:'Bitte den Namen eintragen.',email:'Die E-Mail-Adresse stimmt nicht.',services:'Bitte eine Leistung wählen.',gone:'Dieser Termin existiert nicht mehr. Bitte neu laden.',time:'Bitte Beginn und Ende prüfen.'}[e.message]||'Speichern hat nicht geklappt. Bitte erneut versuchen.';
    }
  }
  draw();
}

/* ---------- Eingang ---------- */
async function loadInbox(){
  const box=$('#inbox');
  if(!S.inbox)box.innerHTML='<div class="skel" style="margin:0"></div>';
  try{const j=await api('inbox');S.inbox=j;setBadge(j.inbox);drawInbox()}
  catch(e){if(e.message!=='key')box.innerHTML=`<div class="note warn">${ICON.warn}<div>Der Eingang konnte nicht geladen werden. Bitte erneut versuchen.</div></div>`}
}
function drawInbox(){
  const {messages,bookings}=S.inbox,box=$('#inbox');
  const items=[...bookings.map(b=>({t:'b',at:b.status==='storniert'?b.cancelled:b.created,b,neu:b.seen===0})),...messages.map(m=>({t:'m',at:m.created_at,m,neu:m.status==='neu'}))].sort((a,b)=>a.at<b.at?1:-1);
  const neu=items.filter(i=>i.neu),open=items.filter(i=>!i.neu&&i.t==='m'&&i.m.status==='gelesen'),recent=items.filter(i=>!i.neu&&i.t==='b'),done=items.filter(i=>!i.neu&&i.t==='m'&&i.m.status==='erledigt').slice(0,30);
  $('#allseen').hidden=!neu.some(i=>i.t==='b');
  const row=i=>{
    if(i.t==='b'){const b=i.b,cx=b.status==='storniert';
      return `<button type="button" class="row ind" data-b="${b.id}"><span class="lead ${cx?'cxl':'online'}">${cx?ICON.x:ICON.cal}</span><span class="main-t"><b>${cx?'Abgesagt: ':''}${esc(b.name)}</b><span>${esc(fShort.format(day(b.date)))} · ${fmtT(b.start)} Uhr · ${esc(b.services.join(', '))}</span></span><span class="trail">${esc(rel(i.at))}</span>${i.neu?'<span class="unread" aria-label="neu"></span>':ICON.chev}</button>`}
    const m=i.m;
    return `<button type="button" class="row ind${m.status==='erledigt'?' done':''}" data-m="${m.id}"><span class="lead msg">${ICON.msg}</span><span class="main-t"><b>${esc(m.name)}${m.topic?' · '+esc(m.topic):''}</b><span class="wrap">${esc(m.message)}</span></span><span class="trail">${esc(rel(i.at))}</span>${i.neu?'<span class="unread" aria-label="neu"></span>':ICON.chev}</button>`;
  };
  const grp=(t,l,foot)=>l.length?`<div class="ghd">${t}</div><div class="group">${l.map(row).join('')}</div>${foot?`<p class="gft">${foot}</p>`:''}`:'';
  let h=neu.length?grp('Neu',neu):`<div class="group"><div class="empty"><b>Alles gesehen</b>Neue Online-Buchungen, Absagen und Nachrichten erscheinen hier.</div></div>`;
  h+=grp('Offene Nachrichten',open,'Markiere Nachrichten als erledigt, sobald du geantwortet hast.')+grp('Online-Buchungen der letzten 14 Tage',recent)+grp('Erledigt',done,'Nachrichten werden nach 90 Tagen automatisch gelöscht.');
  box.innerHTML=h;
}
$('#inbox').addEventListener('click',e=>{
  const b=e.target.closest('[data-b]'),m=e.target.closest('[data-m]');
  if(b)openEvent(S.inbox.bookings.find(x=>x.id===b.dataset.b));
  if(m)openMsg(S.inbox.messages.find(x=>x.id===m.dataset.m));
});
$('#allseen').onclick=async()=>{try{const j=await api('seen',{},{all:true});setBadge(j.inbox);S.inbox.bookings.forEach(b=>b.seen=1);drawInbox()}catch(e){toast('Das hat nicht geklappt.')}};
function openMsg(m){
  if(m.status==='neu'){m.status='gelesen';api('msg',{},{id:m.id,status:'gelesen'}).then(j=>setBadge(j.inbox)).catch(()=>{});drawInbox()}
  const tel=String(m.phone||'').replace(/[^\d+]/g,'');
  const subj=encodeURIComponent('Deine Anfrage beim Wohlfühlraum Sunny'+(m.topic?': '+m.topic:''));
  sheet('Nachricht','<button type="button" data-close>Schließen</button>','',
    `<div class="hero-ev"><div class="k" style="color:#6E8EA8">${esc(m.topic||'Nachricht')}</div><h3>${esc(m.name)}</h3><p>${esc(rel(m.created_at))}</p></div>
    <div class="acts">${tel?`<a class="act" href="tel:${esc(tel)}">${ICON.phone}Anrufen</a><a class="act" href="https://wa.me/${esc(waNum(m.phone))}" target="_blank" rel="noopener">${ICON.wa}WhatsApp</a>`:''}${m.email?`<a class="act" href="mailto:${esc(m.email)}?subject=${subj}">${ICON.mail}Antworten</a>`:''}</div>
    <div class="group"><div class="kv"><dd style="white-space:pre-wrap">${esc(m.message)}</dd></div></div>
    <dl class="group">${kv('Telefon',tel?`<a href="tel:${esc(tel)}">${esc(m.phone)}</a>`:'')}${kv('E-Mail',m.email?`<a href="mailto:${esc(m.email)}?subject=${subj}">${esc(m.email)}</a>`:'')}${kv('Eingegangen',esc(new Date(m.created_at).toLocaleString('de-DE',{dateStyle:'medium',timeStyle:'short'})))}</dl>
    <button type="button" class="btn-row" id="m-book">Als Termin eintragen</button>
    <button type="button" class="btn-row" id="m-done">${m.status==='erledigt'?'Wieder als offen markieren':'Als erledigt markieren'}</button>
    <button type="button" class="btn-row danger" id="m-del">Nachricht löschen</button>`);
  sin.querySelector('[data-close]').onclick=closeSheet;
  $('#m-book').onclick=()=>{go('cal');openEditor({date:iso(today()),name:m.name,phone:m.phone,email:m.email,note:m.topic&&m.topic!=='Sonstiges'?m.topic:''})};
  $('#m-done').onclick=async()=>{const st=m.status==='erledigt'?'gelesen':'erledigt';try{const j=await api('msg',{},{id:m.id,status:st});m.status=st;setBadge(j.inbox);closeSheet();drawInbox();toast(st==='erledigt'?'Als erledigt markiert':'Wieder offen')}catch(e){toast('Das hat nicht geklappt.')}};
  $('#m-del').onclick=async()=>{const b=$('#m-del');if(b.dataset.sure!=='1'){b.dataset.sure='1';b.textContent='Wirklich löschen?';return}
    try{const j=await api('msg',{},{id:m.id,status:'delete'});S.inbox.messages=S.inbox.messages.filter(x=>x!==m);setBadge(j.inbox);closeSheet();drawInbox();toast('Nachricht gelöscht')}catch(e){toast('Das hat nicht geklappt.')}};
}

/* ---------- Sperrzeiten ---------- */
async function loadClosures(){
  const box=$('#closures');
  if(!S.clos)box.innerHTML='<div class="skel" style="margin:0"></div>';
  try{
    const [c]=await Promise.all([api('closures'),S.meta?null:load()]);S.clos=c;drawClosures();
  }catch(e){if(e.message!=='key')box.innerHTML=`<div class="note warn">${ICON.warn}<div>Die Sperrzeiten konnten nicht geladen werden. Bitte erneut versuchen.</div></div>`}
}
function span(c){
  const a=day(c.from),b=day(c.to),n=Math.round((b-a)/864e5)+1;
  return (n===1?fLongY.format(a):fShort.format(a)+' – '+fShort.format(b)+(a.getFullYear()!==b.getFullYear()||b.getFullYear()!==new Date().getFullYear()?' '+b.getFullYear():''))+(n>1?' · '+n+' Tage':'');
}
function drawClosures(){
  const {closures,holidays}=S.clos,box=$('#closures'),hours=S.meta?.hours||{};
  let h='<div class="ghd">Geplante Sperrzeiten</div>';
  h+=closures.length?'<div class="group">'+closures.map(c=>`<button type="button" class="row ind" data-c="${c.id}"><span class="lead ${c.kind==='urlaub'?'vac':'closed'}">${c.kind==='urlaub'?ICON.sun:ICON.ban}</span><span class="main-t"><b>${esc(c.label||KIND[c.kind])}</b><span>${esc(KIND[c.kind])} · ${esc(span(c))}</span></span>${ICON.chev}</button>`).join('')+'</div><p class="gft">An diesen Tagen ist online keine Buchung möglich. Kundinnen sehen im Buchungskalender „Urlaub“ oder „Geschlossen“.</p>'
    :`<div class="group"><div class="empty"><b>Keine Sperrzeiten geplant</b>Trag Urlaub, Brückentage oder Schließtage ein. Diese Tage sind dann online nicht buchbar.</div><button type="button" class="row" id="c-first" style="justify-content:center;color:var(--tint);font-weight:500">Sperrzeit hinzufügen</button></div>`;
  h+='<div class="ghd">Feiertage in Baden-Württemberg</div><div class="group">'+holidays.map(x=>`<label class="row"><span class="main-t"><b>${esc(x.name)}</b><span>${esc(fLongY.format(day(x.date)))}</span></span><span class="trail" style="margin-right:4px">${x.open?'geöffnet':'geschlossen'}</span><input type="checkbox" class="sw" data-hol="${x.date}" ${x.open?'checked':''} aria-label="${esc(x.name)} geöffnet"></label>`).join('')+'</div><p class="gft">Feiertage sind automatisch gesperrt. Schalte einen Feiertag ein, wenn du an dem Tag trotzdem arbeiten möchtest.</p>';
  h+='<div class="ghd">Feste Öffnungszeiten</div><div class="group">'+[1,2,3,4,5,6,0].map(d=>`<div class="row"><span class="main-t"><b>${WDL[d]}</b></span><span class="trail">${hours[d]?hours[d].map(r=>r[0]+':00 – '+r[1]+':00').join('<br>'):'online nicht buchbar'}</span></div>`).join('')+'</div><p class="gft">Online buchbar sind nur diese Zeiten. Samstags-Termine nach Vereinbarung trägst du selbst ein. Für dauerhafte Änderungen melde dich bei deiner Webbetreuung.</p>';
  box.innerHTML=h;
  $('#c-first')&&($('#c-first').onclick=()=>openClosure());
}
$('#closures').addEventListener('click',e=>{const r=e.target.closest('[data-c]');if(r)openClosure(S.clos.closures.find(c=>c.id===r.dataset.c))});
$('#closures').addEventListener('change',async e=>{
  const sw=e.target.closest('[data-hol]');if(!sw)return;
  const open=sw.checked,lbl=sw.parentElement.querySelector('.trail');lbl.textContent=open?'geöffnet':'geschlossen';
  try{await api('holiday',{},{date:sw.dataset.hol,open});const x=S.clos.holidays.find(h=>h.date===sw.dataset.hol);x.open=open;toast(open?'Feiertag geöffnet: online buchbar':'Feiertag wieder geschlossen')}
  catch(err){sw.checked=!open;lbl.textContent=!open?'geöffnet':'geschlossen';toast('Das hat nicht geklappt.')}
});
$('#addclose').onclick=()=>openClosure();
function openClosure(c,date){
  const C=c?{...c}:{kind:'urlaub',from:date||iso(today()),to:date||iso(today()),label:''};
  const foot={urlaub:'Kundinnen sehen „Urlaub“. Die Bezeichnung siehst nur du.',geschlossen:'Kundinnen sehen „Geschlossen“. Die Bezeichnung siehst nur du.',feiertag:'Kundinnen sehen die Bezeichnung, z. B. „Heiligabend“.',sonstiges:'Kundinnen sehen „Geschlossen“. Die Bezeichnung siehst nur du.'};
  const ph={urlaub:'z. B. Sommerurlaub',geschlossen:'z. B. Fortbildung',feiertag:'z. B. Heiligabend',sonstiges:'z. B. Renovierung'};
  function draw(){
    sheet(c?'Sperrzeit bearbeiten':'Neue Sperrzeit','<button type="button" data-close>Abbrechen</button>',`<button type="button" id="c-save">${c?'Sichern':'Hinzufügen'}</button>`,
      `<div class="seg full" role="tablist" style="grid-template-columns:repeat(4,1fr)">${Object.keys(KIND).map(k=>`<button type="button" role="tab" data-k="${k}" aria-selected="${C.kind===k}">${KIND[k]}</button>`).join('')}</div>
      <div class="group">
        <label class="fld"><span>Von</span><input type="date" id="c-from" value="${C.from}"></label>
        <label class="fld"><span>Bis</span><input type="date" id="c-to" value="${C.to}"></label>
        <label class="fld"><span>Bezeichnung</span><input id="c-label" value="${esc(C.label)}" placeholder="${ph[C.kind]}" maxlength="60"></label>
      </div><p class="gft">${foot[C.kind]} Die Tage sind ganztägig gesperrt.</p>
      <div id="c-warn"></div><p class="err" id="c-err" role="alert"></p>
      ${c?'<button type="button" class="btn-row danger" id="c-del">Sperrzeit löschen</button>':''}`);
    sin.querySelector('[data-close]').onclick=closeSheet;
    $$('[data-k]',sin).forEach(b=>b.onclick=()=>{read();C.kind=b.dataset.k;draw()});
    $('#c-from').onchange=()=>{read();if(C.to<C.from){C.to=C.from;$('#c-to').value=C.to}warn()};
    $('#c-to').onchange=()=>{read();warn()};
    $('#c-save').onclick=save;
    $('#c-del')&&($('#c-del').onclick=async()=>{const b=$('#c-del');if(b.dataset.sure!=='1'){b.dataset.sure='1';b.textContent='Wirklich löschen? Die Tage werden wieder buchbar.';return}
      try{await api('closure_del',{},{id:c.id});closeSheet();toast('Sperrzeit gelöscht');afterClosure()}catch(e){toast('Das hat nicht geklappt.')}});
    warn();
  }
  function read(){C.from=$('#c-from').value;C.to=$('#c-to').value;C.label=$('#c-label').value.trim()}
  let wk=0;
  async function warn(){
    const my=++wk,w=$('#c-warn');if(!C.from||!C.to||C.to<C.from){w.innerHTML='';return}
    const n=Math.round((day(C.to)-day(C.from))/864e5);if(n>120){w.innerHTML='';return}
    try{const j=await api('data',{from:C.from,to:C.to});if(my!==wk)return;
      const t=j.events.filter(e=>e.kind==='termin');
      w.innerHTML=t.length?`<div class="note warn">${ICON.warn}<div>In diesem Zeitraum ${t.length===1?'liegt schon ein Termin':'liegen schon '+t.length+' Termine'}. Sie bleiben bestehen, bitte prüfen:<ul>${t.slice(0,6).map(e=>`<li>${esc(fShort.format(day(e.date)))}, ${fmtT(e.start)} ${esc(e.name)}</li>`).join('')}${t.length>6?'<li>…</li>':''}</ul></div></div>`:'';
    }catch(e){}
  }
  async function save(){
    read();const err=$('#c-err');err.textContent='';
    if(!C.from||!C.to){err.textContent='Bitte Von und Bis wählen.';return}
    if(C.to<C.from){err.textContent='„Bis“ muss nach „Von“ liegen.';return}
    $('#c-save').disabled=true;
    try{const j=await api('closure',{},{id:c?.id||'',kind:C.kind,from:C.from,to:C.to,label:C.label});closeSheet();
      toast(j.affected?`Gesichert · ${j.affected} ${j.affected===1?'Termin liegt':'Termine liegen'} in diesem Zeitraum`:'Sperrzeit gesichert, online nicht mehr buchbar',j.affected?5000:2600);afterClosure()}
    catch(e){$('#c-save').disabled=false;err.textContent=e.message==='long'?'Eine Sperrzeit darf höchstens ein Jahr lang sein.':'Speichern hat nicht geklappt. Bitte erneut versuchen.'}
  }
  draw();
}
function afterClosure(){dayCache={};if(S.sec==='closures')loadClosures();else refresh()}

/* ---------- Aktualisieren ---------- */
function refresh(){if(S.sec==='cal')load();else if(S.sec==='inbox')loadInbox();else loadClosures()}
async function poll(){
  if(document.hidden||dlg.open)return;
  try{
    if(S.sec==='cal'){const prev=S.data?.inbox;await load();const now=S.data?.inbox;if(prev!=null&&now>prev)toast(now-prev===1?'Neu im Eingang':(now-prev)+' neue Einträge im Eingang')}
    else{const j=await api('data',{from:iso(today()),to:iso(today())});const prev=S.badge;setBadge(j.inbox);if(S.sec==='inbox'&&prev!=null&&j.inbox!==prev)loadInbox()}
  }catch(e){}
}
setInterval(poll,60000);
document.addEventListener('visibilitychange',()=>{if(!document.hidden)poll()});

/* Start */
load();
if(flashMsg)toast(flashMsg);
})();
