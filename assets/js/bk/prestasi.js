(() => {
  'use strict';

  const app = document.getElementById('prestasiApp');
  if (!app) return;
  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const body = document.getElementById('prestasiBody');
  const mobileList = document.getElementById('prestasiMobileList');
  const form = document.getElementById('formPrestasi');
  const alertBox = document.getElementById('prestasiAlert');
  const modalEl = document.getElementById('modalPrestasi');
  const tahun = document.getElementById('prestasiTahun');
  if (!body || !mobileList) return;

  const state = { limit:25, offset:0, total:0 };
  const pager = window.SisfourPagination?.mount(body,{id:'bkPrestasiPager',label:'prestasi',onChange:(next)=>{state.limit=next.limit;state.offset=next.offset;load();}});
  const esc=(value)=>{const div=document.createElement('div');div.textContent=value??'';return div.innerHTML;};
  const today=()=>{const d=new Date();return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;};

  const params=(withPaging=true)=>{
    const p=new URLSearchParams({format:'json'});
    if(withPaging){p.set('limit',String(state.limit));p.set('offset',String(state.offset));}
    [['id_tahun','prestasiTahun'],['search','prestasiSearch'],['tingkat','prestasiTingkat'],['tanggal_mulai','prestasiMulai'],['tanggal_selesai','prestasiSelesai']].forEach(([key,id])=>{const value=document.getElementById(id)?.value;if(value)p.set(key,value);});
    return p;
  };

  const syncUrl=()=>{const p=params(true);p.delete('format');const q=p.toString();history.replaceState(null,'',`${location.pathname}${q?`?${q}`:''}`);};
  const restoreState=()=>{
    const p=new URLSearchParams(location.search);const limit=Number(p.get('limit')||25),offset=Number(p.get('offset')||0);state.limit=[25,50,100].includes(limit)?limit:25;state.offset=Number.isFinite(offset)&&offset>=0?offset:0;
    [['id_tahun','prestasiTahun'],['search','prestasiSearch'],['tingkat','prestasiTingkat'],['tanggal_mulai','prestasiMulai'],['tanggal_selesai','prestasiSelesai']].forEach(([key,id])=>{const value=p.get(key),el=document.getElementById(id);if(value!==null&&el)el.value=value;});
    if(tahun && !tahun.value) window.SisfourActiveYearDefault?.applyDefault(tahun);
  };

  function show(message,type='danger'){if(!alertBox)return;alertBox.className=`alert alert-${type}`;alertBox.textContent=message;}
  function setButtonBusy(button,busy,label='Memproses...'){if(!button)return;if(busy){if(button.dataset.busy==='1')return;button.dataset.busy='1';button.dataset.busyHtml=button.innerHTML;button.disabled=true;button.innerHTML=`<span class="spinner-border spinner-border-sm me-2"></span>${label}`;return;}button.disabled=false;if(button.dataset.busyHtml!==undefined)button.innerHTML=button.dataset.busyHtml;delete button.dataset.busy;delete button.dataset.busyHtml;}
  const formSubmitButton=(target)=>target?.querySelector('button[type="submit"],input[type="submit"]')||null;

  async function requestJson(url,options={}){const response=await fetch(url,{...options,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest',...(options.headers||{})},credentials:'same-origin'});let payload;try{payload=await response.json();}catch{throw new Error('Response server tidak valid.');}if(!response.ok||payload.status!=='success')throw new Error(payload.message||'Proses gagal.');return payload;}

  function setStudent(row=null){if(!form)return;const select=form.elements.id_siswa;window.SisfourSearchableSelect?.setValue(select,row?.id_siswa||'',row?`${row.nama_siswa} — ${row.nisn}`:'');}
  function openForm(row=null){if(!form||!modalEl)return;form.reset();form.dataset.busy='0';setButtonBusy(formSubmitButton(form),false);form.elements.id.value=row?.id||'';setStudent(row);form.elements.nama_prestasi.value=row?.nama_prestasi||'';form.elements.tingkat.value=row?.tingkat||'Madrasah';form.elements.tanggal.value=row?.tanggal||today();form.elements.penyelenggara.value=row?.penyelenggara||'';form.elements.keterangan.value=row?.keterangan||'';document.getElementById('judulModalPrestasi').textContent=row?'Edit Prestasi Siswa':'Tambah Prestasi Siswa';bootstrap.Modal.getOrCreateInstance(modalEl).show();}

  function render(rows,manage){
    body.innerHTML=rows.map(row=>`<tr data-json="${encodeURIComponent(JSON.stringify(row))}"><td>${esc(row.tanggal)}</td><td><strong>${esc(row.nama_siswa)}</strong><div class="small text-muted">${esc(row.nisn)}</div></td><td>${esc(row.nama_prestasi)}</td><td>${esc(row.tingkat)}</td><td>${esc(row.penyelenggara||'-')}</td><td>${esc(row.keterangan||'-')}</td>${manage?'<td class="text-nowrap"><button type="button" class="btn btn-sm btn-outline-primary btn-edit-prestasi">Edit</button> <button type="button" class="btn btn-sm btn-outline-danger btn-delete-prestasi">Hapus</button></td>':''}</tr>`).join('')||`<tr><td colspan="${manage?7:6}" class="text-center text-muted py-4">Tidak ada data.</td></tr>`;
    mobileList.innerHTML=rows.map(row=>`<div class="list-group-item py-3" data-json="${encodeURIComponent(JSON.stringify(row))}"><div class="d-flex justify-content-between align-items-start gap-2"><div class="sisfour-cell-primary"><span class="sisfour-cell-title">${esc(row.nama_siswa)}</span><span class="sisfour-cell-meta">${esc(row.tanggal)} · ${esc(row.nama_tahun||'')} ${esc(row.semester||'')}</span></div><span class="badge bg-label-primary">${esc(row.tingkat)}</span></div><div class="fw-semibold mt-2">${esc(row.nama_prestasi)}</div><div class="small text-muted mt-1">${esc(row.penyelenggara||'-')}</div>${row.keterangan?`<div class="small mt-2">${esc(row.keterangan)}</div>`:''}${manage?'<div class="sisfour-mobile-actions mt-3"><button type="button" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-edit-prestasi">Edit</button><button type="button" class="btn btn-sm btn-outline-danger sisfour-touch-target--compact btn-delete-prestasi">Hapus</button></div>':''}</div>`).join('')||'<div class="list-group-item text-center text-muted py-4">Tidak ada data.</div>';
    bindRows();
  }

  function rowData(button){const holder=button.closest('[data-json]');return holder?JSON.parse(decodeURIComponent(holder.dataset.json)):null;}
  async function load(){pager?.setDisabled(true);try{const payload=await requestJson(`${base}/bk/prestasi?${params(true)}`);const data=payload.data||{},rows=Array.isArray(data.rows)?data.rows:[];state.total=Number(data.total||0);state.limit=Number(data.limit||state.limit);state.offset=Number(data.offset??state.offset);if(!rows.length&&state.total>0&&state.offset>=state.total){state.offset=Math.floor((state.total-1)/state.limit)*state.limit;await load();return;}render(rows,Boolean(data.can_manage));pager?.render(state);syncUrl();}catch(error){show(error.message||'Gagal memuat data.');}finally{pager?.setDisabled(false);}}

  function bindRows(){
    document.querySelectorAll('.btn-edit-prestasi').forEach(button=>button.addEventListener('click',()=>{const row=rowData(button);if(row)openForm(row);}));
    document.querySelectorAll('.btn-delete-prestasi').forEach(button=>button.addEventListener('click',async()=>{if(button.dataset.busy==='1')return;const row=rowData(button);if(!row)return;if(!window.Swal?.fire){show('Dialog konfirmasi tidak tersedia. Muat ulang halaman lalu coba lagi.');return;}const confirmation=await Swal.fire({icon:'warning',title:'Hapus prestasi?',html:`<strong>${esc(row.nama_siswa||'-')}</strong><br>${esc(row.nama_prestasi||'-')}`,showCancelButton:true,confirmButtonText:'Ya, hapus',cancelButtonText:'Batal',reverseButtons:true});if(!confirmation.isConfirmed)return;setButtonBusy(button,true,'Menghapus...');try{const payload=await requestJson(`${base}/bk/prestasi/delete/${row.id}`,{method:'DELETE'});show(payload.message||'Prestasi berhasil dihapus.','success');await load();}catch(error){show(error.message||'Gagal menghapus.');}finally{setButtonBusy(button,false);}}));
  }

  document.getElementById('btnPrestasiBaru')?.addEventListener('click',()=>openForm());
  document.getElementById('btnPrestasiCari')?.addEventListener('click',()=>{state.offset=0;load();});
  document.getElementById('btnPrestasiReset')?.addEventListener('click',()=>{if(tahun){tahun.value='';window.SisfourActiveYearDefault?.applyDefault(tahun);}['prestasiSearch','prestasiTingkat','prestasiMulai','prestasiSelesai'].forEach(id=>{const el=document.getElementById(id);if(el)el.value='';});state.offset=0;load();});
  document.getElementById('prestasiSearch')?.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();state.offset=0;load();}});
  document.getElementById('btnPrestasiExport')?.addEventListener('click',event=>{event.preventDefault();const p=params(false);p.delete('format');location.href=`${base}/bk/prestasi/export?${p}`;});

  form?.addEventListener('submit',async event=>{event.preventDefault();if(form.dataset.busy==='1')return;form.dataset.busy='1';const submitButton=formSubmitButton(form);setButtonBusy(submitButton,true,'Menyimpan...');const fd=new FormData(form),id=String(fd.get('id')||'');fd.delete('id');let url=`${base}/bk/prestasi/create`,options={method:'POST',body:fd};if(id){url=`${base}/bk/prestasi/update/${id}`;options={method:'PUT',body:new URLSearchParams(fd),headers:{'Content-Type':'application/x-www-form-urlencoded'}};}try{const payload=await requestJson(url,options);bootstrap.Modal.getInstance(modalEl)?.hide();show(payload.message||'Prestasi berhasil disimpan.','success');await load();}catch(error){show(error.message||'Gagal menyimpan.');}finally{form.dataset.busy='0';setButtonBusy(submitButton,false);}});

  restoreState();
  load();
})();
