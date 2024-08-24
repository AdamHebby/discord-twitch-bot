<?php

use Discord\Discord;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Bot;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GameRepository;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\NotifiedClipsRepository;
use DiscordTwitchBot\Repository\NotifiedStreamsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;
use Symfony\Component\Dotenv\Dotenv;
use TwitchApi\HelixGuzzleClient;
use TwitchApi\TwitchApi;

if (file_exists(__DIR__ . '/../lock') && filemtime(__DIR__ . '/../lock') > time() - 55) {
    echo "Bot is already running", PHP_EOL;
    exit(1);
}

touch(__DIR__ . '/../lock');

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

Benchmarker::getInstance()->start('Boot');

$logFilePath = '/var/log/twitch-bot.log';
$logLevel = LogLevel::INFO;
$envPath = __DIR__ . '/../.env';

if (!file_exists($logFilePath)) {
    $envPath = __DIR__ . '/../.env.dev';
    $logFilePath = 'php://stdout';
    $logLevel = LogLevel::DEBUG;
}

(new Dotenv())->load($envPath);

$discordLogger = new Logger('Discord');
$discordLogger->pushHandler(new StreamHandler($logFilePath, LogLevel::WARNING));

$botLogger = new Logger('TwitchBot');
$botLogger->pushHandler(new StreamHandler($logFilePath, $logLevel));

$discord = new Discord([
    'logger' => $discordLogger,
    'token' => $_ENV['DISCORD_BOT_TOKEN'],
    'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT,
    'storeMessages' => false,
    'disabledEvents' => [
        Event::PRESENCE_UPDATE,
        Event::TYPING_START,
        Event::VOICE_STATE_UPDATE,
        Event::VOICE_SERVER_UPDATE,
        // Event::INTERACTION_CREATE,
        Event::USER_UPDATE,
        // Event::GUILD_CREATE,
        // Event::GUILD_DELETE,
        // Event::GUILD_UPDATE,
        // Event::GUILD_INTEGRATIONS_UPDATE,
        // Event::INTEGRATION_CREATE,
        // Event::INTEGRATION_UPDATE,
        // Event::INTEGRATION_DELETE,
        Event::WEBHOOKS_UPDATE,
        // Event::APPLICATION_COMMAND_PERMISSIONS_UPDATE,
        Event::INVITE_CREATE,
        Event::INVITE_DELETE,
        // Event::CHANNEL_CREATE,
        // Event::CHANNEL_UPDATE,
        // Event::CHANNEL_DELETE,
        Event::CHANNEL_PINS_UPDATE,
        Event::GUILD_BAN_ADD,
        Event::GUILD_BAN_REMOVE,
        Event::GUILD_EMOJIS_UPDATE,
        Event::GUILD_STICKERS_UPDATE,
        // Event::MESSAGE_CREATE,
        Event::MESSAGE_DELETE,
        Event::MESSAGE_DELETE_BULK,
        Event::MESSAGE_UPDATE,
        Event::MESSAGE_REACTION_ADD,
        Event::MESSAGE_REACTION_REMOVE,
        Event::MESSAGE_REACTION_REMOVE_ALL,
        Event::MESSAGE_REACTION_REMOVE_EMOJI,
        // Event::GUILD_MEMBER_ADD,
        // Event::GUILD_MEMBER_REMOVE,
        // Event::GUILD_MEMBER_UPDATE,
        // Event::GUILD_ROLE_CREATE,
        // Event::GUILD_ROLE_DELETE,
        // Event::GUILD_ROLE_UPDATE,
        Event::GUILD_SCHEDULED_EVENT_CREATE,
        Event::GUILD_SCHEDULED_EVENT_UPDATE,
        Event::GUILD_SCHEDULED_EVENT_DELETE,
        Event::GUILD_SCHEDULED_EVENT_USER_ADD,
        Event::GUILD_SCHEDULED_EVENT_USER_REMOVE,
        Event::THREAD_CREATE,
        Event::THREAD_UPDATE,
        Event::THREAD_DELETE,
        Event::THREAD_LIST_SYNC,
        Event::THREAD_MEMBER_UPDATE,
        Event::THREAD_MEMBERS_UPDATE,
        Event::STAGE_INSTANCE_CREATE,
        Event::STAGE_INSTANCE_UPDATE,
        Event::STAGE_INSTANCE_DELETE,
        Event::AUTO_MODERATION_RULE_CREATE,
        Event::AUTO_MODERATION_RULE_UPDATE,
        Event::AUTO_MODERATION_RULE_DELETE,
        Event::AUTO_MODERATION_ACTION_EXECUTION,
    ],
]);

$bot = new Bot(
    $discord,
    new TwitchWrapper(
        new TwitchApi(
            new HelixGuzzleClient($_ENV['TWITCH_CLIENT_ID']),
            $_ENV['TWITCH_CLIENT_ID'],
            $_ENV['TWITCH_CLIENT_SECRET']
        )
    ),
    $botLogger,
    new GuildSettingsRepository(__DIR__ . '/../data/'),
    new NotifiedStreamsRepository(__DIR__ . '/../data/notified_streams.db'),
    new StreamerRepository(__DIR__ . '/../data/streamer.db'),
    new GameRepository(__DIR__ . '/../data/games.db'),
    new NotifiedClipsRepository(__DIR__ . '/../data/notified_clips.db')
);

Benchmarker::getInstance()->stop('Boot');

$bot->run();
