/**
 * Agenda Kelas - Dashboard JavaScript
 * File: assets/js/dashboard.js
 * Khusus untuk halaman dashboard
 */

// ============================================
// DASHBOARD STATISTICS ANIMATION
// ============================================
function animateNumbers() {
    const statNumbers = document.querySelectorAll('.stat-value');
    
    statNumbers.forEach(el => {
        const target = parseInt(el.getAttribute('data-target'));
        if (!target || isNaN(target)) return;
        
        let current = 0;
        const increment = target / 50;
        const timer = setInterval(() => {
            current += increment;
            if (current >= target) {
                el.textContent = target;
                clearInterval(timer);
            } else {
                el.textContent = Math.floor(current);
            }
        }, 20);
    });
}

// ============================================
// CHARTS INITIALIZATION
// ============================================
function initAttendanceChart(canvasId, labels, data) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Jumlah Siswa',
                data: data,
                backgroundColor: '#FFD65A',
                borderColor: '#e6c04a',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#e8ecf2'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

function initPieChart(canvasId, labels, data, colors) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors || ['#FFD65A', '#f59e0b', '#10b981', '#3b82f6', '#dc2626'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// ============================================
// DATE RANGE PICKER
// ============================================
function initDateRangePicker(startId, endId) {
    const startInput = document.getElementById(startId);
    const endInput = document.getElementById(endId);
    
    if (startInput && endInput) {
        startInput.addEventListener('change', function() {
            endInput.min = this.value;
        });
        
        endInput.addEventListener('change', function() {
            startInput.max = this.value;
        });
    }
}

// ============================================
// EXPORT TABLE TO EXCEL
// ============================================
function exportToExcel(tableId, filename = 'export.xlsx') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.table_to_sheet(table, { raw: true });
    XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
    XLSX.writeFile(wb, filename);
}

// ============================================
// PRINT TABLE
// ============================================
function printTable(tableId, title = 'Laporan') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>${title}</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body { padding: 20px; }
                h2 { color: #FFD65A; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background: #FFD65A; }
            </style>
        </head>
        <body>
            <h2>${title}</h2>
            ${table.outerHTML}
            <script>window.print();<\/script>
        </body>
        </html>
    `);
    printWindow.document.close();
}

// ============================================
// SEARCH TABLE
// ============================================
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) return;
    
    input.addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const rows = table.getElementsByTagName('tr');
        
        for (let i = 1; i < rows.length; i++) {
            const row = rows[i];
            let found = false;
            const cells = row.getElementsByTagName('td');
            
            for (let j = 0; j < cells.length; j++) {
                const cellText = cells[j].textContent.toLowerCase();
                if (cellText.includes(searchText)) {
                    found = true;
                    break;
                }
            }
            
            row.style.display = found ? '' : 'none';
        }
    });
}

// ============================================
// PAGINATION
// ============================================
function initPagination(tableId, rowsPerPage = 10) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.getElementsByTagName('tbody')[0]?.getElementsByTagName('tr');
    if (!rows) return;
    
    const rowCount = rows.length;
    const pageCount = Math.ceil(rowCount / rowsPerPage);
    let currentPage = 1;
    
    function showPage(page) {
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        for (let i = 0; i < rowCount; i++) {
            rows[i].style.display = (i >= start && i < end) ? '' : 'none';
        }
    }
    
    function createPaginationControls() {
        const controls = document.createElement('div');
        controls.className = 'd-flex justify-content-center mt-3 gap-2';
        controls.id = 'paginationControls';
        
        for (let i = 1; i <= pageCount; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            btn.className = 'btn btn-sm ' + (i === currentPage ? 'btn-primary-custom' : 'btn-outline-custom');
            btn.onclick = () => {
                currentPage = i;
                showPage(currentPage);
                updateButtons();
            };
            controls.appendChild(btn);
        }
        
        table.parentNode.insertBefore(controls, table.nextSibling);
    }
    
    function updateButtons() {
        const btns = document.querySelectorAll('#paginationControls button');
        btns.forEach((btn, idx) => {
            if (idx + 1 === currentPage) {
                btn.classList.add('btn-primary-custom');
                btn.classList.remove('btn-outline-custom');
            } else {
                btn.classList.add('btn-outline-custom');
                btn.classList.remove('btn-primary-custom');
            }
        });
    }
    
    if (rowCount > rowsPerPage) {
        showPage(1);
        createPaginationControls();
    }
}