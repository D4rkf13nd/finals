class LazyLoader {
    constructor() {
        this.initializeDataTable();
        this.bindEvents();
    }

    initializeDataTable() {
        this.table = $('#dataTable').DataTable({
            processing: true,
            serverSide: false,
            pageLength: 10, // Set 10 rows per page
            ajax: {
                url: 'backend/db.php',
                type: 'GET',
                dataSrc: 'data'
            },
            columns: [
                { data: 'name', orderable: true },
                { data: 'age', orderable: true },
                { data: 'sex', orderable: true },
                { data: 'barangay', orderable: true },
                { data: 'address', orderable: true },
                { data: 'contact', orderable: true },
                { data: 'birthday', orderable: true },
                {
                    data: 'id',
                    orderable: false,
                    render: function(data) {
                        return `
                            <a href="backend/edit.php?id=${data}" class="btn btn-sm btn-warning">Edit</a>
                            <a href="backend/delete.php?id=${data}" class="btn btn-sm btn-danger">Delete</a>
                        `;
                    }
                }
            ],
            dom: '<"row"<"col-sm-12"tr>>p',
            language: {
                processing: '<div class="spinner-border text-primary" role="status"></div>'
            }
        });
    }

    bindEvents() {
        // Search functionality
        $('#tableSearch').on('keyup', (e) => {
            this.table.search(e.target.value).draw();
        });

        // Sort dropdown handler
        $('.sort-option').on('click', (e) => {
            const column = $(e.target).data('column');
            const currentOrder = $(e.target).data('order') || 'asc';
            const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
            
            this.table
                .order([parseInt(column), currentOrder])
                .draw();
            
            $(e.target).data('order', newOrder);
            
            // Update sort button text
            const columnName = $(e.target).text();
            $('#sortDropdown').text(`Sort by: ${columnName} (${currentOrder})`);
        });

        // Reset button
        $('#resetTable').on('click', () => {
            $('#tableSearch').val('');
            this.table
                .search('')
                .order([0, 'asc'])
                .draw();
            $('#sortDropdown').text('Sort by');
        });
    }
}

// Initialize on document ready
document.addEventListener('DOMContentLoaded', () => {
    new LazyLoader();
});