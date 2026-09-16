(() => {
  'use strict';

  const detailContent = document.getElementById('konselingDetailContent');
  const updateForm = document.getElementById('formKonselingUpdate');

  if (!detailContent || !updateForm) return;

  const text = (name) => String(updateForm.elements[name]?.value || '').trim();

  function ensureSummary() {
    let card = document.getElementById('konselingSavedProgress');
    if (card) return card;

    card = document.createElement('div');
    card.id = 'konselingSavedProgress';
    card.className = 'card border mb-4';
    card.innerHTML = `
      <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h6 class="mb-0">Perkembangan Tersimpan</h6>
        <span class="badge bg-label-secondary" id="konselingSavedStatus">Proses</span>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12">
            <small class="text-muted d-block mb-1">Uraian Masalah</small>
            <div id="konselingSavedUraian" class="text-break">Belum diisi.</div>
          </div>
          <div class="col-12">
            <small class="text-muted d-block mb-1">Hasil Pembahasan dan Kesepakatan</small>
            <div id="konselingSavedHasil" class="text-break">Belum diisi.</div>
          </div>
          <div class="col-md-6">
            <small class="text-muted d-block mb-1">Rencana Berikutnya</small>
            <div id="konselingSavedRencana">Belum ditentukan.</div>
          </div>
          <div class="col-md-6">
            <small class="text-muted d-block mb-1">Tanggal Pertemuan Berikutnya</small>
            <div id="konselingSavedTanggal">-</div>
          </div>
        </div>
      </div>
    `;

    updateForm.parentNode.insertBefore(card, updateForm);
    return card;
  }

  function refreshSummary() {
    const card = ensureSummary();
    if (!card) return;

    const status = text('status') || 'Proses';
    document.getElementById('konselingSavedUraian').textContent = text('uraian_masalah') || 'Belum diisi.';
    document.getElementById('konselingSavedHasil').textContent = text('hasil_kesepakatan') || 'Belum diisi.';
    document.getElementById('konselingSavedRencana').textContent = text('rencana_berikutnya') || 'Belum ditentukan.';
    document.getElementById('konselingSavedTanggal').textContent = text('tanggal_berikutnya') || '-';

    const badge = document.getElementById('konselingSavedStatus');
    if (badge) {
      badge.textContent = status;
      badge.className = `badge bg-label-${status === 'Selesai' ? 'success' : 'warning'}`;
    }

    // Urutan modal: detail -> perkembangan tersimpan -> form update/tindak lanjut.
    if (card.nextElementSibling !== updateForm) {
      updateForm.parentNode.insertBefore(card, updateForm);
    }
  }

  const observer = new MutationObserver(() => {
    if (!detailContent.classList.contains('d-none')) {
      window.requestAnimationFrame(refreshSummary);
    }
  });

  observer.observe(detailContent, {
    attributes: true,
    attributeFilter: ['class'],
  });

  updateForm.addEventListener('submit', () => {
    window.setTimeout(refreshSummary, 500);
  });
})();
