<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\Query\CheckRoleQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Monolog\Logger;

class AddStreamer extends Command
{
    public function __construct(
        private readonly Logger $logger,
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchApi,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly CheckRoleQuery $checkRoleQuery,
    ) {
    }

    public function getName(): string
    {
        return 'add';
    }

    public function execute(Message $message): void
    {
        Benchmarker::getInstance()->start(__CLASS__);

        if (!$this->checkRoleQuery->execute($message)) {
            $message->reply("You don't have the correct role to use this command!");
            $message->react('⛔');
            return;
        }

        $args          = self::getArgs($message);
        $streamer      = $args[0];
        $guildSettings = $this->guildSettingsRepository->load($message->channel->guild_id);
        array_shift($args);
        $customMessage = implode(' ', $args);

        if ($guildSettings->hasStreamer($streamer)) {
            $message->reply("{$streamer} is already being watched!");
            return;
        }

        if (!$this->twitchApi->checkStreamerExists($streamer)) {
            $message->reply("{$streamer} is not a valid Twitch streamer!");
            return;
        }

        $guildSettings->addStreamer($streamer, $customMessage);
        $this->guildSettingsRepository->save($guildSettings);

        $message->reply("{$streamer} is now being watched!");

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
