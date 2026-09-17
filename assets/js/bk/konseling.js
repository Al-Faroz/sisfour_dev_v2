(() => {
  'use strict';

  const app = document.getElementById('bkKonselingApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const canManage = app.dataset.canManage === '1';
  const canExport = app.dataset.canExport === '1';
  const body = document.getElementById('konselingBody');
  const mobileList = document.getElementById('konselingMobileList');
  const totalLabel = document.getElementById('konselingTotal');
  const alertBox = document.getElementById('konselingAlert');
  const tahun = document.getElementById('konselingTahun');
  const search = document.getElementById('konselingSearch');
  const filterKelas = document.getElementById('konselingFilterKelas');
  const filterStatus = document.getElementById('konselingFilterStatus');
  const filterBidang = document.getElementById('konselingFilterBidang');
  const mulai = document.getElementById('konselingMulai');
  const selesai = document.getElementById('konselingSelesai');
  const createModalEl = document.getElementById('modalKonselingBaru');
  const detailModalEl = document.getElementById('modalKonselingDetail');
  const createForm = document.getElementById('formKonselingBaru');
  const updateForm = document.getElementById('formKonselingUpdate');
  const followForm = document.getElementById('formKonselingTindak');
  const kelasSelect = document.getElementById('konselingKelas');
  const siswaSelect = document.getElementById('konselingSiswa');
  const bidangSelect = document.getElementById('konselingBidang');
  const topikSelect = document.getElementById('konselingTopik');
  const detailLoading = document.getElementById('konselingDetailLoading');
  const detailContent = document.getElementById('konselingDetailContent');
  const followTimeline = document.getElementById('timelineKonselingTindak');
  if (!body || !mobileList) return;

  const state = { limit:25, offset:0, total:0, detailId:0 };
  let topikMap={};
  try { topikMap=JSON.parse(document.getElementById('konselingTopikData')?.textContent||'{}'); } catch { topikMap={}; }

  const pager=window.SisfourPagination?.mount(body,{id:'bkKonselingPager',label:'catatan konseling',onChange:(next)=>{state.limit=next.limit;state.offset=next.offset;load();}});
  const esc=(value)=>{const div=document.createElement('div');div.textContent=value??'';return div.innerHTML;};
  const today=()=>{const d=new Date();return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;};
  const statusBadge=(value)=>value==='Selesai'?'success':'warning';

  function show(message,type='danger'){if(!alertBox)return;alertBox.className=`alert alert-${type}`;alertBox.textContent=message;}
  function hideAlert(){alertBox?.classList.add('d-none');}
  function setBusy(button,busy,label='Memproses...'){if(!button)return;if(busy){if(button.dataset.busy==='1')return;button.dataset.busy='1';button.dataset.busyHtml=button.innerHTML;button.disabled=true;button.innerHTML=`<span class="spinner-border spinner-border-sm me-2"></span>${label}`;return;}button.disabled=false;if(button.dataset.busyHtml!==undefined)button.innerHTML=button.dataset.busyHtml;delete button.dataset.busy;delete button.dataset.busyHtml;}
  const submitButton=(form)=>form?.querySelector('button[type="submit"],input[type="submit"]')||null;

  async function requestJson(url,options={}){const response=await fetch(url,{...options,headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest',...(options.headers||{})},credentials:'same-origin'});let payload;try{payload=await response.json();}catch{throw new Error('Response server tidak valid.');}if(!response.ok||payload.status!=='success')throw new Error(payload.message||'Proses gagal.');return payload;}

  function params(withPaging=true){const p=new URLSearchParams({format:'json'});if(withPaging){p.set('limit',String(state.limit));p.set('offset',String(state.offset));}if(tahun?.value)p.set('id_tahun',tahun.value);if(search?.value)p.set('search',search.value);if(filterKelas?.value)p.set('id_kelas',filterKelas.value);if(filterStatus?.value)p.set('status',filterStatus.value);if(filterBidang?.value)p.set('bidang',filterBidang.value);if(mulai?.value)p.set('tanggal_mulai',mulai.value);if(selesai?.value)p.set('tanggal_selesai',selesai.value);return p;}

  function syncFilterClasses(rows){if(!filterKelas)return;const selected=String(filterKelas.value||'');filterKelas.innerHTML='<option value="">Semua kelas</option>';(rows||[]).forEach(row=>filterKelas.add(new Option(row.nama_kelas,row.id)));if([...filterKelas.options].some(x=>x.value===selected))filterKelas.value=selected;window.SisfourSearchableSelect?.sync(filterKelas);}

  function render(rows){
    body.innerHTML=rows.map(row=>`<tr><td class="text-nowrap">${esc(row.tanggal)}</td><td><strong>${esc(row.nama_siswa)}</strong><div class="small text-muted">${esc(row.nisn||'-')}</div></td><td>${esc(row.nama_kelas)}</td><td>${esc(row.bentuk_layanan)}<div class="small text-muted">Pertemuan ke-${Number(row.pertemuan_ke||1)}</div></td><td><span class="badge bg-label-secondary">${esc(row.bidang)}</span><div class="small mt-1">${esc(row.topik)}</div></td><td><span class="badge bg-label-${statusBadge(row.status)}">${esc(row.status)}</span></td><td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary btn-detail-konseling" data-id="${Number(row.id)}">Detail</button></td></tr>`).join('')||'<tr><td colspan="7" class="text-center text-muted py-4">Belum ada catatan Konseling BK.</td></tr>';
    mobileList.innerHTML=rows.map(row=>`<div class="list-group-item py-3"><div class="d-flex justify-content-between align-items-start gap-2 mb-2"><div class="sisfour-cell-primary"><span class="sisfour-cell-title">${esc(row.nama_siswa)}</span><span class="sisfour-cell-meta">${esc(row.nama_kelas)} · ${esc(row.tanggal)} · ${esc(row.nama_tahun||'')} ${esc(row.semester||'')}</span></div><span class="badge bg-label-${statusBadge(row.status)}">${esc(row.status)}</span></div><div class="small mb-1"><strong>${esc(row.bentuk_layanan)}</strong> · Pertemuan ke-${Number(row.pertemuan_ke||1)}</div><div class="small text-muted mb-3">${esc(row.bidang)} · ${esc(row.topik)}</div><button type="button" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-detail-konseling" data-id="${Number(row.id)}">Detail Konseling</button></div>`).join('')||'<div class="list-group-item text-center text-muted py-4">Belum ada catatan Konseling BK.</div>';
    document.querySelectorAll('.btn-detail-konseling').forEach(btn=>btn.addEventListener('click',()=>openDetail(btn.dataset.id)));
  }

  async function load(){pager?.setDisabled(true);try{const payload=await requestJson(`${base}/bk/konseling?${params(true)}`);const data=payload.data||{},rows=Array.isArray(data.rows)?data.rows:[];state.total=Number(data.total||0);state.limit=Number(data.limit||state.limit);state.offset=Number(data.offset??state.offset);if(!rows.length&&state.total>0&&state.offset>=state.total){state.offset=Math.floor((state.total-1)/state.limit)*state.limit;await load();return;}hideAlert();render(rows);syncFilterClasses(data.kelas||[]);pager?.render(state);if(totalLabel)totalLabel.textContent=`${state.total} catatan`;}catch(error){show(error.message||'Data Konseling BK gagal dimuat.');}finally{pager?.setDisabled(false);}}

  function renderTopics(){if(!bidangSelect||!topikSelect)return;const values=Array.isArray(topikMap[bidangSelect.value])?topikMap[bidangSelect.value]:[];topikSelect.innerHTML='<option value="">Pilih topik</option>';values.forEach(value=>topikSelect.add(new Option(value,value)));}
  async function loadStudents(){if(!siswaSelect||!kelasSelect)return;siswaSelect.disabled=true;siswaSelect.innerHTML='<option value="">Memuat siswa...</option>';window.SisfourSearchableSelect?.sync(siswaSelect);const id=String(kelasSelect.value||'');if(!id){siswaSelect.innerHTML='<option value="">Pilih kelas terlebih dahulu</option>';window.SisfourSearchableSelect?.sync(siswaSelect);return;}try{const payload=await requestJson(`${base}/bk/konseling/siswa-kelas/${encodeURIComponent(id)}`);siswaSelect.innerHTML='<option value="">Pilih siswa</option>';(payload.data?.rows||[]).forEach(row=>siswaSelect.add(new Option(row.text||`${row.nama} — ${row.nisn}`,row.id)));siswaSelect.disabled=false;window.SisfourSearchableSelect?.sync(siswaSelect);}catch(error){siswaSelect.innerHTML='<option value="">Siswa gagal dimuat</option>';window.SisfourSearchableSelect?.sync(siswaSelect);show(error.message||'Daftar siswa gagal dimuat.');}}

  function openCreate(){if(!createForm||!createModalEl)return;createForm.reset();createForm.dataset.busy='0';setBusy(submitButton(createForm),false);const date=document.getElementById('konselingTanggal');if(date)date.value=today();if(siswaSelect){siswaSelect.innerHTML='<option value="">Pilih kelas terlebih dahulu</option>';siswaSelect.disabled=true;window.SisfourSearchableSelect?.sync(siswaSelect);}renderTopics();bootstrap.Modal.getOrCreateInstance(createModalEl).show();}

  function setStoredRencana(select,value){if(!select)return;select.querySelectorAll('option[data-stored-legacy="1"]').forEach(option=>option.remove());const stored=String(value||'').trim();if(!stored){select.value='';return;}if(![...select.options].some(option=>option.value===stored)){const option=new Option(`${stored} (tersimpan)`,stored);option.dataset.storedLegacy='1';select.add(option);}select.value=stored;}

  function resetFollowForm(idKonseling=''){if(!followForm)return;followForm.reset();followForm.dataset.busy='0';setBusy(submitButton(followForm),false);followForm.elements.id.value='';followForm.elements.id_konseling.value=idKonseling||state.detailId||'';followForm.elements.tanggal.value=today();followForm.elements.status.value='Proses';setStoredRencana(followForm.elements.rencana_berikutnya,'');document.getElementById('judulKonselingTindak').textContent='Tambah Tindak Lanjut';document.getElementById('btnBatalEditKonselingTindak')?.classList.add('d-none');}

  function editFollow(row){if(!followForm)return;followForm.elements.id.value=row.id||'';followForm.elements.id_konseling.value=row.id_konseling||state.detailId||'';followForm.elements.tanggal.value=row.tanggal||today();followForm.elements.perkembangan.value=row.perkembangan||'';followForm.elements.hasil_kesepakatan.value=row.hasil_kesepakatan||'';setStoredRencana(followForm.elements.rencana_berikutnya,row.rencana_berikutnya||'');followForm.elements.tanggal_berikutnya.value=row.tanggal_berikutnya||'';followForm.elements.status.value=row.status||'Proses';document.getElementById('judulKonselingTindak').textContent='Edit Tindak Lanjut';document.getElementById('btnBatalEditKonselingTindak')?.classList.remove('d-none');followForm.scrollIntoView({behavior:'smooth',block:'start'});}

  function renderFollowUps(rows,detailCanManage){const count=document.getElementById('jumlahKonselingTindak');if(count)count.textContent=String(rows.length);if(!followTimeline)return;followTimeline.innerHTML=rows.map(row=>`<div class="border rounded p-3" data-follow="${encodeURIComponent(JSON.stringify(row))}"><div class="d-flex flex-wrap justify-content-between gap-2 mb-2"><div><strong>${esc(row.tanggal)}</strong><div class="small text-muted">${esc(row.nama_pencatat||row.username_pencatat||'-')}</div></div><div class="d-flex align-items-center gap-2"><span class="badge bg-label-${statusBadge(row.status)}">${esc(row.status)}</span>${detailCanManage?'<button type="button" class="btn btn-sm btn-outline-primary btn-edit-konseling-tindak">Edit</button>':''}</div></div><div class="mb-2">${esc(row.perkembangan||'-')}</div>${row.hasil_kesepakatan?`<div class="small"><strong>Hasil:</strong> ${esc(row.hasil_kesepakatan)}</div>`:''}${row.rencana_berikutnya?`<div class="small"><strong>Rencana:</strong> ${esc(row.rencana_berikutnya)}</div>`:''}${row.tanggal_berikutnya?`<div class="small text-muted">Pertemuan berikutnya: ${esc(row.tanggal_berikutnya)}</div>`:''}</div>`).join('')||'<div class="text-center text-muted py-3">Belum ada tindak lanjut Konseling.</div>';followTimeline.querySelectorAll('.btn-edit-konseling-tindak').forEach(btn=>btn.addEventListener('click',()=>{try{editFollow(JSON.parse(decodeURIComponent(btn.closest('[data-follow]').dataset.follow)));}catch{show('Data tindak lanjut tidak dapat dibaca.');}}));}

  function fillDetail(data){const row=data.row||{};state.detailId=Number(row.id||0);document.getElementById('detailKonselingSiswa').textContent=`${row.nama_siswa||'-'} — ${row.nisn||'-'}`;document.getElementById('detailKonselingKelas').textContent=row.nama_kelas||'-';document.getElementById('detailKonselingTanggal').textContent=`${row.tanggal||'-'} · Pertemuan ke-${Number(row.pertemuan_ke||1)}`;document.getElementById('detailKonselingLayanan').textContent=row.bentuk_layanan||'-';document.getElementById('detailKonselingTopik').textContent=`${row.bidang||'-'} · ${row.topik||'-'}`;document.getElementById('detailKonselingCara').textContent=row.cara_hadir||'-';document.getElementById('detailKonselingGuru').textContent=row.nama_pencatat||row.nama_guru_bk||'-';
    const badge=document.getElementById('detailKonselingStatus');if(badge){badge.textContent=row.status||'Proses';badge.className=`badge bg-label-${statusBadge(row.status)}`;}
    if(updateForm){updateForm.dataset.busy='0';setBusy(submitButton(updateForm),false);updateForm.elements.id.value=row.id||'';updateForm.elements.uraian_masalah.value=row.uraian_masalah||'';updateForm.elements.hasil_kesepakatan.value=row.hasil_kesepakatan||'';setStoredRencana(updateForm.elements.rencana_berikutnya,row.rencana_berikutnya||'');updateForm.elements.tanggal_berikutnya.value=row.tanggal_berikutnya||'';updateForm.elements.status.value=row.status||'Proses';}
    const ur=document.getElementById('detailKonselingUraianReadonly'),ha=document.getElementById('detailKonselingHasilReadonly');if(ur)ur.textContent=row.uraian_masalah||'Belum diisi.';if(ha)ha.textContent=row.hasil_kesepakatan||'Belum diisi.';
    resetFollowForm(row.id||'');renderFollowUps(Array.isArray(data.tindak_lanjut)?data.tindak_lanjut:[],Boolean(data.can_manage));
  }

  async function openDetail(id){if(!detailModalEl)return;state.detailId=Number(id||0);bootstrap.Modal.getOrCreateInstance(detailModalEl).show();detailLoading?.classList.remove('d-none');detailContent?.classList.add('d-none');if(detailLoading)detailLoading.textContent='Memuat data...';try{const payload=await requestJson(`${base}/bk/konseling/detail/${encodeURIComponent(id)}`);fillDetail(payload.data||{});detailLoading?.classList.add('d-none');detailContent?.classList.remove('d-none');}catch(error){if(detailLoading)detailLoading.textContent=error.message||'Detail gagal dimuat.';}}

  document.getElementById('btnKonselingBaru')?.addEventListener('click',openCreate);kelasSelect?.addEventListener('change',loadStudents);bidangSelect?.addEventListener('change',renderTopics);
  tahun?.addEventListener('change',()=>{if(filterKelas){filterKelas.value='';window.SisfourSearchableSelect?.sync(filterKelas);}});
  document.getElementById('btnKonselingCari')?.addEventListener('click',()=>{state.offset=0;load();});
  document.getElementById('btnKonselingReset')?.addEventListener('click',()=>{if(tahun){tahun.value='';window.SisfourActiveYearDefault?.applyDefault(tahun);}if(search)search.value='';if(filterKelas){filterKelas.value='';window.SisfourSearchableSelect?.sync(filterKelas);}if(filterStatus)filterStatus.value='';if(filterBidang)filterBidang.value='';if(mulai)mulai.value='';if(selesai)selesai.value='';state.offset=0;load();});
  search?.addEventListener('keydown',event=>{if(event.key==='Enter'){event.preventDefault();state.offset=0;load();}});
  if(canExport)document.getElementById('btnKonselingExport')?.addEventListener('click',event=>{event.preventDefault();const p=params(false);p.delete('format');location.href=`${base}/bk/konseling/export?${p.toString()}`;});

  createForm?.addEventListener('submit',async event=>{event.preventDefault();if(createForm.dataset.busy==='1')return;createForm.dataset.busy='1';const button=submitButton(createForm);setBusy(button,true,'Menyimpan...');try{const payload=await requestJson(`${base}/bk/konseling/create`,{method:'POST',body:new FormData(createForm)});bootstrap.Modal.getInstance(createModalEl)?.hide();show(payload.message||'Tahap 1 berhasil disimpan.','success');await load();if(payload.data?.id)await openDetail(payload.data.id);}catch(error){show(error.message||'Tahap 1 gagal disimpan.');}finally{createForm.dataset.busy='0';setBusy(button,false);}});

  updateForm?.addEventListener('submit',async event=>{event.preventDefault();if(updateForm.dataset.busy==='1')return;updateForm.dataset.busy='1';const button=submitButton(updateForm);setBusy(button,true,'Menyimpan...');const fd=new FormData(updateForm),id=String(fd.get('id')||'');fd.delete('id');try{const payload=await requestJson(`${base}/bk/konseling/update/${encodeURIComponent(id)}`,{method:'PUT',body:new URLSearchParams(fd),headers:{'Content-Type':'application/x-www-form-urlencoded'}});show(payload.message||'Pertemuan awal berhasil diperbarui.','success');await load();await openDetail(id);}catch(error){show(error.message||'Update Konseling gagal disimpan.');}finally{updateForm.dataset.busy='0';setBusy(button,false);}});

  followForm?.addEventListener('submit',async event=>{event.preventDefault();if(followForm.dataset.busy==='1')return;followForm.dataset.busy='1';const button=submitButton(followForm);setBusy(button,true,'Menyimpan...');const fd=new FormData(followForm),id=String(fd.get('id')||''),idKonseling=String(fd.get('id_konseling')||state.detailId||'');fd.delete('id');fd.delete('id_konseling');let url=`${base}/bk/konseling/${encodeURIComponent(idKonseling)}/tindak-lanjut`,options={method:'POST',body:fd};if(id){url=`${base}/bk/konseling/tindak-lanjut/${encodeURIComponent(id)}`;options={method:'PUT',body:new URLSearchParams(fd),headers:{'Content-Type':'application/x-www-form-urlencoded'}};}try{const payload=await requestJson(url,options);show(payload.message||'Tindak lanjut Konseling berhasil disimpan.','success');await load();await openDetail(idKonseling);}catch(error){show(error.message||'Tindak lanjut Konseling gagal disimpan.');}finally{followForm.dataset.busy='0';setBusy(button,false);}});
  document.getElementById('btnBatalEditKonselingTindak')?.addEventListener('click',()=>resetFollowForm(state.detailId));

  renderTopics();
  load();
})();
