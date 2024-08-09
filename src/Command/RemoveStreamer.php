<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\Query\CheckRoleQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Monolog\Logger;

class RemoveStreamer extends Command
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
        return 'remove';
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

        if ($guildSettings->hasStreamer($streamer)) {
            $message->reply("{$streamer} is not being watched!");
            return;
        }

        $guildSettings->removeStreamer($streamer);
        $this->guildSettingsRepository->save($guildSettings);

        $message->reply("{$streamer} is no longer being watched!");

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
