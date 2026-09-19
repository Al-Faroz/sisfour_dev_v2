(() => {
  const app = document.getElementById('ptspPublicApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/$/, '');
  const autoPrint = app.dataset.autoPrint === '1';
  const autoDownloadPdf = app.dataset.autoDownloadPdf === '1';
  const alertBox = document.getElementById('ptspPublicAlert');
  const receipt = document.getElementById('ptspReceipt');
  const receiptBody = document.getElementById('ptspReceiptBody');

  const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
    '&':'&amp;',
    '<':'&lt;',
    '>':'&gt;',
    '"':'&quot;',
    "'":'&#039;'
  }[c]));

  function show(message, type = 'danger', extra = '') {
    if (!alertBox) return;
    alertBox.className = `alert alert-${type}`;
    alertBox.innerHTML = `${esc(message)}${extra}`;
    alertBox.scrollIntoView({behavior:'smooth', block:'center'});
  }

  function busy(form, on) {
    form.dataset.busy = on ? '1' : '0';
    const button = form.querySelector('button[type="submit"]');
    if (!button) return;
    button.disabled = on;
    if (on) {
      button.dataset.label = button.textContent;
      button.textContent = 'Mengirim...';
    } else if (button.dataset.label) {
      button.textContent = button.dataset.label;
    }
  }

  async function submit(form, endpoint) {
    if (form.dataset.busy === '1') return null;
    busy(form, true);
    try {
      const response = await fetch(`${base}/${endpoint}`, {
        method:'POST',
        body:new FormData(form)
      });
      const payload = await response.json().catch(() => ({}));
      if (!response.ok || payload.status !== 'success') {
        throw new Error(payload.message || 'Permintaan gagal.');
      }
      return payload;
    } finally {
      busy(form, false);
    }
  }

  function printReceipt() {
    if (!receipt || receipt.classList.contains('d-none')) return;
    window.print();
  }

  function downloadReceiptPdf(pdf) {
    if (!pdf?.base64) return false;

    try {
      const binary = atob(String(pdf.base64));
      const bytes = new Uint8Array(binary.length);
      for (let i = 0; i < binary.length; i += 1) {
        bytes[i] = binary.charCodeAt(i);
      }

      const blob = new Blob([bytes], {
        type: String(pdf.mime_type || 'application/pdf')
      });
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = String(pdf.filename || 'bukti-layanan-ptsp.pdf');
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(() => URL.revokeObjectURL(url), 1000);

      return true;
    } catch (error) {
      return false;
    }
  }

  document.getElementById('formPublicLayanan')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;

    try {
      const payload = await submit(form, 'ptsp/layanan');
      if (!payload) return;

      const r = payload.data?.receipt || {};
      if (receiptBody) {
        receiptBody.innerHTML = [
          ['Waktu', r.submitted_at],
          ['Nama', r.nama_lengkap],
          ['Kategori', r.kategori_pemohon],
          ['Layanan', r.jenis_layanan],
          ['Keterangan', r.tujuan_keterangan],
        ].map(([k,v]) =>
          `<div class="mb-1"><strong>${esc(k)}:</strong> ${esc(v || '-')}</div>`
        ).join('');
      }

      receipt?.classList.remove('d-none');
      show(
        payload.message || 'Pengajuan berhasil.',
        'success',
        ' <button id="btnPrintPtspReceipt" class="btn btn-sm btn-outline-success ms-2" type="button">Cetak Bukti 80mm</button>'
      );
      document.getElementById('btnPrintPtspReceipt')
        ?.addEventListener('click', printReceipt, {once:true});

      form.reset();

      if (autoPrint) {
        window.setTimeout(printReceipt, 250);
      } else if (autoDownloadPdf) {
        const pdf = payload.data?.receipt_pdf || null;
        const downloaded = downloadReceiptPdf(pdf);

        if (!downloaded) {
          const warning = payload.data?.receipt_pdf_error
            || 'PDF otomatis tidak dapat diunduh. Gunakan tombol Cetak Bukti 80mm.';
          show(
            payload.message || 'Pengajuan berhasil.',
            'warning',
            ` <span class="ms-1">${esc(warning)}</span> <button id="btnPrintPtspReceiptFallback" class="btn btn-sm btn-outline-warning ms-2" type="button">Cetak Manual</button>`
          );
          document.getElementById('btnPrintPtspReceiptFallback')
            ?.addEventListener('click', printReceipt, {once:true});
        }
      }
    } catch (error) {
      show(error.message || 'Pengajuan gagal.');
    }
  });

  document.getElementById('formPublicPolling')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    try {
      const payload = await submit(form, 'ptsp/polling');
      if (!payload) return;
      show(payload.message || 'Polling berhasil dikirim.', 'success');
      form.reset();
    } catch (error) {
      show(error.message || 'Polling gagal dikirim.');
    }
  });

  document.getElementById('formPublicPengaduan')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    if (!form.querySelector('input[name="klasifikasi[]"]:checked')) {
      show('Pilih minimal satu klasifikasi laporan.');
      return;
    }

    try {
      const payload = await submit(form, 'ptsp/pengaduan');
      if (!payload) return;
      show(payload.message || 'Laporan berhasil dikirim.', 'success');
      form.reset();
    } catch (error) {
      show(error.message || 'Laporan gagal dikirim.');
    }
  });
})();
