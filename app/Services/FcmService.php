<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Modules\User\Models\User;

class FcmService
{
    protected ?string $credentialsPath;

    protected ?Messaging $messaging = null;

    public function __construct()
    {
        $this->credentialsPath = config('services.fcm.credentials');
    }

    protected function messaging(): ?Messaging
    {
        if ($this->messaging) {
            return $this->messaging;
        }

        if (! $this->credentialsPath || ! file_exists($this->credentialsPath)) {
            logger()->warning('FCM credentials not found', ['path' => $this->credentialsPath]);

            return null;
        }

        $factory = (new Factory)->withServiceAccount($this->credentialsPath);

        $this->messaging = $factory->createMessaging();
        logger()->info('FCM messaging initialized');

        return $this->messaging;
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (! $user->fcm_token) {
            logger()->warning('FCM skipped: no token for user '.$user->id);

            return false;
        }

        $messaging = $this->messaging();

        if (! $messaging) {
            logger()->warning('FCM skipped: messaging not initialized');

            return false;
        }

        try {
            $message = CloudMessage::new()->withToken($user->fcm_token)
                ->withNotification(Notification::create($title, $body))
                ->withData($data);

            $messaging->send($message);
            logger()->info('FCM sent to user '.$user->id);

            return true;
        } catch (\Throwable $e) {
            logger()->error('FCM send failed: '.$e->getMessage());

            return false;
        }
    }
}
