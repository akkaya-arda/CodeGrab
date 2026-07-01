<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Notification $notification)
    {
    }

    public function build()
    {
        return $this->subject('System Alert: ' . $this->notification->title)
            ->html("
                        <div style='font-family: sans-serif; padding: 20px; color: #333;'>
                            <h2 style='color: #dc2626; border-bottom: 1px solid #ddd; padding-bottom: 10px;'>System Notification Alert</h2>
                            <p><strong>Type:</strong> " . e($this->notification->type) . "</p>
                            <p><strong>Alert:</strong> " . e($this->notification->title) . "</p>
                            <p><strong>Details:</strong></p>
                            <pre style='background: #f4f4f5; border: 1px solid #e4e4e7; padding: 15px; border-radius: 6px; font-family: monospace; overflow-x: auto;'>" . e($this->notification->message) . "</pre>
                            <hr style='border: 0; border-top: 1px solid #ddd; margin: 20px 0;' />
                            <p style='font-size: 11px; color: #71717a;'>This is an automated alert.</p>
                        </div>
                    ");
    }
}
