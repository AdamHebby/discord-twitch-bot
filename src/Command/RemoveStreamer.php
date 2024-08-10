<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\Query\CheckRoleQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Psr\Log\LoggerInterface;

class RemoveStreamer extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
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

        $this->logger->info(sprintf(
            'Removed streamer %s from guild "%s" (%s) by user "%s" (%s)',
            $streamer,
            $message->channel->guild->name,
            $message->channel->guild->id,
            $message->author->username,
            $message->author->id
        ));

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
