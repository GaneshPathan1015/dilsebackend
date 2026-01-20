@extends('admin.layouts.master')
@section('main_section')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Tax Rate Management</h5>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaxRateModal">
                <i class="fas fa-plus me-2"></i>Add Tax Rate
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="taxRatesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Rate (%)</th>
                            <th>HSN Code</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createTaxRateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Tax Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="createTaxRateForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tax Code *</label>
                        <input type="text" name="code" class="form-control" placeholder="e.g., GST_DIAMOND" required>
                        <div class="error-message" id="error_code"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tax Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Diamond GST" required>
                        <div class="error-message" id="error_name"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GST Rate (%) *</label>
                        <input type="number" step="0.01" name="rate" class="form-control" placeholder="e.g., 0.25" min="0" max="999.99" required>
                        <div class="error-message" id="error_rate"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">HSN Code</label>
                        <input type="text" name="hsn_code" class="form-control" placeholder="e.g., 7102">
                        <div class="error-message" id="error_hsn_code"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div class="error-message" id="error_status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editTaxRateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Tax Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editTaxRateForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tax Code *</label>
                        <input type="text" name="code" id="edit_code" class="form-control" required readonly>
                        <div class="error-message" id="error_edit_code"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tax Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                        <div class="error-message" id="error_edit_name"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">GST Rate (%) *</label>
                        <input type="number" step="0.01" name="rate" id="edit_rate" class="form-control" min="0" max="999.99" required>
                        <div class="error-message" id="error_edit_rate"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">HSN Code</label>
                        <input type="text" name="hsn_code" id="edit_hsn_code" class="form-control">
                        <div class="error-message" id="error_edit_hsn_code"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status *</label>
                        <select name="status" id="edit_status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <div class="error-message" id="error_edit_status"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteTaxRateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this tax rate? This action cannot be undone.</p>
                <input type="hidden" id="delete_id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<style>
    .error-message {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }
</style>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#taxRatesTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('admin.tax-rates.data') }}",
        columns: [
            { data: 'id', name: 'id' },
            { data: 'code', name: 'code' },
            { data: 'name', name: 'name' },
            { 
                data: 'rate', 
                name: 'rate',
                render: function(data) {
                    return data + '%';
                }
            },
            { data: 'hsn_code', name: 'hsn_code' },
            { 
                data: 'status', 
                name: 'status',
                render: function(data) {
                    return data === 'active' 
                        ? '<span class="badge bg-success">Active</span>'
                        : '<span class="badge bg-danger">Inactive</span>';
                }
            },
            { 
                data: 'created_at', 
                name: 'created_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString('en-GB');
                }
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary edit-btn" data-id="${row.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-btn" data-id="${row.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']]
    });

    // Clear form errors
    function clearErrors(formId) {
        $(`#${formId} .error-message`).text('');
        $(`#${formId} input`).removeClass('is-invalid');
        $(`#${formId} select`).removeClass('is-invalid');
    }

    // Show errors
    function showErrors(errors) {
        $.each(errors, function(field, messages) {
            const errorElement = $(`#error_${field}`);
            const inputElement = $(`[name="${field}"]`);
            
            if (errorElement.length) {
                errorElement.text(messages[0]);
            }
            
            if (inputElement.length) {
                inputElement.addClass('is-invalid');
            }
        });
    }

    // Create Tax Rate
    $('#createTaxRateForm').submit(function(e) {
        e.preventDefault();
        
        clearErrors('createTaxRateForm');
        
        $.ajax({
            url: "{{ route('admin.tax-rates.store') }}",
            type: "POST",
            data: $(this).serialize(),
            success: function(response) {
                $('#createTaxRateModal').modal('hide');
                $('#createTaxRateForm')[0].reset();
                table.ajax.reload();
                toastr.success(response.message || 'Tax rate created successfully');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    toastr.error('An error occurred. Please try again.');
                }
            }
        });
    });

    // Edit Tax Rate - Fetch data
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: "{{ route('admin.tax-rates.edit', ':id') }}".replace(':id', id),
            type: "GET",
            success: function(response) {
                $('#edit_id').val(response.id);
                $('#edit_code').val(response.code);
                $('#edit_name').val(response.name);
                $('#edit_rate').val(response.rate);
                $('#edit_hsn_code').val(response.hsn_code);
                $('#edit_status').val(response.status);
                
                $('#editTaxRateModal').modal('show');
            },
            error: function() {
                toastr.error('Failed to load tax rate data');
            }
        });
    });

    // Update Tax Rate
    $('#editTaxRateForm').submit(function(e) {
        e.preventDefault();
        
        const id = $('#edit_id').val();
        clearErrors('editTaxRateForm');
        
        $.ajax({
            url: "{{ route('admin.tax-rates.update', ':id') }}".replace(':id', id),
            type: "PUT",
            data: $(this).serialize(),
            success: function(response) {
                $('#editTaxRateModal').modal('hide');
                table.ajax.reload();
                toastr.success(response.message || 'Tax rate updated successfully');
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    showErrors(xhr.responseJSON.errors);
                } else {
                    toastr.error('An error occurred. Please try again.');
                }
            }
        });
    });

    // Delete Tax Rate - Open confirmation
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        $('#delete_id').val(id);
        $('#deleteTaxRateModal').modal('show');
    });

    // Confirm Delete
    $('#confirmDeleteBtn').click(function() {
        const id = $('#delete_id').val();
        
        $.ajax({
            url: "{{ route('admin.tax-rates.destroy', ':id') }}".replace(':id', id),
            type: "DELETE",
            data: {
                _token: "{{ csrf_token() }}"
            },
            success: function(response) {
                $('#deleteTaxRateModal').modal('hide');
                table.ajax.reload();
                toastr.success(response.message || 'Tax rate deleted successfully');
            },
            error: function(xhr) {
                if (xhr.status === 400) {
                    toastr.error(xhr.responseJSON.message || 'Cannot delete this tax rate');
                } else {
                    toastr.error('An error occurred. Please try again.');
                }
            }
        });
    });

    // Reset form when modal is closed
    $('#createTaxRateModal').on('hidden.bs.modal', function() {
        $('#createTaxRateForm')[0].reset();
        clearErrors('createTaxRateForm');
    });

    $('#editTaxRateModal').on('hidden.bs.modal', function() {
        clearErrors('editTaxRateForm');
    });
});
</script>
@endsection