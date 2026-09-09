(() => {
  'use strict';

  const app = document.getElementById('signageApp');
  if (!app) return;

  const dataUrl = app.dataset.dataUrl;
  const refreshMinutes = Math.max(1, Number(app.dataset.refreshMinutes || 20));
  const refreshMs = refreshMinutes * 60 * 1000;
  const status = document.getElementById('signageStatus');

  const esc = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };

  function updateClock() {
    const now = new Date();
    document.getElementById('signageClock').textContent = now.toLocaleTimeString('id-ID', {
      hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
    });
    document.getElementById('signageDate').textContent = now.toLocaleDateString('id-ID', {
      weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
    });
  }

  function formatDateTime(value) {
    if (!value) return '-';
    const normalized = String(value).replace(' ', 'T');
    const date = new Date(normalized);
    if (Number.isNaN(date.getTime())) return value;
    return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false });
  }

  function renderEws(rows) {
    const body = document.getElementById('ewsBody');
    document.getElementById('ewsCount').textContent = String(rows.length);
    body.innerHTML = rows.length ? rows.map((row, index) => `
      <tr>
        <td>${index + 1}</td>
        <td>${esc(row.nama_siswa)}</td>
        <td>${esc(row.nama_kelas || '-')}</td>
        <td class="col-center">${Number(row.total_alpha || 0)}</td>
      </tr>
    `).join('') : `
      <tr><td colspan="4" class="empty ok">✓ Tidak ada siswa yang memenuhi kriteria EWS Alpha.</td></tr>
    `;
    document.getElementById('ewsScroll').scrollTop = 0;
  }

  function renderKelas(rows) {
    const body = document.getElementById('kelasBody');
    document.getElementById('kelasCount').textContent = String(rows.length);
    body.innerHTML = rows.length ? rows.map((row, index) => `
      <tr>
        <td>${index + 1}</td>
        <td>${esc(row.nama_kelas)}</td>
        <td>${esc(row.nama_wali || '-')}</td>
      </tr>
    `).join('') : `
      <tr><td colspan="3" class="empty ok">✓ Seluruh kelas sudah melakukan Presensi Sesi Awal.</td></tr>
    `;
    document.getElementById('kelasScroll').scrollTop = 0;
  }

  function renderGuru(rows) {
    const body = document.getElementById('guruBody');
    document.getElementById('guruCount').textContent = String(rows.length);
    body.innerHTML = rows.length ? rows.map((row, index) => `
      <tr>
        <td>${index + 1}</td>
        <td>${esc(row.nama_guru)}</td>
        <td>${esc(row.nama_kelas)}</td>
        <td>${esc(row.nama_mapel)}</td>
        <td>${esc(String(row.jam_mulai || '').slice(0, 5))}–${esc(String(row.jam_selesai || '').slice(0, 5))}</td>
      </tr>
    `).join('') : `
      <tr><td colspan="5" class="empty ok">✓ Tidak ada Presensi Mengajar yang terlewat.</td></tr>
    `;
    document.getElementById('guruScroll').scrollTop = 0;
  }

  async function loadData() {
    status.textContent = 'Memperbarui data...';

    try {
      const response = await fetch(`${dataUrl}?_=${Date.now()}`, {
        cache: 'no-store',
        headers: { Accept: 'application/json' },
      });
      const payload = await response.json();

      if (!response.ok || payload.status !== 'success') {
        throw new Error(payload.message || 'Data gagal dimuat.');
      }

      const data = payload.data || {};
      document.getElementById('signageTahun').textContent = data.tahun_ajaran || 'Tahun Ajaran aktif belum tersedia';
      document.getElementById('signageLastUpdate').textContent = formatDateTime(data.generated_at);
      renderEws(data.ews_siswa || []);
      renderKelas(data.kelas_belum_presensi || []);
      renderGuru(data.guru_belum_presensi || []);
      status.textContent = `Data aktif • refresh ${refreshMinutes} menit`;
    } catch (error) {
      status.textContent = `Gagal memperbarui: ${error.message || 'error'}`;
    }
  }

  function autoScroll(container) {
    let last = performance.now();
    let pauseUntil = 0;

    function frame(now) {
      const delta = now - last;
      last = now;

      if (container.scrollHeight > container.clientHeight + 4) {
        if (now >= pauseUntil) {
          container.scrollTop += delta * 0.018;

          if (container.scrollTop + container.clientHeight >= container.scrollHeight - 2) {
            container.scrollTop = 0;
            pauseUntil = now + 1800;
          }
        }
      } else {
        container.scrollTop = 0;
      }

      requestAnimationFrame(frame);
    }

    requestAnimationFrame(frame);
  }

  updateClock();
  window.setInterval(updateClock, 1000);
  document.querySelectorAll('.signage-scroll').forEach(autoScroll);
  loadData();
  window.setInterval(loadData, refreshMs);
})();
