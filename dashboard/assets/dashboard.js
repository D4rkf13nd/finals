let barangayChart;
let genderChart;

function initializeCharts() {
    // Initialize Barangay Chart
    const barangayCtx = document.getElementById('barangayChart').getContext('2d');
    barangayChart = new Chart(barangayCtx, {
        type: 'bar',
        data: {
            labels: window.barangayDataPoints.map(point => point.label),
            datasets: [{
                label: 'Population by Barangay',
                data: window.barangayDataPoints.map(point => point.y),
                backgroundColor: [
                    'rgba(17, 2, 146, 0.8)',    // Dark blue
                    'rgba(255, 0, 55, 0.8)',     // Red
                    'rgba(165, 120, 255, 0.8)',  // Purple
                    'rgba(0, 168, 255, 0.8)',    // Light blue
                    'rgba(255, 127, 0, 0.8)',    // Orange
                    'rgba(0, 200, 83, 0.8)',     // Green
                    'rgba(238, 9, 121, 0.8)',    // Pink
                    'rgba(255, 193, 7, 0.8)'     // Yellow
                ],
                borderColor: [
                    'rgba(17, 2, 146, 1)',
                    'rgba(255, 0, 55, 1)',
                    'rgba(165, 120, 255, 1)',
                    'rgba(0, 168, 255, 1)',
                    'rgba(255, 127, 0, 1)',
                    'rgba(0, 200, 83, 1)',
                    'rgba(238, 9, 121, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Initialize Gender Chart
    const genderCtx = document.getElementById('genderChart').getContext('2d');
    genderChart = new Chart(genderCtx, {
        type: 'doughnut',
        data: {
            labels: window.genderDataPoints.map(point => point.label),
            datasets: [{
                data: window.genderDataPoints.map(point => point.y),
                backgroundColor: [
                    'rgba(17, 2, 146, 1)',
                    'rgba(255, 0, 55, 1)',
                    'rgba(165, 120, 255, 1)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Chart type toggle handlers
document.getElementById('barBtn').addEventListener('click', () => {
    if (barangayChart) {
        barangayChart.destroy();
    }
    barangayChart = new Chart(document.getElementById('barangayChart').getContext('2d'), {
        type: 'bar',
        data: barangayChart.config.data,
        options: barangayChart.config.options
    });
});

document.getElementById('lineBtn').addEventListener('click', () => {
    if (barangayChart) {
        barangayChart.destroy();
    }
    barangayChart = new Chart(document.getElementById('barangayChart').getContext('2d'), {
        type: 'line',
        data: barangayChart.config.data,
        options: barangayChart.config.options
    });
});

document.getElementById('pieBtn').addEventListener('click', () => {
    if (barangayChart) {
        barangayChart.destroy();
    }
    barangayChart = new Chart(document.getElementById('barangayChart').getContext('2d'), {
        type: 'pie',
        data: barangayChart.config.data,
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
});

// Export button handler
document.getElementById('exportBtn').addEventListener('click', () => {
    window.location.href = 'backend/export.php';
});

// Sorting functionality
document.querySelectorAll('.sort-option').forEach(option => {
    option.addEventListener('click', (e) => {
        e.preventDefault();
        const column = e.target.dataset.column;
        const order = e.target.dataset.order || 'asc';
        
        // Update dropdown text
        document.getElementById('sortDropdown').textContent = e.target.textContent;
        
        // Sort table
        sortTable(column, order);
    });
});

function sortTable(column, order) {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const columnMap = {
        'name': 1,
        'age': 2,
        'barangay': 4
    };
    
    const columnIndex = columnMap[column];
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        if (column === 'age') {
            return order === 'asc' ? 
                parseInt(aValue) - parseInt(bValue) : 
                parseInt(bValue) - parseInt(aValue);
        }
        
        return order === 'asc' ? 
            aValue.localeCompare(bValue) : 
            bValue.localeCompare(aValue);
    });
    
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
}

function initializeDataTable() {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const rowsPerPage = 10;
    let currentPage = 1;

    function showPage(page) {
        const start = (page - 1) * rowsPerPage;
        const end = start + rowsPerPage;
        
        rows.forEach((row, index) => {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });
    }

    function createPagination() {
        const totalPages = Math.ceil(rows.length / rowsPerPage);
        const paginationContainer = document.createElement('div');
        paginationContainer.className = 'pagination-container mt-3 d-flex justify-content-center';
        
        const pagination = document.createElement('ul');
        pagination.className = 'pagination';

        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = 'page-item';
        const prevLink = document.createElement('a');
        prevLink.className = 'page-link';
        prevLink.href = '#';
        prevLink.textContent = 'Previous';
        prevLi.appendChild(prevLink);
        pagination.appendChild(prevLi);

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = 'page-item';
            const link = document.createElement('a');
            link.className = 'page-link';
            link.href = '#';
            link.textContent = i;
            li.appendChild(link);
            pagination.appendChild(li);

            link.addEventListener('click', (e) => {
                e.preventDefault();
                currentPage = i;
                showPage(currentPage);
                updatePaginationState();
            });
        }

        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = 'page-item';
        const nextLink = document.createElement('a');
        nextLink.className = 'page-link';
        nextLink.href = '#';
        nextLink.textContent = 'Next';
        nextLi.appendChild(nextLink);
        pagination.appendChild(nextLi);

        prevLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                showPage(currentPage);
                updatePaginationState();
            }
        });

        nextLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage < totalPages) {
                currentPage++;
                showPage(currentPage);
                updatePaginationState();
            }
        });

        function updatePaginationState() {
            // Update active state
            pagination.querySelectorAll('.page-item').forEach((item, index) => {
                if (index === 0) {
                    item.classList.toggle('disabled', currentPage === 1);
                } else if (index === totalPages + 1) {
                    item.classList.toggle('disabled', currentPage === totalPages);
                } else {
                    item.classList.toggle('active', index === currentPage);
                }
            });
        }

        paginationContainer.appendChild(pagination);
        table.parentNode.insertBefore(paginationContainer, table.nextSibling);
        updatePaginationState();
    }

    // Initialize pagination
    showPage(1);
    createPagination();
}

function optimizeTableLayout() {
    const tableContainer = document.querySelector('.table-responsive');
    const table = document.querySelector('table');
    
    // Add classes for better table responsiveness
    table.classList.add('table-responsive-stack');
    
    // Add custom styles
    const style = document.createElement('style');
    style.textContent = `
        .table-responsive {
            overflow-x: hidden !important;
        }
        
        .table-responsive-stack td,
        .table-responsive-stack th {
            max-width: 200px;
            white-space: normal;
            word-wrap: break-word;
        }
        
        @media screen and (max-width: 768px) {
            .table-responsive-stack tr {
                display: flex;
                flex-direction: column;
                border: 1px solid #ccc;
                margin-bottom: 1rem;
            }
            
            .table-responsive-stack td {
                border: none;
                position: relative;
                padding-left: 130px;
            }
            
            .table-responsive-stack td:before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 120px;
                font-weight: bold;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Add data-label attributes to td elements
    const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent);
    table.querySelectorAll('tbody tr').forEach(row => {
        row.querySelectorAll('td').forEach((td, index) => {
            td.setAttribute('data-label', headers[index]);
        });
    });
}

// Update the document ready handler
document.addEventListener('DOMContentLoaded', () => {
    initializeCharts();
    initializeDataTable();
    optimizeTableLayout(); // Add this line
});