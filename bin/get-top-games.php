<?php

use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GameRepository;
use Psr\Log\LogLevel;
use Symfony\Component\Dotenv\Dotenv;
use TwitchApi\HelixGuzzleClient;
use TwitchApi\TwitchApi;

if (!file_exists(__DIR__ . '/../.env')) {
    echo "Please create a .env file", PHP_EOL;
    exit(1);
}

if (!extension_loaded('sqlite3')) {
    echo "Please install sqlite3 extension", PHP_EOL;
    exit(1);
}

if (!file_exists(__DIR__ . '/../data')) {
    mkdir(__DIR__ . '/../data');
}

require_once __DIR__ . '/../vendor/autoload.php';

$envPath = __DIR__ . '/../.env';
$logFilePath = 'php://stdout';
$logLevel = LogLevel::DEBUG;

(new Dotenv())->load($envPath);

$twitch = new TwitchWrapper(
    new TwitchApi(
        new HelixGuzzleClient($_ENV['TWITCH_CLIENT_ID']),
        $_ENV['TWITCH_CLIENT_ID'],
        $_ENV['TWITCH_CLIENT_SECRET']
    )
);

$gameRepository = new GameRepository(__DIR__ . '/../data/games.db');
foreach ($twitch->getTopGames(2000) as $gameId => $gameName) {
    $gameRepository->add($gameId, $gameName);
}
