(() => {
  'use strict';

  const app = document.getElementById('bkKasusApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const pageCanManage = app.dataset.canManage === '1';
  const body = document.getElementById('kasusBody');
  const mobileList = document.getElementById('kasusMobileList');
  const alertBox = document.getElementById('kasusAlert');
  const tahun = document.getElementById('kasusTahun');
  const search = document.getElementById('kasusSearch');
  const kategori = document.getElementById('kasusKategori');
  const mulai = document.getElementById('kasusMulai');
  const selesai = document.getElementById('kasusSelesai');
  const form = document.getElementById('formKasus');
  const modalEl = document.getElementById('modalKasus');
  const detailModalEl = document.getElementById('modalDetailKasus');
  const detailLoading = document.getElementById('detailKasusLoading');
  const detailContent = document.getElementById('detailKasusContent');
  const formTindak = document.getElementById('formTindakLanjut');
  const timeline = document.getElementById('timelineTindakLanjut');
  if (!body || !mobileList) return;

  const state = { limit: 25, offset: 0, total: 0 };
  const pager = window.SisfourPagination?.mount(body, {
    id: 'bkKasusPager', label: 'catatan pelanggaran',
    onChange: (next) => { state.limit = next.limit; state.offset = next.offset; load(); },
  });

  const esc = (value) => { const div = document.createElement('div'); div.textContent = value ?? ''; return div.innerHTML; };
  const today = () => { const d = new Date(); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; };

  function show(message, type = 'danger') { if (!alertBox) return; alertBox.className = `alert alert-${type}`; alertBox.textContent = message; }
  function hideAlert() { alertBox?.classList.add('d-none'); }
  function setButtonBusy(button, busy, label = 'Memproses...') {
    if (!button) return;
    if (busy) { if (button.dataset.busy === '1') return; button.dataset.busy='1'; button.dataset.busyHtml=button.innerHTML; button.disabled=true; button.innerHTML=`<span class="spinner-border spinner-border-sm me-2"></span>${label}`; return; }
    button.disabled=false; if (button.dataset.busyHtml !== undefined) button.innerHTML=button.dataset.busyHtml; delete button.dataset.busy; delete button.dataset.busyHtml;
  }
  const formSubmitButton = (target) => target?.querySelector('button[type="submit"],input[type="submit"]') || null;

  function setRemoteSelectValue(select, value, text) {
    if (!select) return;
    window.SisfourSearchableSelect?.setValue(select, value, text);
    if (!window.SisfourSearchableSelect) {
      const normalized=String(value??''); let option=[...select.options].find(x=>x.value===normalized);
      if (!option && normalized) { option=new Option(text||normalized, normalized, true, true); select.add(option); }
      select.value=normalized;
    }
  }

  async function requestJson(url, options = {}) {
    const response = await fetch(url, { ...options, headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest',...(options.headers||{})}, credentials:'same-origin' });
    let payload; try { payload=await response.json(); } catch { throw new Error('Response server tidak valid.'); }
    if (!response.ok || payload.status !== 'success') throw new Error(payload.message || 'Proses gagal.');
    return payload;
  }

  function params(withPaging = true) {
    const p = new URLSearchParams({ format:'json' });
    if (withPaging) { p.set('limit', String(state.limit)); p.set('offset', String(state.offset)); }
    if (tahun?.value) p.set('id_tahun', tahun.value);
    if (search?.value) p.set('search', search.value);
    if (kategori?.value) p.set('kategori', kategori.value);
    if (mulai?.value) p.set('tanggal_mulai', mulai.value);
    if (selesai?.value) p.set('tanggal_selesai', selesai.value);
    return p;
  }

  const categoryClass = (value) => value === 'Berat' ? 'danger' : (value === 'Sedang' ? 'warning' : 'secondary');

  function itemData(element) {
    const holder=element.closest('[data-json]');
    if (!holder?.dataset.json) return null;
    try { return JSON.parse(decodeURIComponent(holder.dataset.json)); } catch { return null; }
  }

  function renderRows(rows, canManage) {
    body.innerHTML = rows.map(row => `
      <tr data-json="${encodeURIComponent(JSON.stringify(row))}">
        <td class="text-nowrap">${esc(row.tanggal)}</td>
        <td><strong>${esc(row.nama_siswa)}</strong><div class="small text-muted">NISN ${esc(row.nisn||'-')}</div></td>
        <td>${esc(row.nama_pelanggaran)}</td>
        <td><span class="badge bg-label-${categoryClass(row.kategori)}">${esc(row.kategori)}</span></td>
        <td>${esc(row.keterangan||'-')}</td>
        <td class="text-end"><div class="sisfour-row-actions justify-content-end"><button type="button" class="btn btn-sm btn-outline-secondary btn-detail-kasus">Tindak Lanjut</button>${canManage?'<button type="button" class="btn btn-sm btn-outline-primary btn-edit-kasus">Edit</button><button type="button" class="btn btn-sm btn-outline-danger btn-delete-kasus">Hapus</button>':''}</div></td>
      </tr>`).join('') || '<tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data.</td></tr>';

    mobileList.innerHTML = rows.map(row => `
      <div class="list-group-item py-3" data-json="${encodeURIComponent(JSON.stringify(row))}">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2"><div class="sisfour-cell-primary"><span class="sisfour-cell-title">${esc(row.nama_siswa)}</span><span class="sisfour-cell-meta">${esc(row.tanggal)} · ${esc(row.nama_tahun||'')} ${esc(row.semester||'')}</span></div><span class="badge bg-label-${categoryClass(row.kategori)}">${esc(row.kategori)}</span></div>
        <div class="fw-semibold mb-1">${esc(row.nama_pelanggaran)}</div><div class="small text-muted mb-3">${esc(row.keterangan||'Tanpa keterangan tambahan.')}</div>
        <div class="sisfour-mobile-actions"><button type="button" class="btn btn-sm btn-outline-secondary sisfour-touch-target--compact btn-detail-kasus">Tindak Lanjut</button>${canManage?'<button type="button" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-edit-kasus">Edit</button><button type="button" class="btn btn-sm btn-outline-danger sisfour-touch-target--compact btn-delete-kasus">Hapus</button>':''}</div>
      </div>`).join('') || '<div class="list-group-item text-center text-muted py-4">Tidak ada data.</div>';
    bindRows();
  }

  async function load() {
    pager?.setDisabled(true);
    try {
      const payload=await requestJson(`${base}/bk/kasus?${params(true)}`); const data=payload.data||{}; const rows=Array.isArray(data.rows)?data.rows:[];
      state.total=Number(data.total||0); state.limit=Number(data.limit||state.limit); state.offset=Number(data.offset??state.offset);
      if (!rows.length && state.total>0 && state.offset>=state.total) { state.offset=Math.floor((state.total-1)/state.limit)*state.limit; await load(); return; }
      hideAlert(); renderRows(rows, Boolean(data.can_manage)); pager?.render(state);
    } catch(error) { show(error.message||'Data pelanggaran gagal dimuat.'); } finally { pager?.setDisabled(false); }
  }

  function bindRows() {
    document.querySelectorAll('.btn-detail-kasus').forEach(btn=>btn.addEventListener('click',()=>{const row=itemData(btn); if(row?.id) openDetail(row.id);}));
    document.querySelectorAll('.btn-edit-kasus').forEach(btn=>btn.addEventListener('click',()=>{const row=itemData(btn); if(row) openForm(row);}));
    document.querySelectorAll('.btn-delete-kasus').forEach(btn=>btn.addEventListener('click',async()=>{
      if(btn.dataset.busy==='1') return; const row=itemData(btn); if(!row?.id) return;
      const result=await Swal.fire({icon:'warning',title:'Hapus catatan pelanggaran?',text:`Catatan ${row.nama_siswa} dan seluruh tindak lanjutnya akan dihapus.`,showCancelButton:true,confirmButtonText:'Ya, hapus',cancelButtonText:'Batal',confirmButtonColor:'#d33'});
      if(!result.isConfirmed) return; setButtonBusy(btn,true,'Menghapus...');
      try { const payload=await requestJson(`${base}/bk/kasus/delete/${row.id}`,{method:'DELETE'}); show(payload.message||'Catatan berhasil dihapus.','success'); await load(); } catch(error){show(error.message||'Gagal menghapus catatan.');} finally{setButtonBusy(btn,false);}
    }));
  }

  function openForm(row=null) {
    if(!form||!modalEl) return; form.reset(); form.dataset.busy='0'; setButtonBusy(formSubmitButton(form),false); form.elements.id.value=row?.id||'';
    if(row) setRemoteSelectValue(form.elements.id_siswa,row.id_siswa,`${row.nama_siswa||'-'} — ${row.nisn||'-'}`); else setRemoteSelectValue(form.elements.id_siswa,'','');
    form.elements.id_pelanggaran.value=row?.id_pelanggaran||''; window.SisfourSearchableSelect?.sync(form.elements.id_pelanggaran);
    form.elements.tanggal.value=row?.tanggal||today(); form.elements.keterangan.value=row?.keterangan||'';
    const title=document.getElementById('judulModalKasus'); if(title) title.textContent=row?'Edit Catatan Pelanggaran':'Tambah Catatan Pelanggaran';
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  async function openDetail(idKasus) {
    if(!detailModalEl) return; bootstrap.Modal.getOrCreateInstance(detailModalEl).show(); detailLoading?.classList.remove('d-none'); detailContent?.classList.add('d-none'); if(detailLoading) detailLoading.textContent='Memuat data...';
    try { const payload=await requestJson(`${base}/bk/kasus/detail/${idKasus}`); renderDetail(payload.data||{}); detailLoading?.classList.add('d-none'); detailContent?.classList.remove('d-none'); } catch(error){if(detailLoading) detailLoading.textContent=error.message||'Detail gagal dimuat.';}
  }

  function renderDetail(data) {
    const kasus=data.kasus||{};
    document.getElementById('detailKasusSiswa').textContent=`${kasus.nama_siswa||'-'} — ${kasus.nisn||'-'}`;
    document.getElementById('detailKasusTanggal').textContent=kasus.tanggal||'-'; document.getElementById('detailKasusKategori').textContent=kasus.kategori||'-'; document.getElementById('detailKasusPelanggaran').textContent=kasus.nama_pelanggaran||'-'; document.getElementById('detailKasusKeterangan').textContent=kasus.keterangan||'-';
    if(formTindak){ resetTindakForm(); formTindak.elements.id_kasus.value=kasus.id||''; const select=formTindak.elements.tindak_lanjut; select.innerHTML='<option value="">Pilih tindak lanjut</option>'; (data.tindak_lanjut_options||[]).forEach(x=>select.add(new Option(x,x))); }
    const rows=Array.isArray(data.tindak_lanjut)?data.tindak_lanjut:[]; const count=document.getElementById('jumlahTindakLanjut'); if(count) count.textContent=String(rows.length);
    if(!timeline) return;
    timeline.innerHTML=rows.map(row=>`<div class="border rounded p-3" data-tindak="${encodeURIComponent(JSON.stringify(row))}"><div class="d-flex flex-wrap justify-content-between gap-2 mb-2"><div><strong>${esc(row.tindak_lanjut)}</strong><div class="small text-muted">${esc(row.tanggal)}</div></div>${data.can_manage?'<button type="button" class="btn btn-sm btn-outline-primary btn-edit-tindak">Edit</button>':''}</div><div>${esc(row.keterangan||'-')}</div><div class="small text-muted mt-2">Dicatat oleh: ${esc(row.nama_input||row.username_input||'-')}</div></div>`).join('')||'<div class="text-center text-muted py-3">Belum ada tindak lanjut.</div>';
    timeline.querySelectorAll('.btn-edit-tindak').forEach(btn=>btn.addEventListener('click',()=>{try{editTindak(JSON.parse(decodeURIComponent(btn.closest('[data-tindak]').dataset.tindak)));}catch{show('Data tindak lanjut tidak dapat dibaca.');}}));
  }

  function resetTindakForm(){ if(!formTindak) return; const idKasus=formTindak.elements.id_kasus.value; formTindak.reset(); formTindak.dataset.busy='0'; setButtonBusy(formSubmitButton(formTindak),false); formTindak.elements.id.value=''; formTindak.elements.id_kasus.value=idKasus; formTindak.elements.tanggal.value=today(); document.getElementById('judulTindakLanjut').textContent='Tambah Tindak Lanjut'; document.getElementById('btnBatalEditTindak')?.classList.add('d-none'); }
  function editTindak(row){ if(!formTindak) return; formTindak.elements.id.value=row.id||''; formTindak.elements.tanggal.value=row.tanggal||''; formTindak.elements.tindak_lanjut.value=row.tindak_lanjut||''; formTindak.elements.keterangan.value=row.keterangan||''; document.getElementById('judulTindakLanjut').textContent='Edit Tindak Lanjut'; document.getElementById('btnBatalEditTindak')?.classList.remove('d-none'); formTindak.scrollIntoView({behavior:'smooth',block:'start'}); }

  document.getElementById('btnKasusBaru')?.addEventListener('click',()=>openForm());
  document.getElementById('btnKasusCari')?.addEventListener('click',()=>{state.offset=0;load();});
  document.getElementById('btnKasusReset')?.addEventListener('click',()=>{ if(tahun){tahun.value=''; window.SisfourActiveYearDefault?.applyDefault(tahun);} if(search)search.value=''; if(kategori)kategori.value=''; if(mulai)mulai.value=''; if(selesai)selesai.value=''; state.offset=0; load(); });
  search?.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();state.offset=0;load();}});
  document.getElementById('btnKasusExport')?.addEventListener('click',event=>{event.preventDefault();const p=params(false);p.delete('format');window.location.href=`${base}/bk/kasus/export?${p.toString()}`;});

  form?.addEventListener('submit',async event=>{
    event.preventDefault(); if(form.dataset.busy==='1') return; form.dataset.busy='1'; const button=formSubmitButton(form); setButtonBusy(button,true,'Menyimpan...');
    const fd=new FormData(form); const id=String(fd.get('id')||''); fd.delete('id'); let url=`${base}/bk/kasus/create`; let options={method:'POST',body:fd};
    if(id){url=`${base}/bk/kasus/update/${id}`;options={method:'PUT',body:new URLSearchParams(fd),headers:{'Content-Type':'application/x-www-form-urlencoded'}};}
    try{const payload=await requestJson(url,options);bootstrap.Modal.getInstance(modalEl)?.hide();show(payload.message||'Catatan pelanggaran berhasil disimpan.','success');await load();if(!id&&payload.data?.id)await openDetail(payload.data.id);}catch(error){show(error.message||'Catatan pelanggaran gagal disimpan.');}finally{form.dataset.busy='0';setButtonBusy(button,false);}
  });

  formTindak?.addEventListener('submit',async event=>{
    event.preventDefault();if(formTindak.dataset.busy==='1')return;formTindak.dataset.busy='1';const button=formSubmitButton(formTindak);setButtonBusy(button,true,'Menyimpan...');
    const fd=new FormData(formTindak);const id=String(fd.get('id')||'');const idKasus=String(fd.get('id_kasus')||'');fd.delete('id');fd.delete('id_kasus');let url=`${base}/bk/kasus/${idKasus}/tindak-lanjut`;let options={method:'POST',body:fd};
    if(id){url=`${base}/bk/kasus/tindak-lanjut/${id}`;options={method:'PUT',body:new URLSearchParams(fd),headers:{'Content-Type':'application/x-www-form-urlencoded'}};}
    try{const payload=await requestJson(url,options);show(payload.message||'Tindak lanjut berhasil disimpan.','success');await openDetail(idKasus);}catch(error){show(error.message||'Tindak lanjut gagal disimpan.');}finally{formTindak.dataset.busy='0';setButtonBusy(button,false);}
  });
  document.getElementById('btnBatalEditTindak')?.addEventListener('click',resetTindakForm);
  if(!pageCanManage&&formTindak)formTindak.classList.add('d-none');
  load();
})();
