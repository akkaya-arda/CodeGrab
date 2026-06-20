<?php

namespace App\Notifications\Channels;

use App\Models\Notification;

interface NotificationChannelInterface
{
    public function send(Notification $notification): void;

    public function isEnabled(): bool;
}
