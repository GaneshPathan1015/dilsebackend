@extends('admin.layouts.master')

@section('main_section')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Add New Tax Rate</h4>
        </div>
        <div class="card-body">
            <form id="createTaxRateForm">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tax Code *</label>
                        <select name="code" class="form-select" required>
                            <option value="">Select Tax Code</option>
                            <option value="GST_DIAMOND">GST_DIAMOND (Diamond - 0.25%)</option>
                            <option value="GST_GOLD">GST_GOLD (Gold - 3%)</option>
                            <option value="GST_MAKING">GST_MAKING (Making Charges - 3%)</option>
                            <option value="CUSTOM">Custom</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tax Name *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">GST Rate (%) *</label>
                        <input type="number" step="0.01" name="rate" class="form-control" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">HSN Code</label>
                        <input type="text" name="hsn_code" class="form-control">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Save Tax Rate</button>
                    <a href="{{ route('tax-rates.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#createTaxRateForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: "{{ route('tax-rates.store') }}",
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    window.location.href = "{{ route('tax-rates.index') }}";
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        toastr.error(messages[0]);
                    });
                }
            }
        });
    });
});
</script>
@endsection