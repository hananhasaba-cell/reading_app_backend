<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FcmService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase-service-account.json'));

        $this->messaging = $factory->createMessaging();
    }


    public function sendToUserDevices($user, array $notification, array $data = []): void
    {
        $title = $notification['title'] ?? '';
        $body  = $notification['body']  ?? '';


        $notif = Notification::create($title, $body);

        foreach ($user->devices as $device) {
            $token = $device->token;
            if (!$token) {
                continue;
            }

            try {

                $message = CloudMessage::new()
                    ->withTarget('token', $token)
                    ->withNotification($notif)
                    ->withData($data);

                $this->messaging->send($message);
            } catch (\Throwable $e) {
                continue;
            }
        }
    }
}