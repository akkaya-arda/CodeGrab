<?php

namespace App\Notifications\Channels;

use App\Models\Notification;
use App\Models\Setting;
use App\Mail\NotificationMail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;

class SmtpChannel implements NotificationChannelInterface
{
    public function isEnabled(): bool
    {
        $enabled = Setting::getValue('smtp_enabled');
        return $enabled === '1' || $enabled === 'true';
    }

    public function send(Notification $notification): void
    {
        $host = Setting::getValue('smtp_host');
        $port = Setting::getValue('smtp_port') ?: 587;
        $encryption = Setting::getValue('smtp_encryption') ?: 'tls';
        $username = Setting::getValue('smtp_username');
        $password = Setting::getValue('smtp_password');
        $fromAddress = Setting::getValue('smtp_from_address') ?: 'alert@guardhelper.com';
        $fromName = Setting::getValue('smtp_from_name') ?: 'Guard Helper';
        $toAddress = Setting::getValue('smtp_to_address');

        if (!$toAddress || !$host) {
            return;
        }

        
        Config::set('mail.mailers.dynamic_smtp', [
            'transport' => 'smtp',
            'host' => $host,
            'port' => (int) $port,
            'encryption' => $encryption === 'none' ? null : $encryption,
            'username' => $username,
            'password' => $password,
            'timeout' => null,
            'local_domain' => env('MAIL_EHLO_DOMAIN'),
        ]);

        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        Mail::mailer('dynamic_smtp')->to($toAddress)->send(new NotificationMail($notification));
    }
}
