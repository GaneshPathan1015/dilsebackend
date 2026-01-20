@extends('admin.layouts.master')

@section('main_section')
<style>
    /* Type Badges */
    .badge-virtual { background: #007bff; color: #fff; }
    .badge-showroom { background: #6f42c1; color: #fff; }
    
    /* Stats Cards */
    .stats-card {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
        transition: transform 0.3s;
    }
    
    .stats-card:hover {
        transform: translateY(-5px);
    }
    
    .stats-card .icon {
        width: 50px;
        height: 50px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 15px;
    }
    
    .stats-card.total .icon { background: rgba(0, 123, 255, 0.1); color: #007bff; }
    .stats-card.scheduled .icon { background: rgba(40, 167, 69, 0.1); color: #28a745; }
    .stats-card.unscheduled .icon { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
    .stats-card.today .icon { background: rgba(111, 66, 193, 0.1); color: #6f42c1; }
    
    /* Modal Styles */
    .modal-header {
        background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
        color: white;
    }
    
    .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }
    
    /* Action Buttons */
    .btn-group .btn {
        padding: 5px 10px;
        font-size: 14px;
    }
    
    /* Filter Section */
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    /* DataTable Custom */
    #appointmentsTable tbody tr {
        transition: background-color 0.2s;
    }
    
    /* Appointment Card in Modal */
    .appointment-card {
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .info-label {
        font-weight: 600;
        color: #666;
        min-width: 140px;
    }
    
    .info-value {
        color: #333;
    }
    
    /* Highlight unscheduled appointments */
    .unscheduled-row {
        background-color: #fff8e1 !important;
        border-left: 4px solid #ffc107;
    }
    
    .scheduled-row {
        border-left: 4px solid #28a745;
    }
    
    /* Warning for unscheduled */
    .status-warning {
        color: #ffc107;
        font-size: 12px;
        font-weight: 500;
    }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stats-card total">
                <div class="icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <h6 class="text-muted">Total Requests</h6>
                    <h3 id="totalAppointments">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card scheduled">
                <div class="icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h6 class="text-muted">Scheduled</h6>
                    <h3 id="scheduledAppointments">0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card unscheduled">
                <div class="icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h6 class="text-muted">Pending</h6>
                    <h3 id="unscheduledAppointments">0</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <div class="row">
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-control" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="unscheduled">Pending Schedule</option>
                        <option value="scheduled">Scheduled</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Date (Scheduled)</label>
                    <select class="form-control" id="dateFilter">
                        <option value="">All Dates</option>
                        <option value="today">Today</option>
                        <option value="tomorrow">Tomorrow</option>
                        <option value="week">Next 7 Days</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Appointment Type</label>
                    <select class="form-control" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="virtual">💻 Virtual</option>
                        <option value="showroom">🏢 Showroom</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" id="searchFilter" placeholder="Search name, email, contact...">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <button class="btn btn-primary" onclick="applyFilters()">
                    <i class="fas fa-filter me-1"></i> Apply Filters
                </button>
                <button class="btn btn-secondary" onclick="resetFilters()">
                    <i class="fas fa-redo me-1"></i> Reset
                </button>
                <button class="btn btn-success" onclick="refreshData()">
                    <i class="fas fa-refresh me-1"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Appointment Requests Management</h4>
            <div>
                <button class="btn btn-primary" onclick="refreshData()">
                    <i class="fas fa-refresh me-1"></i> Refresh
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="appointmentsTable">
                    <thead class="bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Date & Time</th>
                            <th>Type</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ============== MODALS ============== -->

<!-- 1. View Appointment Modal -->
<div class="modal fade" id="viewAppointmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Appointment Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="appointmentDetails">
                <!-- Details will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="openScheduleModal()" id="scheduleButton">
                    <i class="fas fa-calendar-plus me-1"></i> Schedule Appointment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 2. Schedule Appointment Modal -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Schedule Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="scheduleForm">
                @csrf
                <input type="hidden" id="scheduleAppointmentId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Appointment Date *</label>
                        <input type="date" class="form-control" name="appointment_date" id="scheduleDate" required 
                               min="{{ date('Y-m-d') }}">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Appointment Time *</label>
                        <input type="time" class="form-control" name="appointment_time" id="scheduleTime" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Time Zone</label>
                        <select class="form-control" name="time_zone" id="scheduleTimeZone">
                            <option value="India">India (IST)</option>
                            <option value="United Kingdom">United Kingdom (GMT)</option>
                            <option value="USA">USA</option>
                            <option value="Australia">Australia</option>

                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Appointment Type *</label>
                        <select class="form-control" name="appointment_type" id="scheduleType" required>
                            <option value="">Select Type</option>
                            <option value="virtual">💻 Virtual Appointment</option>
                            <option value="showroom">🏢 Showroom Appointment</option>
                        </select>
                    </div>
                    
                    <div id="virtualFields" class="mb-3" style="display: none;">
                        <label class="form-label">Meeting Link *</label>
                        <input type="url" class="form-control" name="meeting_link" id="scheduleMeetingLink" 
                               placeholder="https://meet.google.com/xxx-yyyy-zzz">
                        <small class="text-muted">Example: https://meet.google.com/abc-defg-hij</small>
                    </div>
                    
                    <div id="showroomFields" class="mb-3" style="display: none;">
                        <label class="form-label">Showroom Address *</label>
                        <textarea class="form-control" name="location" id="scheduleLocation" rows="2" 
                               placeholder="Enter showroom address"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Confirmation email will be automatically sent to the customer and copied to service@dilsejewels.com
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="scheduleSubmitBtn">
                        <span id="scheduleBtnText">Schedule & Send Email</span>
                        <span id="scheduleLoading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Processing...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. Send Email Modal -->
<div class="modal fade" id="emailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Confirmation Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="emailForm">
                @csrf
                <input type="hidden" id="emailAppointmentId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Email will be sent to:</label>
                        <input type="email" class="form-control" id="recipientEmail" readonly>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Email will be sent to customer and copied to service@dilsejewels.com
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="emailSubmitBtn">
                        <span>Send Email</span>
                        <span id="emailLoading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Sending...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this appointment request? This action cannot be undone.</p>
                <input type="hidden" id="deleteAppointmentId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDelete()" id="deleteSubmitBtn">
                    <span>Delete Appointment</span>
                    <span id="deleteLoading" style="display: none;">
                        <i class="fas fa-spinner fa-spin"></i> Deleting...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    window.dataTable = $('#appointmentsTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: "{{ route('admin.appointments.fetch') }}",
            dataSrc: 'data'
        },
        rowCallback: function(row, data, index) {
            // Add class based on whether appointment is scheduled or not
            if (!data.appointment_date) {
                $(row).addClass('unscheduled-row');
            } else {
                $(row).addClass('scheduled-row');
            }
        },
        columns: [
            { 
                data: 'id',
                render: function(data) {
                    return `<strong>#${data}</strong>`;
                }
            },
            {
                data: null,
                render: function(data) {
                    if (!data.appointment_date) {
                        return `
                            <div>
                                <span class="text-muted">Not scheduled yet</span><br>
                                <small class="status-warning">
                                    <i class="fas fa-clock"></i> Waiting for schedule
                                </small>
                            </div>
                        `;
                    } else {
                        const date = new Date(data.appointment_date);
                        const formattedDate = date.toLocaleDateString('en-GB', {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric'
                        });

                        const [hour, minute] = data.appointment_time.split(':');
                        const formattedTime = new Date(0, 0, 0, hour, minute)
                            .toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });

                        return `
                            <div>
                                <strong>${formattedDate}</strong><br>
                                <small class="text-muted">${formattedTime}</small>
                                ${data.time_zone ? `<br><small class="text-info">${data.time_zone}</small>` : ''}
                            </div>
                        `;
                    }
                }
            },
            {
                data: null,
                render: function(data) {
                    if (!data.appointment_type) {
                        return `<span class="text-muted">Not set</span>`;
                    }
                    const badgeClass = data.appointment_type === 'virtual' ? 'badge-virtual' : 'badge-showroom';
                    const icon = data.appointment_type === 'virtual' ? '💻 Virtual' : '🏢 Showroom';
                    return `<span class="badge ${badgeClass}">${icon}</span>`;
                }
            },
            {
                data: null,
                render: function(data) {
                    return `
                        <div>
                            <strong>${data.name}</strong><br>
                            <small class="text-muted">${data.email}</small>
                        </div>
                    `;
                }
            },
            {
                data: 'contact_number',
                render: function(data) {
                    return data ? `<a href="tel:${data}" class="text-decoration-none">${data}</a>` : 'N/A';
                }
            },
            {
                data: null,
                render: function(data) {
                    const scheduleBtnText = !data.appointment_date ? 
                        `<i class="fas fa-calendar-plus"></i>` : 
                        `<i class="fas fa-calendar-alt"></i> `;
                    
                    const scheduleBtnTitle = !data.appointment_date ? 
                        'Schedule Appointment' : 
                        'Reschedule';
                    
                    const emailBtnDisabled = !data.appointment_date ? 'disabled' : '';
                    
                    return `
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-info view-btn" data-id="${data.id}" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-warning schedule-btn" data-id="${data.id}" title="${scheduleBtnTitle}">
                                ${scheduleBtnText}
                            </button>
                            <button class="btn btn-success email-btn" data-id="${data.id}" data-email="${data.email}" title="Send Email" ${emailBtnDisabled}>
                                <i class="fas fa-envelope"></i>
                            </button>
                            <button class="btn btn-danger delete-btn" data-id="${data.id}" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        language: {
            emptyTable: "No appointment requests found",
            info: "Showing _START_ to _END_ of _TOTAL_ appointments",
            infoEmpty: "Showing 0 to 0 of 0 appointments",
            infoFiltered: "(filtered from _MAX_ total appointments)",
            search: "Search:",
            lengthMenu: "Show _MENU_ appointments"
        }
    });

    // Load statistics
    loadStatistics();

    // Event handlers for buttons
    $(document).on('click', '.view-btn', function() {
        const appointmentId = $(this).data('id');
        viewAppointmentDetails(appointmentId);
    });

    $(document).on('click', '.schedule-btn', function() {
        const appointmentId = $(this).data('id');
        openScheduleModalWithData(appointmentId);
    });

    $(document).on('click', '.email-btn', function() {
        if (!$(this).prop('disabled')) {
            const appointmentId = $(this).data('id');
            const email = $(this).data('email');
            openEmailModal(appointmentId, email);
        }
    });

    $(document).on('click', '.delete-btn', function() {
        const appointmentId = $(this).data('id');
        openDeleteModal(appointmentId);
    });

    // Handle appointment type change in schedule modal
    $('#scheduleType').change(function() {
        const type = $(this).val();
        if (type === 'virtual') {
            $('#virtualFields').show();
            $('#showroomFields').hide();
            $('#scheduleMeetingLink').prop('required', true);
            $('#scheduleLocation').prop('required', false);
        } else if (type === 'showroom') {
            $('#virtualFields').hide();
            $('#showroomFields').show();
            $('#scheduleMeetingLink').prop('required', false);
            $('#scheduleLocation').prop('required', true);
        } else {
            $('#virtualFields').hide();
            $('#showroomFields').hide();
            $('#scheduleMeetingLink').prop('required', false);
            $('#scheduleLocation').prop('required', false);
        }
    });

    // Form submissions
    $('#scheduleForm').submit(function(e) {
        e.preventDefault();
        scheduleAppointment();
    });

    $('#emailForm').submit(function(e) {
        e.preventDefault();
        sendCustomEmail();
    });
});

// ============== FUNCTIONS ==============

let currentAppointmentId = null;
let currentAppointmentEmail = null;

function loadStatistics() {
    $.ajax({
        url: "{{ route('admin.appointments.stats') }}",
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('#totalAppointments').text(response.data.total);
                $('#scheduledAppointments').text(response.data.scheduled);
                $('#unscheduledAppointments').text(response.data.unscheduled);
            }
        },
        error: function() {
            console.log('Error loading statistics');
        }
    });
}

function viewAppointmentDetails(id) {
    currentAppointmentId = id;
    
    $.ajax({
        url: `{{ url('admin/appointments/show') }}/${id}`,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const appointment = response.data;
                currentAppointmentEmail = appointment.email;
                const modal = $('#viewAppointmentModal');
                const details = $('#appointmentDetails');
                const scheduleBtn = $('#scheduleButton');
                
                // Update schedule button text based on status
                if (appointment.appointment_date) {
                    scheduleBtn.html('<i class="fas fa-calendar-edit me-1"></i> Reschedule Appointment');
                } else {
                    scheduleBtn.html('<i class="fas fa-calendar-plus me-1"></i> Schedule Appointment');
                }
                
                const detailsHtml = `
                    <div class="appointment-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Appointment Request #${appointment.id}</h5>
                            ${!appointment.appointment_date ? 
                                `<span class="badge badge-warning">⏳ Pending Schedule</span>` : 
                                `<span class="badge badge-success">✅ Scheduled</span>`
                            }
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="mb-3">📅 Appointment Information</h6>
                                ${appointment.appointment_date ? `
                                <div class="d-flex mb-2">
                                    <span class="info-label">Date:</span>
                                    <span class="info-value ms-2">
                                        <strong>${new Date(appointment.appointment_date).toLocaleDateString('en-GB', {
                                            day: '2-digit',
                                            month: 'long',
                                            year: 'numeric'
                                        })}</strong>
                                    </span>
                                </div>
                                <div class="d-flex mb-2">
                                    <span class="info-label">Time:</span>
                                    <span class="info-value ms-2">
                                        <strong>${appointment.appointment_time}</strong>
                                        ${appointment.time_zone ? ` (${appointment.time_zone})` : ''}
                                    </span>
                                </div>
                                ` : `
                                <div class="d-flex mb-2">
                                    <span class="info-label">Date & Time:</span>
                                    <span class="info-value ms-2 text-warning">Not scheduled yet</span>
                                </div>
                                `}
                                
                                ${appointment.appointment_type ? `
                                <div class="d-flex mb-2">
                                    <span class="info-label">Type:</span>
                                    <span class="info-value ms-2">
                                        <span class="badge ${appointment.appointment_type === 'virtual' ? 'badge-virtual' : 'badge-showroom'}">
                                            ${appointment.appointment_type === 'virtual' ? '💻 Virtual' : '🏢 Showroom'}
                                        </span>
                                    </span>
                                </div>
                                ` : ''}
                                
                                ${appointment.appointment_type === 'virtual' && appointment.meeting_link ? `
                                <div class="d-flex mb-2">
                                    <span class="info-label">Meeting Link:</span>
                                    <span class="info-value ms-2">
                                        <a href="${appointment.meeting_link}" target="_blank">${appointment.meeting_link}</a>
                                    </span>
                                </div>
                                ` : ''}
                                
                                ${appointment.appointment_type === 'showroom' && appointment.location ? `
                                <div class="d-flex mb-2">
                                    <span class="info-label">Showroom:</span>
                                    <span class="info-value ms-2">${appointment.location}</span>
                                </div>
                                ` : ''}
                                
                                <div class="d-flex mb-2">
                                    <span class="info-label">Requested On:</span>
                                    <span class="info-value ms-2">
                                        ${appointment.created_at ? new Date(appointment.created_at).toLocaleString() : 'N/A'}
                                    </span>
                                </div>
                                
                                ${appointment.scheduled_at ? `
                                <div class="d-flex mb-2">
                                    <span class="info-label">Scheduled On:</span>
                                    <span class="info-value ms-2">
                                        ${new Date(appointment.scheduled_at).toLocaleString()}
                                    </span>
                                </div>
                                ` : ''}
                            </div>
                            
                            <div class="col-md-6">
                                <h6 class="mb-3">👤 Customer Information</h6>
                                <div class="d-flex mb-2">
                                    <span class="info-label">Name:</span>
                                    <span class="info-value ms-2">${appointment.name}</span>
                                </div>
                                <div class="d-flex mb-2">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value ms-2">
                                        <a href="mailto:${appointment.email}">${appointment.email}</a>
                                    </span>
                                </div>
                                <div class="d-flex mb-2">
                                    <span class="info-label">Contact:</span>
                                    <span class="info-value ms-2">
                                        <a href="tel:${appointment.contact_number}">${appointment.contact_number}</a>
                                    </span>
                                </div>
                                ${appointment.guest_email ? `
                                <div class="d-flex mb-2">
                                    <span class="info-label">Guest Email:</span>
                                    <span class="info-value ms-2">${appointment.guest_email}</span>
                                </div>
                                ` : ''}
                                
                                <div class="d-flex mb-2">
                                    <span class="info-label">Category:</span>
                                    <span class="info-value ms-2">${appointment.category || 'N/A'}</span>
                                </div>
                                
                                ${appointment.other_category ? `
                                <div class="d-flex mb-2">
                                    <span class="info-label">Specific Interest:</span>
                                    <span class="info-value ms-2">${appointment.other_category}</span>
                                </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        ${appointment.additional_information ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="mb-3">📝 Additional Information</h6>
                                <div class="border p-3 bg-light rounded">
                                    ${appointment.additional_information}
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;
                
                details.html(detailsHtml);
                modal.modal('show');
            } else {
                toastr.error(response.message || 'Appointment not found');
            }
        },
        error: function(xhr) {
            if (xhr.status === 404) {
                toastr.error('Appointment not found');
            } else {
                toastr.error('Error loading appointment details');
            }
        }
    });
}

function openScheduleModal() {
    if (currentAppointmentId) {
        openScheduleModalWithData(currentAppointmentId);
    }
}

function openScheduleModalWithData(id) {
    currentAppointmentId = id;
    
    // Reset form
    $('#scheduleForm')[0].reset();
    $('#virtualFields').hide();
    $('#showroomFields').hide();
    $('#scheduleMeetingLink').prop('required', false);
    $('#scheduleLocation').prop('required', false);
    
    // Set minimum date to today
    $('#scheduleDate').attr('min', new Date().toISOString().split('T')[0]);
    
    // Set default time to next hour
    const nextHour = new Date();
    nextHour.setHours(nextHour.getHours() + 1);
    nextHour.setMinutes(0);
    $('#scheduleTime').val(nextHour.toTimeString().slice(0, 5));
    
    // Load existing data if any
    $.ajax({
        url: `{{ url('admin/appointments/show') }}/${id}`,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const appointment = response.data;
                $('#scheduleAppointmentId').val(appointment.id);
                
                if (appointment.appointment_date) {
                    $('#scheduleDate').val(appointment.appointment_date);
                } else {
                    // Default to tomorrow
                    const tomorrow = new Date();
                    tomorrow.setDate(tomorrow.getDate() + 1);
                    $('#scheduleDate').val(tomorrow.toISOString().split('T')[0]);
                }
                
                if (appointment.appointment_time) {
                    $('#scheduleTime').val(appointment.appointment_time);
                }
                
                if (appointment.time_zone) {
                    $('#scheduleTimeZone').val(appointment.time_zone);
                }
                
                if (appointment.appointment_type) {
                    $('#scheduleType').val(appointment.appointment_type);
                    // Trigger change to show/hide fields
                    $('#scheduleType').trigger('change');
                    
                    if (appointment.appointment_type === 'virtual' && appointment.meeting_link) {
                        $('#scheduleMeetingLink').val(appointment.meeting_link);
                    }
                    if (appointment.appointment_type === 'showroom' && appointment.location) {
                        $('#scheduleLocation').val(appointment.location);
                    }
                }
                
                $('#scheduleModal').modal('show');
            }
        },
        error: function() {
            $('#scheduleModal').modal('show');
        }
    });
}

function scheduleAppointment() {
    const id = $('#scheduleAppointmentId').val();
    const formData = $('#scheduleForm').serialize();
    
    // Show loading state
    $('#scheduleBtnText').hide();
    $('#scheduleLoading').show();
    $('#scheduleSubmitBtn').prop('disabled', true);
    
    $.ajax({
        url: `{{ url('admin/appointments/schedule') }}/${id}`,
        type: 'POST',
        data: formData,
        success: function(response) {
            // Reset loading state
            $('#scheduleBtnText').show();
            $('#scheduleLoading').hide();
            $('#scheduleSubmitBtn').prop('disabled', false);
            
            if (response.success) {
                toastr.success(response.message);
                $('#scheduleModal').modal('hide');
                $('#viewAppointmentModal').modal('hide');
                dataTable.ajax.reload();
                loadStatistics();
            } else {
                if (response.errors) {
                    $.each(response.errors, function(field, messages) {
                        toastr.error(messages[0]);
                    });
                } else {
                    toastr.error(response.message || 'Error scheduling appointment');
                }
            }
        },
        error: function(xhr) {
            // Reset loading state
            $('#scheduleBtnText').show();
            $('#scheduleLoading').hide();
            $('#scheduleSubmitBtn').prop('disabled', false);
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                toastr.error(xhr.responseJSON.message);
            } else {
                toastr.error('Error scheduling appointment. Please check console for details.');
            }
            console.error('Schedule error:', xhr.responseJSON);
        }
    });
}

function openEmailModal(id, email) {
    $('#emailAppointmentId').val(id);
    $('#recipientEmail').val(email);
    $('#emailModal').modal('show');
}

function sendCustomEmail() {
    const id = $('#emailAppointmentId').val();
    
    // Show loading state
    $('#emailSubmitBtn').prop('disabled', true);
    $('#emailLoading').show();
    
    $.ajax({
        url: `{{ url('admin/appointments/send-email') }}/${id}`,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            email_type: 'confirmation'
        },
        success: function(response) {
            // Reset loading state
            $('#emailSubmitBtn').prop('disabled', false);
            $('#emailLoading').hide();
            
            if (response.success) {
                toastr.success(response.message);
                $('#emailModal').modal('hide');
                $('#emailForm')[0].reset();
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            // Reset loading state
            $('#emailSubmitBtn').prop('disabled', false);
            $('#emailLoading').hide();
            toastr.error('Error sending email');
        }
    });
}

function openDeleteModal(id) {
    $('#deleteAppointmentId').val(id);
    $('#deleteModal').modal('show');
}

function confirmDelete() {
    const id = $('#deleteAppointmentId').val();
    
    // Show loading state
    $('#deleteSubmitBtn').prop('disabled', true);
    $('#deleteLoading').show();
    
    $.ajax({
        url: `{{ url('admin/appointments/delete') }}/${id}`,
        type: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            // Reset loading state
            $('#deleteSubmitBtn').prop('disabled', false);
            $('#deleteLoading').hide();
            
            if (response.success) {
                toastr.success(response.message);
                $('#deleteModal').modal('hide');
                dataTable.ajax.reload();
                loadStatistics();
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            // Reset loading state
            $('#deleteSubmitBtn').prop('disabled', false);
            $('#deleteLoading').hide();
            toastr.error('Error deleting appointment');
        }
    });
}

function applyFilters() {
    const filters = {
        status_filter: $('#statusFilter').val(),
        date_filter: $('#dateFilter').val(),
        appointment_type: $('#typeFilter').val(),
        search: $('#searchFilter').val()
    };
    
    dataTable.ajax.url(`{{ route('admin.appointments.fetch') }}?${$.param(filters)}`).load();
}

function resetFilters() {
    $('#statusFilter').val('');
    $('#dateFilter').val('');
    $('#typeFilter').val('');
    $('#searchFilter').val('');
    applyFilters();
}

function refreshData() {
    dataTable.ajax.reload();
    loadStatistics();
    toastr.success('Data refreshed successfully');
}

// Auto refresh every 60 seconds
setInterval(function() {
    refreshData();
}, 60000);
</script>
@endsection