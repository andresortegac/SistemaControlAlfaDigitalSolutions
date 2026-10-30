import './bootstrap';
import { initializeTheme } from './shared/theme';

import { ensureBootstrap } from './shared/bootstrap';
import { initializeClipboard } from './shared/clipboard';
import { initializeConfirmations } from './shared/confirmations';
import { initializeDashboardPage } from './pages/dashboard/index';
import { initializeLicenciaCreatePage } from './pages/licencias/create';
import { initializeReportesPage } from './pages/reportes/index';

async function initializeApplication() {
    initializeTheme();
    const toggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    function setNavigation(open) {
        sidebar?.classList.toggle('show', open);
        toggle?.setAttribute('aria-expanded', String(open));
        if (backdrop) backdrop.hidden = !open;
        document.body.classList.toggle('navigation-open', open);
    }
    toggle?.addEventListener('click', () => setNavigation(toggle.getAttribute('aria-expanded') !== 'true'));
    backdrop?.addEventListener('click', () => setNavigation(false));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && toggle?.getAttribute('aria-expanded') === 'true') {
            setNavigation(false);
            toggle.focus();
        }
    });
    window.matchMedia('(min-width: 992px)').addEventListener('change', (event) => {
        if (event.matches) setNavigation(false);
    });
    await ensureBootstrap();
    initializeConfirmations();
    initializeClipboard();
    initializeLicenciaCreatePage();
    await initializeDashboardPage();
    await initializeReportesPage();
}

initializeApplication().catch((error) => {
    console.error('Error inicializando la aplicación.', error);
});
