<?php

namespace DiscordTwitchBot\Application;

class PushoverNotifier
{
    public function __construct(
        private readonly string $pushoverToken,
        private readonly string $pushoverUserKey,
    ) {
    }

    public function sendNotification(string $message): void
    {
        $data = [
            'token' => $this->pushoverToken,
            'user' => $this->pushoverUserKey,
            'message' => $message,
        ];

        $ch = curl_init('https://api.pushover.net/1/messages.json');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
    }

    public static function factory(): self
    {
        return new self(
            $_ENV['PUSHOVER_TOKEN'],
            $_ENV['PUSHOVER_USER_KEY']
        );
    }
}
