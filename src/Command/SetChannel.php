<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\Query\CheckRoleQuery;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Psr\Log\LoggerInterface;

class SetChannel extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly CheckRoleQuery $checkRoleQuery,
    ) {
    }

    public function getName(): string
    {
        return 'channel';
    }

    public function execute(Message $message): void
    {
        Benchmarker::getInstance()->start(__CLASS__);

        if (!$this->checkRoleQuery->execute($message)) {
            $message->reply("You don't have the correct role to use this command!");
            $message->react('⛔');
            return;
        }

        $args           = self::getArgs($message);
        $channel        = $args[0];
        $guildSettings  = $this->guildSettingsRepository->load($message->channel->guild_id);
        $discordChannel = $message->guild->channels->get('name', $channel);

        $matches = [];
        if (preg_match('/^<#(\d+)>$/', $channel, $matches) !== false && count($matches) === 2) {
            $discordChannel = $message->guild->channels->get('id', $matches[1]);
        }

        if ($discordChannel === null) {
            $message->reply("I can't see the channel {$channel}.");
            return;
        }

        $guildSettings->setChannel($discordChannel->id);
        $this->guildSettingsRepository->save($guildSettings);

        $message->reply("Channel set to <#{$discordChannel->id}>!");

        $this->logger->info(sprintf(
            'Set channel to %s (%s) in guild "%s" (%s) by user %s (%s)',
            // @phpstan-ignore property.notFound
            $discordChannel->name,
            $discordChannel->id,
            $message->channel->guild->name,
            $message->channel->guild->id,
            $message->author->username,
            $message->author->id
        ));

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
