(() => {
'use strict';
const app=document.getElementById('bkKasusApp'); if(!app)return;
const base=String(app.dataset.baseUrl||'').replace(/\/+$/,'');
const body=document.getElementById('kasusBody'), info=document.getElementById('kasusInfo'), alertBox=document.getElementById('kasusAlert');
const search=document.getElementById('kasusSearch'), kategori=document.getElementById('kasusKategori'), mulai=document.getElementById('kasusMulai'), selesai=document.getElementById('kasusSelesai');
let offset=0,total=0; const limit=50;
const esc=v=>{const d=document.createElement('div');d.textContent=v??'';return d.innerHTML};
const params=()=>{const p=new URLSearchParams({format:'json',limit,offset});if(search?.value)p.set('search',search.value);if(kategori?.value)p.set('kategori',kategori.value);if(mulai?.value)p.set('tanggal_mulai',mulai.value);if(selesai?.value)p.set('tanggal_selesai',selesai.value);return p};
function show(m,t='danger'){alertBox.className=`alert alert-${t}`;alertBox.textContent=m}
function hide(){alertBox.classList.add('d-none')}
async function load(){try{const r=await fetch(`${base}/bk/kasus?${params()}`,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});const j=await r.json();if(!r.ok||j.status!=='success'){show(j.message||'Gagal memuat data');return}hide();const d=j.data||{};total=Number(d.total||0);body.innerHTML=(d.rows||[]).map(x=>`<tr><td>${esc(x.tanggal)}</td><td>${esc(x.nisn)}<br><strong>${esc(x.nama_siswa)}</strong></td><td>${esc(x.nama_pelanggaran)}</td><td>${esc(x.kategori)}</td><td>${Number(x.poin||0)}</td><td>${esc(x.keterangan||'-')}</td></tr>`).join('')||'<tr><td colspan="6" class="text-center text-muted">Tidak ada data.</td></tr>';info.textContent=`${total?offset+1:0}-${Math.min(offset+limit,total)} dari ${total}`;document.getElementById('kasusPrev').disabled=offset<=0;document.getElementById('kasusNext').disabled=offset+limit>=total}catch(e){show('Terjadi kesalahan jaringan.')}}
document.getElementById('btnKasusCari')?.addEventListener('click',()=>{offset=0;load()});
document.getElementById('kasusPrev')?.addEventListener('click',()=>{offset=Math.max(0,offset-limit);load()});
document.getElementById('kasusNext')?.addEventListener('click',()=>{if(offset+limit<total){offset+=limit;load()}});
document.getElementById('btnKasusExport')?.addEventListener('click',e=>{e.preventDefault();const p=params();p.delete('format');p.delete('limit');p.delete('offset');location.href=`${base}/bk/kasus/export?${p}`});
document.getElementById('formKasus')?.addEventListener('submit',async e=>{e.preventDefault();const r=await fetch(`${base}/bk/kasus/create`,{method:'POST',body:new FormData(e.target),headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});const j=await r.json();if(!r.ok||j.status!=='success'){show(j.message||'Gagal menyimpan');return}location.reload()});
load();
})();