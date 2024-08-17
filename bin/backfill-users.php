<?php

use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

(new Dotenv())->load(__DIR__ . '/../.env');

$twitch = new TwitchWrapper($_ENV['TWITCH_CLIENT_ID'], $_ENV['TWITCH_CLIENT_SECRET']);
$streamerRepo = new \DiscordTwitchBot\Repository\StreamerRepository(
    __DIR__ . '/../data/streamer.db'
);

$jsonFiles = glob(__DIR__ . '/../data/*.json');

foreach ($jsonFiles as $jsonFile) {
    $guildSettings = json_decode(file_get_contents($jsonFile), true);

    foreach ($guildSettings['streamers'] as $streamer => $customMessage) {
        if ($streamerRepo->getStreamerByLogin($streamer) === null) {
            $streamerObject = $twitch->getStreamer($streamer);

            if ($streamerObject !== null) {
                $streamerRepo->add($streamerObject);
            }
        }
    }
}
