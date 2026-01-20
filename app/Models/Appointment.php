<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'contact_number',
        'email',
        'appointment_date',
        'appointment_time',
        'appointment_type',
        'location',
        'meeting_link',
        'time_zone',
        'scheduled_at',
        'guest_email',
        'category',
        'other_category',
        'additional_information'
    ];

    protected $casts = [
        'appointment_date' => 'date',
        'scheduled_at' => 'datetime'
    ];

    // Check if appointment is scheduled
    public function isScheduled()
    {
        return !is_null($this->appointment_date) && !is_null($this->appointment_time);
    }

    // Scope for unscheduled appointments
    public function scopeUnscheduled($query)
    {
        return $query->whereNull('appointment_date');
    }

    // Scope for scheduled appointments
    public function scopeScheduled($query)
    {
        return $query->whereNotNull('appointment_date');
    }

    // Scope for virtual appointments
    public function scopeVirtual($query)
    {
        return $query->where('appointment_type', 'virtual');
    }

    // Scope for showroom appointments
    public function scopeShowroom($query)
    {
        return $query->where('appointment_type', 'showroom');
    }
}