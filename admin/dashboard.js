const menuButton = document.getElementById('menu-button');
const sidebar = document.getElementById('sidebar');
menuButton?.addEventListener('click', () => sidebar.classList.toggle('open'));

const dialog = document.getElementById('product-dialog');
document.getElementById('add-product')?.addEventListener('click', () => dialog?.showModal());
document.getElementById('close-product')?.addEventListener('click', () => dialog?.close());

const categoryDialog = document.getElementById('category-dialog');
document.getElementById('add-category')?.addEventListener('click', () => categoryDialog?.showModal());
document.getElementById('close-category')?.addEventListener('click', () => categoryDialog?.close());

document.querySelectorAll('.order-actions').forEach(form => {
    form.addEventListener('submit', event => {
        if (event.submitter?.value === 'Cancelado' && !window.confirm('Cancelar este pedido?')) {
            event.preventDefault();
        }
    });
});

const search = document.getElementById('order-search');
const filterButtons = document.querySelectorAll('#order-filters button');
const rows = document.querySelectorAll('#order-rows tr');
const empty = document.getElementById('empty-orders');
const normalize = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
let filter = new URLSearchParams(location.search).get('status') === 'Preparando' ? 'Preparando' : '';

function filterOrders() {
    const query = normalize(search.value.trim());
    let matches = 0;
    rows.forEach(row => {
        row.hidden = (filter && row.dataset.status !== filter) || !normalize(row.dataset.search).includes(query);
        if (!row.hidden) matches++;
    });
    empty.hidden = matches > 0;
    filterButtons.forEach(button => button.classList.toggle('active', button.dataset.filter === filter));
}
if (search && empty) {
    search.addEventListener('input', filterOrders);
    filterButtons.forEach(button => button.addEventListener('click', () => {
        filter = button.dataset.filter;
        filterOrders();
    }));
    filterOrders();
}

const sales = document.getElementById('sales');
window.addEventListener('load', () => {
    if (!sales || !window.Chart) return;
    const series = JSON.parse(sales.dataset.series);
    const money = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' });
    sales.hidden = false;
    document.getElementById('fallback-chart').hidden = true;
    new Chart(sales, {
        type: 'bar',
        data: {
            labels: series.map(day => day.label),
            datasets: [{
                data: series.map(day => day.total),
                backgroundColor: series.map((_, index) => index === series.length - 1 ? '#ffc107' : '#9350c7'),
                borderRadius: 5,
                maxBarThickness: 44
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: item => money.format(item.raw) } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#a0a0b0' } },
                y: { beginAtZero: true, grid: { color: '#33333f' }, ticks: { color: '#a0a0b0', callback: value => money.format(value) } }
            }
        }
    });
}, { once: true });
