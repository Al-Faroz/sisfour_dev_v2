/**
 * SisisFour Main UI
 *
 * Global layout bootstrap for the Purple Admin / Sneat-based vertical menu.
 * Desktop sidebar state is owned by the user and persisted in localStorage.
 */

'use strict';

let menu;
let animate;

const SISFOUR_SIDEBAR_STORAGE_KEY = 'sisfour.sidebar.desktop';
const SISFOUR_SIDEBAR_EXPANDED = 'expanded';
const SISFOUR_SIDEBAR_COMPACT = 'compact';

function sisfourReadSidebarState() {
  try {
    const value = window.localStorage.getItem(SISFOUR_SIDEBAR_STORAGE_KEY);

    if (value === SISFOUR_SIDEBAR_COMPACT || value === SISFOUR_SIDEBAR_EXPANDED) {
      return value;
    }
  } catch (_) {
    // Storage may be unavailable in restricted/private browser contexts.
  }

  return SISFOUR_SIDEBAR_EXPANDED;
}

function sisfourWriteSidebarState(value) {
  try {
    window.localStorage.setItem(SISFOUR_SIDEBAR_STORAGE_KEY, value);
  } catch (_) {
    // A failed preference write must never block navigation.
  }
}

function sisfourIsDesktop() {
  return Boolean(window.Helpers) && !window.Helpers.isSmallScreen();
}

function sisfourIsSidebarCollapsed() {
  // Purple Admin/Sneat CSS memakai class ini sebagai kontrak desktop.
  // Jangan bergantung pada Helpers.isCollapsed() karena helper vendor pada
  // build yang dipakai project ini tidak mengelola state collapsed desktop
  // secara konsisten.
  return document.documentElement.classList.contains('layout-menu-collapsed');
}

function sisfourSyncSidebarToggle() {
  const collapsed = sisfourIsDesktop() && sisfourIsSidebarCollapsed();

  document.querySelectorAll('.layout-menu-toggle').forEach((toggle) => {
    if (!sisfourIsDesktop()) {
      toggle.setAttribute('aria-label', 'Buka atau tutup menu navigasi');
      toggle.removeAttribute('aria-expanded');
      return;
    }

    toggle.setAttribute(
      'aria-label',
      collapsed ? 'Perluas sidebar' : 'Minimalkan sidebar'
    );
    toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');

    const icon = toggle.querySelector('[data-sidebar-toggle-icon]');
    if (icon) {
      icon.classList.toggle('bx-chevron-right', collapsed);
      icon.classList.toggle('bx-chevron-left', !collapsed);
    }
  });

  document.documentElement.dataset.sidebarDesktop = collapsed
    ? SISFOUR_SIDEBAR_COMPACT
    : SISFOUR_SIDEBAR_EXPANDED;
}

function sisfourSetDesktopSidebar(state, shouldAnimate = false, persist = false) {
  if (!sisfourIsDesktop()) {
    return;
  }

  const collapsed = state === SISFOUR_SIDEBAR_COMPACT;
  const root = document.documentElement;

  // FIX1: toggle desktop langsung pada class kontrak layout.
  // core.css Purple Admin/Sneat sudah menyediakan seluruh rule
  // .layout-menu-collapsed untuk lebar sidebar dan padding layout-page.
  // Helpers.setCollapsed() pada helper vendor project ini efektif untuk
  // small-screen overlay, tetapi tidak mengubah class desktop yang dibutuhkan.
  if (!shouldAnimate) {
    root.classList.add('layout-no-transition');
  }

  root.classList.toggle('layout-menu-collapsed', collapsed);
  root.classList.remove('layout-menu-hover');

  if (persist) {
    sisfourWriteSidebarState(
      collapsed ? SISFOUR_SIDEBAR_COMPACT : SISFOUR_SIDEBAR_EXPANDED
    );
  }

  const settle = () => {
    root.classList.remove('layout-no-transition');
    sisfourSyncSidebarToggle();

    // Beri tahu komponen yang sensitif terhadap perubahan lebar container
    // (chart/table) tanpa memanggil helper private vendor.
    window.dispatchEvent(new Event('resize'));
  };

  window.setTimeout(settle, shouldAnimate ? 320 : 0);
}

