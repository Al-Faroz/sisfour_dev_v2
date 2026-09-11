/**
 * SisisFour Pagination
 *
 * Reusable Bootstrap-compatible paginator for server-side datasets.
 * It does not fetch data by itself. A page module owns its filters/fetch logic
 * and calls render() with the total, limit, and offset returned by the server.
 */

(() => {
  'use strict';

  const DEFAULT_LIMITS = [25, 50, 100];

  const asPositiveInt = (value, fallback) => {
    const parsed = Number.parseInt(value, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : fallback;
  };

  const asNonNegativeInt = (value, fallback = 0) => {
    const parsed = Number.parseInt(value, 10);
    return Number.isFinite(parsed) && parsed >= 0 ? parsed : fallback;
  };

  const normalizeLimits = (limits) => {
    const source = Array.isArray(limits) && limits.length ? limits : DEFAULT_LIMITS;

    return [...new Set(source.map((value) => asPositiveInt(value, 0)).filter(Boolean))]
      .sort((a, b) => a - b);
  };

  const meta = (totalValue, limitValue, offsetValue) => {
    const total = asNonNegativeInt(totalValue, 0);
    const limit = asPositiveInt(limitValue, DEFAULT_LIMITS[0]);
    const totalPages = Math.max(1, Math.ceil(total / limit));
    const maxOffset = total > 0 ? (totalPages - 1) * limit : 0;
    const offset = Math.min(asNonNegativeInt(offsetValue, 0), maxOffset);
    const page = Math.floor(offset / limit) + 1;
    const start = total === 0 ? 0 : offset + 1;
    const end = Math.min(offset + limit, total);

    return {
      total,
      limit,
      offset,
      page,
      totalPages,
      start,
      end,
      hasPrevious: offset > 0,
      hasNext: offset + limit < total,
    };
  };

  const pageTokens = (page, totalPages) => {
    if (totalPages <= 7) {
      return Array.from({ length: totalPages }, (_, index) => index + 1);
    }

    const tokens = [1];
    const start = Math.max(2, page - 2);
    const end = Math.min(totalPages - 1, page + 2);

    if (start > 2) tokens.push('ellipsis-left');

    for (let current = start; current <= end; current += 1) {
      tokens.push(current);
    }

    if (end < totalPages - 1) tokens.push('ellipsis-right');

    tokens.push(totalPages);
    return tokens;
  };

  const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  class SisfourPagination {
    constructor(container, options = {}) {
      if (!(container instanceof Element)) {
        throw new TypeError('Container pagination tidak valid.');
      }

      this.container = container;
      this.options = {
        limits: normalizeLimits(options.limits),
        showLimit: options.showLimit !== false,
        label: String(options.label || 'data'),
        onChange: typeof options.onChange === 'function' ? options.onChange : () => {},
      };

      this.state = meta(0, this.options.limits[0], 0);
      this.disabled = false;
      this.handleClick = this.handleClick.bind(this);
      this.handleLimitChange = this.handleLimitChange.bind(this);

      this.container.addEventListener('click', this.handleClick);
      this.container.addEventListener('change', this.handleLimitChange);
    }

    setDisabled(disabled) {
      this.disabled = Boolean(disabled);
      this.render(this.state);
    }

    render(nextState = {}) {
      this.state = meta(
        nextState.total ?? this.state.total,
        nextState.limit ?? this.state.limit,
        nextState.offset ?? this.state.offset
      );

      const limits = this.options.limits.includes(this.state.limit)
        ? this.options.limits
        : normalizeLimits([...this.options.limits, this.state.limit]);

      const limitControl = this.options.showLimit
        ? `
          <label class="sisfour-pager__limit">
            <span class="text-muted small">Baris</span>
            <select class="form-select form-select-sm" data-pager-limit ${this.disabled ? 'disabled' : ''}>
              ${limits.map((value) => `
                <option value="${value}" ${value === this.state.limit ? 'selected' : ''}>${value}</option>
              `).join('')}
            </select>
          </label>
        `
        : '';

      const pages = pageTokens(this.state.page, this.state.totalPages)
        .map((token) => {
          if (typeof token !== 'number') {
            return `
              <li class="page-item disabled" aria-hidden="true">
                <span class="page-link">…</span>
              </li>
            `;
          }

          const active = token === this.state.page;
          const offset = (token - 1) * this.state.limit;

          return `
            <li class="page-item ${active ? 'active' : ''}">
              <button
                type="button"
                class="page-link"
                data-pager-offset="${offset}"
                ${this.disabled || active ? 'disabled' : ''}
                aria-label="Halaman ${token}"
                ${active ? 'aria-current="page"' : ''}
              >${token}</button>
            </li>
          `;
        })
        .join('');

      this.container.innerHTML = `
        <div class="sisfour-pager">
          <div class="sisfour-pager__summary">
            ${limitControl}
            <span class="small text-muted">
              Menampilkan <strong>${this.state.start}-${this.state.end}</strong>
              dari <strong>${this.state.total}</strong> ${escapeHtml(this.options.label)}
            </span>
          </div>

          <nav aria-label="Navigasi halaman">
            <ul class="pagination pagination-sm mb-0 flex-wrap">
              <li class="page-item ${this.state.hasPrevious ? '' : 'disabled'}">
                <button
                  type="button"
                  class="page-link"
                  data-pager-offset="${Math.max(0, this.state.offset - this.state.limit)}"
                  ${this.disabled || !this.state.hasPrevious ? 'disabled' : ''}
                  aria-label="Halaman sebelumnya"
                >
                  <i class="bx bx-chevron-left"></i>
                  <span class="d-none d-sm-inline">Sebelumnya</span>
                </button>
              </li>

              ${pages}

              <li class="page-item ${this.state.hasNext ? '' : 'disabled'}">
                <button
                  type="button"
                  class="page-link"
                  data-pager-offset="${this.state.offset + this.state.limit}"
                  ${this.disabled || !this.state.hasNext ? 'disabled' : ''}
                  aria-label="Halaman berikutnya"
                >
                  <span class="d-none d-sm-inline">Berikutnya</span>
                  <i class="bx bx-chevron-right"></i>
                </button>
              </li>
            </ul>
          </nav>
        </div>
      `;

      return { ...this.state };
    }

    handleClick(event) {
      const button = event.target.closest('[data-pager-offset]');
      if (!button || this.disabled || button.disabled) return;

      const offset = asNonNegativeInt(button.dataset.pagerOffset, 0);
      const next = meta(this.state.total, this.state.limit, offset);

      this.options.onChange({
        limit: next.limit,
        offset: next.offset,
        page: next.page,
      });
    }

    handleLimitChange(event) {
      const select = event.target.closest('[data-pager-limit]');
      if (!select || this.disabled) return;

      const limit = asPositiveInt(select.value, this.state.limit);
      const next = meta(this.state.total, limit, 0);

      this.options.onChange({
        limit: next.limit,
        offset: 0,
        page: 1,
      });
    }

    destroy() {
      this.container.removeEventListener('click', this.handleClick);
      this.container.removeEventListener('change', this.handleLimitChange);
      this.container.innerHTML = '';
    }
  }

  const mount = (anchor, options = {}) => {
    const anchorElement = typeof anchor === 'string'
      ? document.querySelector(anchor)
      : anchor;

    if (!(anchorElement instanceof Element)) {
      throw new TypeError('Anchor pagination tidak valid.');
    }

    const card = anchorElement.closest('.card');
    let footer = card?.querySelector('.card-footer') || null;

    if (!footer && card) {
      footer = document.createElement('div');
      footer.className = 'card-footer';
      card.appendChild(footer);
    }

    if (!footer) {
      footer = document.createElement('div');
      footer.className = 'mt-3';
      const table = anchorElement.closest('table');
      const wrapper = table?.parentElement || anchorElement.parentElement;
      wrapper?.insertAdjacentElement('afterend', footer);
    }

    footer.innerHTML = '';

    const container = document.createElement('div');
    container.id = String(options.id || '').trim()
      || `sisfourPager${Math.random().toString(36).slice(2)}`;

    footer.appendChild(container);

    return new SisfourPagination(container, options);
  };

  window.SisfourPagination = {
    create(container, options = {}) {
      return new SisfourPagination(container, options);
    },
    mount,
    meta,
  };
})();
