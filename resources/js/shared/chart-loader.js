import { loadScript } from '../lib/load-script';

export async function ensureChartJs() {
    await loadScript('https://cdn.jsdelivr.net/npm/chart.js');
    const dark = document.documentElement.dataset.bsTheme === 'dark';
    window.Chart.defaults.color = dark ? '#9aafca' : '#64748b';
    window.Chart.defaults.borderColor = dark ? '#243247' : '#e2e8f0';
    window.Chart.defaults.datasets.doughnut.borderColor = dark ? '#101722' : '#ffffff';
    return window.Chart;
}
