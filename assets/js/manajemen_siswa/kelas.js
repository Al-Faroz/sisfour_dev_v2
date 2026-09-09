(() => {
    'use strict';
    const app=document.getElementById('manajemenKelasSiswaApp');if(!app)return;
    const baseUrl=app.dataset.baseUrl.replace(/\/+$/,'');
    const tbody=document.querySelector('#tableKelasSiswa tbody'),filter=document.getElementById('formFilterKelasSiswa'),form=document.getElementById('formAturKelas');
    const modal=new bootstrap.Modal(document.getElementById('modalAturKelas'));let rows=[];
    const endpoint=p=>`${baseUrl}/${p.replace(/^\/+/,'')}`;
    const esc=v=>{const d=document.createElement('div');d.textContent=v??'';return d.innerHTML;};
    const parse=async r=>{const d=await r.json().catch(()=>({}));if(!r.ok||d.status==='error')throw new Error(d.message||'Permintaan gagal.');return d;};
    const err=m=>Swal.fire({icon:'error',title:'Gagal',text:m});
    const ok=m=>Swal.fire({icon:'success',title:'Berhasil',text:m,timer:1500,showConfirmButton:false});
    const render=()=>{tbody.innerHTML=rows.map((r,i)=>{const has=Number(r.id_kelas||0)>0;return `<tr><td>${i+1}</td><td class="fw-semibold">${esc(r.nama)}</td><td class="font-monospace">${esc(r.nisn)}</td><td class="font-monospace">${esc(r.nik)}</td><td>${r.jenis_kelamin==='L'?'L':'P'}</td><td>${has?`<span class="badge bg-label-primary">${esc(r.nama_kelas)}</span>`:'<span class="badge bg-label-warning">Belum Ada Kelas</span>'}</td><td><button type="button" class="btn btn-sm btn-primary btn-atur" data-id="${r.id}">${has?'Pindah Kelas':'Tempatkan'}</button></td></tr>`;}).join('');};
    const load=async()=>{try{const q=new URLSearchParams(new FormData(filter));[...q.entries()].forEach(([k,v])=>{if(!String(v).trim())q.delete(k);});const r=await fetch(endpoint(`manajemen-siswa/kelas/json?${q}`),{headers:{'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});const d=await parse(r);rows=Array.isArray(d.data)?d.data:[];render();}catch(e){err(e.message||'Data gagal dimuat.');}};
    tbody.addEventListener('click',e=>{const b=e.target.closest('.btn-atur');if(!b)return;const id=Number(b.dataset.id),r=rows.find(x=>Number(x.id)===id);if(!r)return;document.getElementById('idSiswaKelas').value=id;document.getElementById('namaSiswaKelas').value=r.nama??'';document.getElementById('kelasSaatIni').value=r.nama_kelas||'Belum Ada Kelas';document.getElementById('idKelasTujuan').value='';modal.show();});
    form.addEventListener('submit',async e=>{e.preventDefault();const id=Number(document.getElementById('idSiswaKelas').value);try{const r=await fetch(endpoint(`manajemen-siswa/kelas/set/${id}`),{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});const d=await parse(r);modal.hide();await ok(d.message);await load();}catch(x){err(x.message||'Kelas gagal diperbarui.');}});
    filter.addEventListener('submit',e=>{e.preventDefault();load();});document.getElementById('btnResetFilter').addEventListener('click',()=>{filter.reset();load();});load();
})();