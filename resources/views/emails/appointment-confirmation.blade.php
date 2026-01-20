<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Appointment Confirmation - Dilse Jewels</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #d4af37 0%, #b8860b 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
            border-left: 4px solid #d4af37;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .label {
            font-weight: 600;
            color: #555;
            min-width: 150px;
        }
        
        .value {
            flex: 1;
            color: #333;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-virtual {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-showroom {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .reminder-box {
            background: #fff8e1;
            border: 1px solid #ffecb3;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .reminder-box h3 {
            color: #f57c00;
            margin-bottom: 10px;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .footer p {
            margin-bottom: 10px;
            opacity: 0.8;
        }
        
        .contact-info {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        @media (max-width: 600px) {
            .header, .content {
                padding: 20px;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .label {
                min-width: auto;
                margin-bottom: 5px;
            }
            
            .contact-info {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <h1>Dilse Jewels 💎</h1>
            <h2>Your Appointment is Confirmed! 🎉</h2>
            <p>Thank you for choosing Dilse Jewels. We're excited to assist you with your jewellery needs.</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="info-box">
                <div class="info-row">
                    <div class="label">Appointment ID:</div>
                    <div class="value"><strong>#{{ $appointment->id }}</strong></div>
                </div>
                
                <div class="info-row">
                    <div class="label">Date:</div>
                    <div class="value">
                        <strong>{{ \Carbon\Carbon::parse($appointment->appointment_date)->format('l, F j, Y') }}</strong>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="label">Time:</div>
                    <div class="value">
                        <strong>{{ $appointment->appointment_time }}</strong>
                        @if($appointment->time_zone)
                            ({{ $appointment->time_zone }})
                        @endif
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="label">Type:</div>
                    <div class="value">
                        <span class="badge badge-{{ $appointment->appointment_type }}">
                            @if($appointment->appointment_type == 'virtual')
                                💻 Virtual Appointment
                            @else
                                🏢 Showroom Appointment
                            @endif
                        </span>
                    </div>
                </div>
                
                @if($appointment->appointment_type == 'virtual' && $appointment->meeting_link)
                <div class="info-row">
                    <div class="label">Meeting Link:</div>
                    <div class="value">
                        <a href="{{ $appointment->meeting_link }}" style="color: #1976d2; text-decoration: underline; font-weight: 500;">
                            Click here to join virtual meeting
                        </a>
                    </div>
                </div>
                @endif
                
                @if($appointment->appointment_type == 'showroom' && $appointment->location)
                <div class="info-row">
                    <div class="label">Showroom Address:</div>
                    <div class="value">{{ $appointment->location }}</div>
                </div>
                @endif
                
                <div class="info-row">
                    <div class="label">Category:</div>
                    <div class="value">{{ ucfirst($appointment->category) }}</div>
                </div>
                
                @if($appointment->other_category)
                <div class="info-row">
                    <div class="label">Specific Interest:</div>
                    <div class="value">{{ $appointment->other_category }}</div>
                </div>
                @endif
            </div>
            
            <!-- Customer Info -->
            <h3 style="margin-bottom: 15px; color: #333;">Your Information</h3>
            <div class="info-box" style="border-left-color: #4caf50;">
                <div class="info-row">
                    <div class="label">Name:</div>
                    <div class="value">{{ $appointment->name }}</div>
                </div>
                
                <div class="info-row">
                    <div class="label">Email:</div>
                    <div class="value">{{ $appointment->email }}</div>
                </div>
                
                <div class="info-row">
                    <div class="label">Contact:</div>
                    <div class="value">{{ $appointment->contact_number }}</div>
                </div>
            </div>
            
            @if($appointment->additional_information)
            <h3 style="margin-bottom: 15px; color: #333;">Additional Information</h3>
            <div class="info-box" style="border-left-color: #ff9800;">
                <p>{{ $appointment->additional_information }}</p>
            </div>
            @endif
            
            <!-- Important Notes -->
            <div class="reminder-box">
                <h3>Important Notes</h3>
                <ul style="margin-left: 20px;">
                    <li>Please arrive 10 minutes before your scheduled time</li>
                    @if($appointment->appointment_type == 'virtual')
                    <li>Test your camera and microphone before the meeting</li>
                    <li>Ensure you have a stable internet connection</li>
                    @else
                    <li>Please bring any relevant documents or items</li>
                    <li>Parking facilities are available</li>
                    @endif
                    <li>To reschedule or cancel, please contact us at least 24 hours in advance</li>
                </ul>
            </div>
            
            <!-- We look forward message -->
            <div style="text-align: center; margin: 30px 0; padding: 20px; background: #f0f8ff; border-radius: 10px;">
                <h3 style="color: #d4af37; margin-bottom: 10px;">We Look Forward to Seeing You! ✨</h3>
                <p>Our team is excited to help you find the perfect jewellery piece that tells your story.</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>This is an automated confirmation. Please do not reply to this email.</p>
            <p>Appointment ID: #{{ $appointment->id }} | Sent: {{ now()->format('F j, Y, g:i a') }}</p>
            
            <div class="contact-info">
                <div class="contact-item">
                    <span>📧</span>
                    <span>service@dilsejewels.com</span>
                </div>
                <div class="contact-item">
                    <span>📞</span>
                    <span>+91 85115 44005</span>
                </div>
            </div>
            
            <p style="margin-top: 20px; font-size: 12px; opacity: 0.6;">
                &copy; {{ date('Y') }} Dilse Jewels. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>