function sisfourRestoreDesktopSidebar() {
  if (!sisfourIsDesktop()) {
    return;
  }

  sisfourSetDesktopSidebar(sisfourReadSidebarState(), false, false);
}

document.addEventListener('DOMContentLoaded', function () {
  if (navigator.userAgent.match(/iPhone|iPad|iPod/i)) {
    document.body.classList.add('ios');
  }
});

(function () {
  const layoutMenuEl = document.querySelectorAll('#layout-menu');

  layoutMenuEl.forEach(function (element) {
    menu = new Menu(element, {
      orientation: 'vertical',
      closeChildren: false,
    });

    window.Helpers.scrollToActive((animate = false));
    window.Helpers.mainMenu = menu;
  });

  const menuToggler = document.querySelectorAll('.layout-menu-toggle');

  menuToggler.forEach((item) => {
    item.addEventListener('click', (event) => {
      event.preventDefault();

      if (!sisfourIsDesktop()) {
        window.Helpers.toggleCollapsed();
        return;
      }

      const nextState = sisfourIsSidebarCollapsed()
        ? SISFOUR_SIDEBAR_EXPANDED
        : SISFOUR_SIDEBAR_COMPACT;

      sisfourSetDesktopSidebar(nextState, true, true);
    });
  });

  const delay = function (elem, callback) {
    let timeout = null;

    elem.onmouseenter = function () {
      timeout = setTimeout(callback, Helpers.isSmallScreen() ? 0 : 300);
    };

    elem.onmouseleave = function () {
      const desktopToggle = document.querySelector(
        '#layout-menu .layout-menu-toggle.d-xl-block'
      );

      desktopToggle?.classList.remove('d-block');
      clearTimeout(timeout);
    };
  };

  const layoutMenu = document.getElementById('layout-menu');

  if (layoutMenu) {
    delay(layoutMenu, function () {
      if (!Helpers.isSmallScreen()) {
        document
          .querySelector('#layout-menu .layout-menu-toggle.d-xl-block')
          ?.classList.add('d-block');
      }
    });
  }

  const menuInnerContainer = document.getElementsByClassName('menu-inner');
  const menuInnerShadow = document.getElementsByClassName('menu-inner-shadow')[0];

  if (menuInnerContainer.length > 0 && menuInnerShadow) {
    menuInnerContainer[0].addEventListener('ps-scroll-y', function () {
      const thumb = this.querySelector('.ps__thumb-y');
      menuInnerShadow.style.display = thumb?.offsetTop ? 'block' : 'none';
    });
  }

  const tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );

  tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  const accordionActiveFunction = function (event) {
    const item = event.target.closest('.accordion-item');
    if (!item) return;

    if (event.type === 'show.bs.collapse') {
      item.classList.add('active');
    } else {
      item.classList.remove('active');
    }
  };

  const accordionTriggerList = [].slice.call(document.querySelectorAll('.accordion'));

  accordionTriggerList.forEach(function (accordionTriggerEl) {
    accordionTriggerEl.addEventListener('show.bs.collapse', accordionActiveFunction);
    accordionTriggerEl.addEventListener('hide.bs.collapse', accordionActiveFunction);
  });

  window.Helpers.setAutoUpdate(true);
  window.Helpers.initPasswordToggle();
  window.Helpers.initSpeechToText();

  sisfourRestoreDesktopSidebar();
  sisfourSyncSidebarToggle();

  window.addEventListener('layouttoggle', sisfourSyncSidebarToggle);

  let wasDesktop = sisfourIsDesktop();
  let resizeTimer = null;

  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);

    resizeTimer = setTimeout(() => {
      const isDesktop = sisfourIsDesktop();

      if (isDesktop && !wasDesktop) {
        sisfourRestoreDesktopSidebar();
      } else if (!isDesktop && wasDesktop) {
        // State desktop disimpan di localStorage, tetapi class compact jangan
        // dibiarkan memengaruhi layout overlay mobile.
        document.documentElement.classList.remove('layout-menu-collapsed');
        document.documentElement.classList.remove('layout-menu-hover');
      }

      wasDesktop = isDesktop;
      sisfourSyncSidebarToggle();
    }, 250);
  });
})();

function isMacOS() {
  return /Mac|iPod|iPhone|iPad/.test(navigator.userAgent);
}
