(() => {
  'use strict';

  const AUTO_ENTITY_NAMES = new Set([
    'id_siswa',
    'id_guru',
    'id_pegawai',
    'id_kelas',
    'id_kelas_tujuan',
    'id_mapel',
    'id_pelanggaran',
    'id_wali',
  ]);

  const instances = new WeakMap();

  class SearchableSelect {
    constructor(select) {
      this.select = select;
      this.remoteUrl = String(select.dataset.searchableRemote || '').trim();
      this.remoteContext = String(select.dataset.searchableContext || '').trim();
      this.minimumChars = Math.max(1, Number(select.dataset.searchableMinChars || 2));
      this.maxResults = Math.max(5, Number(select.dataset.searchableMaxResults || 30));
      this.placeholder = String(
        select.dataset.searchPlaceholder
        || select.querySelector('option[value=""]')?.textContent
        || 'Cari dan pilih...'
      ).trim();
      this.activeIndex = -1;
      this.items = [];
      this.timer = null;
      this.abortController = null;

      this.build();
      this.bind();
      this.syncFromSelect();
    }

    build() {
      this.wrapper = document.createElement('div');
      this.wrapper.className = 'sisfour-searchable';

      this.input = document.createElement('input');
      this.input.type = 'text';
      this.input.className = 'form-control sisfour-searchable__input';
      this.input.autocomplete = 'off';
      this.input.placeholder = this.placeholder;
      this.input.disabled = this.select.disabled;
      this.input.setAttribute('role', 'combobox');
      this.input.setAttribute('aria-expanded', 'false');

      this.dropdown = document.createElement('div');
      this.dropdown.className = 'sisfour-searchable__dropdown d-none';
      this.dropdown.setAttribute('role', 'listbox');

      this.select.parentNode.insertBefore(this.wrapper, this.select);
      this.wrapper.appendChild(this.select);
      this.wrapper.appendChild(this.input);
      this.wrapper.appendChild(this.dropdown);
      this.select.classList.add('sisfour-searchable__native');
    }

    bind() {
      this.input.addEventListener('focus', () => {
        if (this.remoteUrl) {
          if (this.input.value.trim().length >= this.minimumChars) {
            this.queueRemote();
          }
        } else {
          this.renderLocal(this.input.value);
        }
      });

      this.input.addEventListener('input', () => {
        if (this.remoteUrl) {
          this.queueRemote();
        } else {
          this.renderLocal(this.input.value);
        }
      });

      this.input.addEventListener('keydown', (event) => this.onKeyDown(event));
      this.select.addEventListener('change', () => this.syncFromSelect());

      document.addEventListener('click', (event) => {
        if (!this.wrapper.contains(event.target)) {
          this.close();
          this.syncFromSelect();
        }
      });

      this.observer = new MutationObserver(() => {
        if (!this.remoteUrl) {
          this.syncFromSelect();
        }
        this.input.disabled = this.select.disabled;
      });

      this.observer.observe(this.select, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['disabled'],
      });

      this.select.form?.addEventListener('reset', () => {
        window.setTimeout(() => this.syncFromSelect(), 0);
      });
    }

    onKeyDown(event) {
      if (this.dropdown.classList.contains('d-none')) {
        if (event.key === 'ArrowDown') {
          event.preventDefault();
          if (this.remoteUrl) {
            this.queueRemote();
          } else {
            this.renderLocal(this.input.value);
          }
        }
        return;
      }

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        this.moveActive(1);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        this.moveActive(-1);
      } else if (event.key === 'Enter') {
        if (this.activeIndex >= 0 && this.items[this.activeIndex]) {
          event.preventDefault();
          this.choose(this.items[this.activeIndex]);
        }
      } else if (event.key === 'Escape') {
        event.preventDefault();
        this.close();
        this.syncFromSelect();
      }
    }

    moveActive(delta) {
      if (!this.items.length) return;
      this.activeIndex = (this.activeIndex + delta + this.items.length) % this.items.length;
      this.dropdown.querySelectorAll('.sisfour-searchable__item').forEach((element, index) => {
        element.classList.toggle('is-active', index === this.activeIndex);
      });
    }

    renderLocal(query = '') {
      const normalized = String(query || '').trim().toLowerCase();
      const options = Array.from(this.select.options)
        .filter((option) => option.value !== '')
        .filter((option) => !option.disabled)
        .filter((option) => !normalized || option.textContent.toLowerCase().includes(normalized))
        .slice(0, this.maxResults)
        .map((option) => ({
          id: option.value,
          text: option.textContent.trim(),
        }));

      this.renderItems(options, 'Tidak ada pilihan yang cocok.');
    }

    queueRemote() {
      const query = this.input.value.trim();
      window.clearTimeout(this.timer);

      if (query.length < this.minimumChars) {
        this.renderItems([], `Ketik minimal ${this.minimumChars} karakter.`);
        return;
      }

      this.timer = window.setTimeout(() => this.loadRemote(query), 250);
    }

    async loadRemote(query) {
      if (this.abortController) {
        this.abortController.abort();
      }

      this.abortController = new AbortController();

      const url = new URL(this.remoteUrl, window.location.origin);
      url.searchParams.set('q', query);
      url.searchParams.set('limit', String(this.maxResults));
      if (this.remoteContext) {
        url.searchParams.set('context', this.remoteContext);
      }

      this.renderItems([], 'Mencari...');

      try {
        const response = await fetch(url.toString(), {
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          signal: this.abortController.signal,
        });
        const payload = await response.json();

        if (!response.ok || payload.status !== 'success') {
          this.renderItems([], payload.message || 'Pencarian gagal.');
          return;
        }

        this.renderItems(payload.data?.rows || [], 'Data tidak ditemukan.');
      } catch (error) {
        if (error.name !== 'AbortError') {
          this.renderItems([], 'Pencarian gagal. Silakan coba lagi.');
        }
      }
    }

    renderItems(items, emptyMessage) {
      this.items = Array.isArray(items) ? items : [];
      this.activeIndex = -1;
      this.dropdown.innerHTML = '';

      if (!this.items.length) {
        const empty = document.createElement('div');
        empty.className = 'sisfour-searchable__empty';
        empty.textContent = emptyMessage;
        this.dropdown.appendChild(empty);
      } else {
        this.items.forEach((item) => {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'sisfour-searchable__item';
          button.textContent = item.text;
          button.addEventListener('mousedown', (event) => {
            event.preventDefault();
            this.choose(item);
          });
          this.dropdown.appendChild(button);
        });
      }

      this.dropdown.classList.remove('d-none');
      this.input.setAttribute('aria-expanded', 'true');
    }

    choose(item) {
      const value = String(item.id ?? item.value ?? '');
      const text = String(item.text ?? '').trim();
      let option = Array.from(this.select.options).find((candidate) => candidate.value === value);

      if (!option) {
        option = new Option(text, value, true, true);
        option.dataset.remoteSelected = '1';
        this.select.add(option);
      }

      this.select.value = value;
      this.input.value = option.textContent.trim();
      this.select.dispatchEvent(new Event('change', { bubbles: true }));
      this.close();
    }

    setValue(value, text = '') {
      const normalized = String(value ?? '');
      let option = Array.from(this.select.options).find((candidate) => candidate.value === normalized);

      if (!option && normalized !== '') {
        option = new Option(text || normalized, normalized, true, true);
        option.dataset.remoteSelected = '1';
        this.select.add(option);
      }

      this.select.value = normalized;
      this.syncFromSelect();
    }

    syncFromSelect() {
      const selected = this.select.selectedOptions?.[0];
      this.input.disabled = this.select.disabled;
      this.input.value = selected && selected.value !== ''
        ? selected.textContent.trim()
        : '';
    }

    close() {
      this.dropdown.classList.add('d-none');
      this.input.setAttribute('aria-expanded', 'false');
      this.activeIndex = -1;
    }
  }

  function shouldEnhance(select) {
    if (!(select instanceof HTMLSelectElement)) return false;
    if (select.dataset.searchableOff === '1') return false;
    if (select.dataset.searchableSelect !== undefined) return true;
    if (select.dataset.searchableRemote) return true;

    const name = String(select.name || select.id || '').trim();
    if (!AUTO_ENTITY_NAMES.has(name)) return false;

    return select.options.length > 7;
  }

  function enhance(root = document) {
    root.querySelectorAll('select').forEach((select) => {
      if (!shouldEnhance(select) || instances.has(select)) return;
      const instance = new SearchableSelect(select);
      instances.set(select, instance);
    });
  }

  function sync(select) {
    instances.get(select)?.syncFromSelect();
  }

  function setValue(select, value, text = '') {
    if (!instances.has(select) && shouldEnhance(select)) {
      const instance = new SearchableSelect(select);
      instances.set(select, instance);
    }
    instances.get(select)?.setValue(value, text);
  }

  document.addEventListener('DOMContentLoaded', () => enhance());

  const domObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!(node instanceof Element)) return;
        if (node.matches('select')) {
          enhance(node.parentElement || document);
        } else {
          enhance(node);
        }
      });
    });
  });

  domObserver.observe(document.documentElement, { childList: true, subtree: true });

  window.SisfourSearchableSelect = {
    enhance,
    sync,
    setValue,
  };
})();
