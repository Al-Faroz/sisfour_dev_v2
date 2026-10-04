(() => {
'use strict';

const app=document.getElementById('dokumenSiswaApp');
if(!app)return;
const base=String(app.dataset.baseUrl||'').replace(/\/+$/,'');
const canManage=app.dataset.canManage==='1';
const focusImport=app.dataset.focusImport==='1';
let classesByPeriod={};
try{classesByPeriod=JSON.parse(document.getElementById('dokumenClassesData')?.textContent||'{}');}catch{}

const alertBox=document.getElementById('dokumenAlert');
const modalEl=document.getElementById('modalDokumen');
const bulkModalEl=document.getElementById('modalBulkDokumen');
const form=document.getElementById('formDokumen');
const target=document.getElementById('dokumenTarget');
const studentField=document.getElementById('dokumenStudentField');
const levelField=document.getElementById('dokumenLevelField');
const periodSelect=document.getElementById('dokumenFormTahun');
const studentQuery=document.getElementById('dokumenStudentQuery');
const studentSelect=document.getElementById('dokumenIdSiswa');
const bulkForm=document.getElementById('formBulkDokumen');
let previewToken='';

const esc=v=>{const d=document.createElement('div');d.textContent=v??'';return d.innerHTML;};
function show(message,type='danger'){if(!alertBox)return;alertBox.className='alert alert-'+type;alertBox.textContent=message;window.scrollTo({top:0,behavior:'smooth'});}
function rowData(el){const h=el.closest('[data-json]');try{return JSON.parse(decodeURIComponent(h?.dataset.json||''));}catch{return null;}}
async function requestJson(url,opt={}){const r=await fetch(url,{...opt,credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest',...(opt.headers||{})}});let p;try{p=await r.json();}catch{throw new Error('Response server tidak valid.');}if(!r.ok||p.status!=='success')throw new Error(p.message||'Proses gagal.');return p;}
function busy(btn,on,label='Memproses...'){if(!btn)return;if(on){btn.dataset.oldHtml=btn.innerHTML;btn.disabled=true;btn.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>'+label;}else{btn.disabled=false;if(btn.dataset.oldHtml)btn.innerHTML=btn.dataset.oldHtml;delete btn.dataset.oldHtml;}}
function toggleTarget(){const individual=target?.value!=='TINGKAT';studentField?.classList.toggle('d-none',!individual);levelField?.classList.toggle('d-none',individual);if(studentSelect)studentSelect.required=individual;const level=form?.elements.tingkat;if(level)level.required=!individual;}
target?.addEventListener('change',toggleTarget);

document.getElementById('btnDokumenBaru')?.addEventListener('click',()=>{
 form?.reset();form.elements.id.value='';document.getElementById('dokumenModalTitle').textContent='Tambah Dokumen';
 if(periodSelect)periodSelect.value=String(document.querySelector('select[name="id_tahun"]')?.value||periodSelect.value);
 if(studentSelect)studentSelect.innerHTML='<option value="">Pilih hasil pencarian</option>';
 toggleTarget();bootstrap.Modal.getOrCreateInstance(modalEl).show();
});

document.getElementById('btnCariDokumenSiswa')?.addEventListener('click',async()=>{
 const q=String(studentQuery?.value||'').trim(),idTahun=String(periodSelect?.value||'');
 if(q.length<2){show('Ketik minimal 2 karakter untuk mencari siswa.');return;}
 const btn=document.getElementById('btnCariDokumenSiswa');busy(btn,true,'Mencari...');
 try{
  const p=await requestJson(base+'/dokumen-siswa/cari-siswa?'+new URLSearchParams({id_tahun:idTahun,q}));
  const rows=p.data?.rows||[];
  studentSelect.innerHTML='<option value="">Pilih hasil pencarian</option>'+rows.map(r=>'<option value="'+r.id+'">'+esc(r.text)+'</option>').join('');
  if(!rows.length)show('Siswa tidak ditemukan pada periode yang dipilih.','warning');
 }catch(err){show(err.message);}finally{busy(btn,false);}
});

document.querySelectorAll('.btn-edit-dokumen').forEach(btn=>btn.addEventListener('click',()=>{
 const r=rowData(btn);if(!r)return;
 form.reset();form.elements.id.value=String(r.id||'');form.elements.id_tahun.value=String(r.id_tahun||'');form.elements.target_type.value=r.target_type||'INDIVIDU';form.elements.format_file.value=r.format_file||'PDF';form.elements.judul.value=r.judul||'';form.elements.link_gdrive.value=r.link_gdrive||'';form.elements.status.value=r.status||'PUBLISHED';form.elements.tingkat.value=r.tingkat||'';
 studentSelect.innerHTML='<option value="">Pilih hasil pencarian</option>';
 if(r.id_siswa){const label=(r.nisn||'')+' — '+(r.nama_siswa||'')+(r.nama_kelas?' · '+r.nama_kelas:'');studentSelect.add(new Option(label,String(r.id_siswa),true,true));}
 document.getElementById('dokumenModalTitle').textContent='Edit Dokumen';toggleTarget();bootstrap.Modal.getOrCreateInstance(modalEl).show();
}));

form?.addEventListener('submit',async e=>{
 e.preventDefault();const fd=new FormData(form),id=String(fd.get('id')||'');fd.delete('id');const btn=form.querySelector('[type=submit]');busy(btn,true,'Menyimpan...');
 try{
  const url=id?base+'/dokumen-siswa/update/'+encodeURIComponent(id):base+'/dokumen-siswa/create';
  const opt=id?{method:'PUT',body:new URLSearchParams(fd),headers:{'Content-Type':'application/x-www-form-urlencoded'}}:{method:'POST',body:fd};
  const p=await requestJson(url,opt);bootstrap.Modal.getInstance(modalEl)?.hide();show(p.message||'Berhasil.','success');setTimeout(()=>location.reload(),350);
 }catch(err){show(err.message);}finally{busy(btn,false);}
});

document.querySelectorAll('.btn-archive-dokumen').forEach(btn=>btn.addEventListener('click',async()=>{
 if(!confirm('Arsipkan Dokumen ini? Link tidak dihapus dari Google Drive.'))return;busy(btn,true,'...');
 try{const p=await requestJson(base+'/dokumen-siswa/archive/'+encodeURIComponent(btn.dataset.id),{method:'PUT'});show(p.message||'Berhasil.','success');setTimeout(()=>location.reload(),300);}catch(err){show(err.message);}finally{busy(btn,false);}
}));

document.querySelectorAll('.btn-rollback-batch').forEach(btn=>btn.addEventListener('click',async()=>{
 if(!confirm('Rollback seluruh metadata dari batch ini? File Google Drive tidak akan dihapus.'))return;
 busy(btn,true,'Rollback...');
 try{
  const p=await requestJson(base+'/dokumen-siswa/import/rollback/'+encodeURIComponent(btn.dataset.id),{method:'POST'});
  show(p.message||'Batch berhasil di-rollback.','success');
  setTimeout(()=>location.reload(),350);
 }catch(err){show(err.message);}finally{busy(btn,false);}
}));

const templatePeriod=document.getElementById('templateTahun'),templateFilter=document.getElementById('templateFilter'),templateLevelWrap=document.getElementById('templateLevelWrap'),templateClassWrap=document.getElementById('templateClassWrap'),templateLevel=document.getElementById('templateTingkat'),templateClass=document.getElementById('templateKelas');
function renderTemplateControls(){
 const mode=templateFilter?.value||'all';templateLevelWrap?.classList.toggle('d-none',mode!=='tingkat');templateClassWrap?.classList.toggle('d-none',mode!=='kelas');
 const classes=classesByPeriod[String(templatePeriod?.value||'')]||[];
 if(templateClass)templateClass.innerHTML=classes.map(c=>'<option value="'+c.id+'">'+esc(c.nama_kelas)+'</option>').join('');
}
templateFilter?.addEventListener('change',renderTemplateControls);templatePeriod?.addEventListener('change',renderTemplateControls);

document.getElementById('btnDownloadTemplateDokumen')?.addEventListener('click',()=>{
 const q=new URLSearchParams({id_tahun:String(templatePeriod?.value||'')});
 if(templateFilter?.value==='tingkat')q.set('tingkat',String(templateLevel?.value||''));
 if(templateFilter?.value==='kelas')q.set('id_kelas',String(templateClass?.value||''));
 window.location.href=base+'/dokumen-siswa/template?'+q.toString();
});

document.getElementById('btnBulkDokumen')?.addEventListener('click',()=>{previewToken='';document.getElementById('bulkDokumenPreview')?.classList.add('d-none');renderTemplateControls();bootstrap.Modal.getOrCreateInstance(bulkModalEl).show();});

bulkForm?.addEventListener('submit',async e=>{
 e.preventDefault();const btn=bulkForm.querySelector('[type=submit]');busy(btn,true,'Membaca XLSX...');
 try{
  const p=await requestJson(base+'/dokumen-siswa/import/preview',{method:'POST',body:new FormData(bulkForm)}),d=p.data||{};
  previewToken=String(d.token||'');document.getElementById('bulkDokumenPreview').classList.remove('d-none');
  document.getElementById('bulkTotal').textContent=(d.total_row||0)+' total';document.getElementById('bulkValid').textContent=(d.total_valid||0)+' valid';document.getElementById('bulkError').textContent=(d.total_error||0)+' error';
  const errors=document.getElementById('bulkErrors'),warnings=document.getElementById('bulkWarnings');
  errors.classList.toggle('d-none',!(d.errors||[]).length);errors.innerHTML=(d.errors||[]).map(x=>'<div>'+esc(x)+'</div>').join('');
  warnings.classList.toggle('d-none',!(d.warnings||[]).length);warnings.innerHTML=(d.warnings||[]).map(x=>'<div>'+esc(x)+'</div>').join('');
  document.getElementById('bulkPreviewBody').innerHTML=(d.rows||[]).map(r=>'<tr><td>'+r.excel_row+'</td><td>'+esc(r.nisn)+'</td><td>'+esc(r.nama_siswa)+'</td><td>'+esc(r.nama_kelas)+'</td><td class="text-truncate" style="max-width:260px">'+esc(r.link_gdrive)+'</td></tr>').join('');
  document.getElementById('btnCommitBulkDokumen').classList.toggle('d-none',!d.can_commit);
  show(p.message||'Preview selesai.',d.can_commit?'success':'warning');
 }catch(err){show(err.message);}finally{busy(btn,false);}
});

document.getElementById('btnCommitBulkDokumen')?.addEventListener('click',async()=>{
 if(!previewToken)return;const btn=document.getElementById('btnCommitBulkDokumen');busy(btn,true,'Commit...');
 try{const fd=new FormData();fd.append('token',previewToken);const p=await requestJson(base+'/dokumen-siswa/import/commit',{method:'POST',body:fd});show(p.message||'Import berhasil.','success');bootstrap.Modal.getInstance(bulkModalEl)?.hide();setTimeout(()=>location.reload(),400);}catch(err){show(err.message);}finally{busy(btn,false);}
});

renderTemplateControls();toggleTarget();
if(canManage&&focusImport){setTimeout(()=>{document.getElementById('btnBulkDokumen')?.click();},150);}
})();