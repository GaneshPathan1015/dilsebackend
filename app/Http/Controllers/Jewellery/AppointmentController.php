<?php

namespace App\Http\Controllers\Jewellery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Appointment;
use Carbon\Carbon;
use App\Mail\AppointmentConfirmationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    /**
     * Show the appointment management page
     */
    public function index()
    {
        return view('admin.Jewellery.appointments.index'); 
    }

    /**
     * Fetch all appointments for DataTable
     */
    public function fetch(Request $request)
    {
        try {
            $query = Appointment::query();
            
            // Search filter
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('contact_number', 'like', '%' . $search . '%')
                      ->orWhere('category', 'like', '%' . $search . '%');
                });
            }
            
            // Type filter
            if ($request->has('appointment_type') && $request->appointment_type) {
                $query->where('appointment_type', $request->appointment_type);
            }
            
            // Status filter - show only unscheduled appointments
            if ($request->has('status_filter')) {
                if ($request->status_filter == 'unscheduled') {
                    $query->whereNull('appointment_date');
                } elseif ($request->status_filter == 'scheduled') {
                    $query->whereNotNull('appointment_date');
                }
            }
            
            // Date filter for scheduled appointments
            if ($request->has('date_filter') && $request->date_filter) {
                if ($request->date_filter == 'today') {
                    $query->whereDate('appointment_date', Carbon::today());
                } elseif ($request->date_filter == 'tomorrow') {
                    $query->whereDate('appointment_date', Carbon::tomorrow());
                } elseif ($request->date_filter == 'week') {
                    $query->whereBetween('appointment_date', 
                        [Carbon::now(), Carbon::now()->addDays(7)]
                    );
                } elseif ($request->date_filter == 'month') {
                    $query->whereBetween('appointment_date', 
                        [Carbon::now(), Carbon::now()->addDays(30)]
                    );
                }
            }

            $appointments = $query->orderByRaw('appointment_date IS NULL DESC')
                ->orderBy('appointment_date', 'desc')
                ->orderBy('appointment_time', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $appointments
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching appointments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching appointments'
            ], 500);
        }
    }

    /**
     * Get single appointment details
     */
    public function show($id)
    {
        try {
            $appointment = Appointment::find($id);
            
            if (!$appointment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Appointment not found'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $appointment
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching appointment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found'
            ], 404);
        }
    }

    /**
 * Schedule an appointment (set date, time, type)
 */
public function schedule(Request $request, $id)
{
    try {
        \Log::info('Scheduling appointment:', $request->all());
        
        $appointment = Appointment::find($id);
        
        if (!$appointment) {
            \Log::error('Appointment not found with ID: ' . $id);
            return response()->json([
                'success' => false,
                'message' => 'Appointment not found'
            ], 404);
        }
        
        $validator = Validator::make($request->all(), [
            'appointment_date' => 'required|date',
            'appointment_time' => 'required|date_format:H:i',
            'appointment_type' => 'required|in:virtual,showroom',
            'time_zone' => 'nullable|string',
            'meeting_link' => 'nullable|url|required_if:appointment_type,virtual',
            'location' => 'nullable|string|required_if:appointment_type,showroom',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        \Log::info('Updating appointment data:', $validator->validated());

        $appointment->update([
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'appointment_type' => $request->appointment_type,
            'time_zone' => $request->time_zone,
            'meeting_link' => $request->meeting_link,
            'location' => $request->location,
            'scheduled_at' => now(),
        ]);

        \Log::info('Appointment updated successfully, sending email...');

        // Send confirmation email
        $emailSent = $this->sendConfirmationEmail($appointment);

        if (!$emailSent) {
            \Log::warning('Email sending failed, but appointment was scheduled');
        }

        return response()->json([
            'success' => true,
            'message' => 'Appointment scheduled successfully! Confirmation email sent to customer.',
            'data' => $appointment
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Error scheduling appointment: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'Error scheduling appointment: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * Send email manually
     */
    public function sendEmail(Request $request, $id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            
            // Check if appointment is scheduled
            if (!$appointment->appointment_date || !$appointment->appointment_time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please schedule the appointment first with date and time'
                ], 400);
            }
            
            $this->sendConfirmationEmail($appointment);

            return response()->json([
                'success' => true,
                'message' => 'Confirmation email sent successfully to customer'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error sending email: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error sending email'
            ], 500);
        }
    }

    /**
     * Delete an appointment
     */
    public function destroy($id)
    {
        try {
            $appointment = Appointment::findOrFail($id);
            $appointment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Appointment deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting appointment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting appointment'
            ], 500);
        }
    }

    /**
     * Get appointment statistics
     */
    public function getStats()
    {
        try {
            $total = Appointment::count();
            $scheduled = Appointment::whereNotNull('appointment_date')->count();
            $unscheduled = Appointment::whereNull('appointment_date')->count();
            $virtual = Appointment::where('appointment_type', 'virtual')->count();
            $showroom = Appointment::where('appointment_type', 'showroom')->count();
            $today = Appointment::whereDate('appointment_date', Carbon::today())->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total' => $total,
                    'scheduled' => $scheduled,
                    'unscheduled' => $unscheduled,
                    'virtual' => $virtual,
                    'showroom' => $showroom,
                    'today' => $today
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics'
            ], 500);
        }
    }

    /**
     * Send confirmation email
     */
    private function sendConfirmationEmail(Appointment $appointment)
    {
        try {
            $details = [
                'type' => 'confirmation',
                'sent_at' => now()->format('Y-m-d H:i:s'),
                'appointment_id' => $appointment->id,
            ];

            // Send to customer
            Mail::to($appointment->email)
                ->send(new AppointmentConfirmationMail($appointment, $details));

            // Send to guest if provided
            if ($appointment->guest_email) {
                Mail::to($appointment->guest_email)
                    ->send(new AppointmentConfirmationMail($appointment, $details));
            }

            // Send copy to admin (service@dilsejewels.com)
            Mail::to('service@dilsejewels.com')
                ->send(new AppointmentConfirmationMail($appointment, $details));

            return true;
        } catch (\Exception $e) {
            Log::error('Error sending confirmation email: ' . $e->getMessage());
            return false;
        }
    }
}