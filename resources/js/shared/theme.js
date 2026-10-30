export function initializeTheme() {
    const button = document.querySelector('.theme-toggle');
    const root = document.documentElement;

    function update() {
        const dark = root.dataset.bsTheme === 'dark';
        const label = dark ? 'Activar modo claro' : 'Activar modo oscuro';
        button?.setAttribute('aria-label', label);
        button?.setAttribute('title', label);
        const icon = button?.querySelector('i');
        if (icon) icon.className = dark ? 'bi bi-sun' : 'bi bi-moon-stars';
        document.querySelector('meta[name="theme-color"]')?.setAttribute('content', dark ? '#080c13' : '#f3f5f8');
        if (window.Chart) {
            const color = dark ? '#9aafca' : '#64748b';
            const border = dark ? '#243247' : '#e2e8f0';
            window.Chart.defaults.color = color;
            window.Chart.defaults.borderColor = border;
            window.Chart.defaults.datasets.doughnut.borderColor = dark ? '#101722' : '#ffffff';
            Object.values(window.Chart.instances).forEach((chart) => {
                chart.options.color = color;
                if (chart.options.plugins?.legend?.labels) chart.options.plugins.legend.labels.color = color;
                Object.values(chart.options.scales || {}).forEach((scale) => {
                    if (scale.ticks) scale.ticks.color = color;
                    if (scale.grid) scale.grid.color = border;
                    if (scale.border) scale.border.color = border;
                });
                chart.update('none');
            });
        }
    }

    button?.addEventListener('click', () => {
        root.dataset.bsTheme = root.dataset.bsTheme === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem('alfa-theme', root.dataset.bsTheme); } catch (_) {}
        update();
    });
    update();
}
