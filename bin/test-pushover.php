<?php

use Psr\Log\LogLevel;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$logFilePath = '/var/log/twitch-bot.log';
$logLevel = LogLevel::INFO;
$envPath = __DIR__ . '/../.env';

if (!file_exists($logFilePath)) {
    $envPath = __DIR__ . '/../.env.dev';
    $logFilePath = 'php://stdout';
    $logLevel = LogLevel::DEBUG;
}

(new Dotenv())->load($envPath);

use DiscordTwitchBot\Application\PushoverNotifier;

PushoverNotifier::factory()->sendNotification('Test notification from Discord Twitch Bot');
