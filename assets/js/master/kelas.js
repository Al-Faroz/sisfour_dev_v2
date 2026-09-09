(() => {
    'use strict';
    const app=document.getElementById('masterKelasApp'); if(!app)return;
    const baseUrl=app.dataset.baseUrl.replace(/\/+$/,'');
    const table=document.getElementById('tableKelas'), tbody=table.querySelector('tbody');
    const filterForm=document.getElementById('formFilterKelas'), form=document.getElementById('formKelas');
    const modal=new bootstrap.Modal(document.getElementById('modalKelas'));
    let rows=[], editingId=null, dataTable=null;
    const endpoint=p=>`${baseUrl}/${p.replace(/^\/+/,'')}`;
    const esc=v=>{const d=document.createElement('div');d.textContent=v??'';return d.innerHTML;};
    const parse=async r=>{const d=await r.json().catch(()=>({}));if(!r.ok||d.status==='error')throw new Error(d.message||'Permintaan gagal.');return d;};
    const error=e=>Swal.fire({icon:'error',title:'Gagal',text:e?.message||'Terjadi kesalahan.'});
    const success=m=>Swal.fire({icon:'success',title:'Berhasil',text:m,timer:1500,showConfirmButton:false});
    const render=()=>{if(dataTable){dataTable.destroy();dataTable=null;}
        tbody.innerHTML=rows.map((r,i)=>`<tr>
        <td>${i+1}</td><td class="fw-semibold">${esc(r.nama_kelas)}</td><td>${esc(r.tingkat)}</td><td>${esc(r.rombel)}</td>
        <td>${esc(r.nama_tahun)} - ${esc(r.semester)} ${Number(r.tahun_aktif)===1?'<span class="badge bg-label-success ms-1">Aktif</span>':''}</td>
        <td>${Number(r.jumlah_siswa||0)} siswa</td>
        <td><div class="d-flex gap-1">
        <button type="button" class="btn btn-sm btn-outline-primary btn-edit" data-id="${r.id}"><i class="bx bx-edit"></i></button>
        <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="${r.id}"><i class="bx bx-trash"></i></button>
        </div></td></tr>`).join('');
        if(typeof window.DataTable==='function')dataTable=new window.DataTable(table,{pageLength:25,order:[[4,'desc'],[2,'asc'],[3,'asc']]});
    };
    const load=async()=>{try{const q=new URLSearchParams(new FormData(filterForm));[...q.entries()].forEach(([k,v])=>{if(!String(v).trim())q.delete(k);});
        const r=await fetch(endpoint(`master/kelas/json?${q}`),{headers:{'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
        const d=await parse(r); rows=Array.isArray(d.data)?d.data:[]; render();}catch(e){error(e);}};
    document.getElementById('btnTambahKelas').addEventListener('click',()=>{editingId=null;form.reset();document.getElementById('kelasId').value='';document.getElementById('modalKelasTitle').textContent='Tambah Kelas';modal.show();});
    form.addEventListener('submit',async e=>{e.preventDefault();const btn=document.getElementById('btnSimpanKelas'),sp=btn.querySelector('.spinner-border');btn.disabled=true;sp.classList.remove('d-none');
        try{let r;if(editingId===null){r=await fetch(endpoint('master/kelas/create'),{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});}
        else{const p=new URLSearchParams();for(const[k,v]of new FormData(form).entries())if(k!=='csrf_test_name')p.append(k,v);
            r=await fetch(endpoint(`master/kelas/update/${editingId}`),{method:'PUT',body:p,headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});}
        const d=await parse(r);modal.hide();await success(d.message);await load();}catch(err){error(err);}finally{btn.disabled=false;sp.classList.add('d-none');}});
    tbody.addEventListener('click',async e=>{const edit=e.target.closest('.btn-edit'),del=e.target.closest('.btn-delete');
        if(edit){const id=Number(edit.dataset.id),r=rows.find(x=>Number(x.id)===id);if(!r)return;editingId=id;form.reset();document.getElementById('kelasId').value=String(id);document.getElementById('tingkat').value=r.tingkat??'';document.getElementById('rombel').value=r.rombel??'';document.getElementById('id_tahun').value=r.id_tahun??'';document.getElementById('modalKelasTitle').textContent='Edit Kelas';modal.show();return;}
        if(del){const id=Number(del.dataset.id),c=await Swal.fire({icon:'warning',title:'Hapus kelas?',text:'Kelas hanya dapat dihapus jika tidak lagi memiliki dependency aktif.',showCancelButton:true,confirmButtonText:'Ya, hapus',cancelButtonText:'Batal'});if(!c.isConfirmed)return;
            try{const r=await fetch(endpoint(`master/kelas/delete/${id}`),{method:'DELETE',headers:{'X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});const d=await parse(r);await success(d.message);await load();}catch(err){error(err);}}});
    filterForm.addEventListener('submit',e=>{e.preventDefault();load();});
    document.getElementById('btnResetFilter').addEventListener('click',()=>{filterForm.reset();load();});
    load();
})();