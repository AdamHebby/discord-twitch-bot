<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\Query\CheckRoleQuery;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Psr\Log\LoggerInterface;

class SetPrefix extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly CheckRoleQuery $checkRoleQuery,
    ) {
    }

    public function getName(): string
    {
        return 'prefix';
    }

    public function execute(Message $message): void
    {
        Benchmarker::getInstance()->start(__CLASS__);

        if (!$this->checkRoleQuery->execute($message)) {
            $message->reply("You don't have the correct role to use this command!");
            $message->react('⛔');
            return;
        }

        $args = self::getArgs($message);
        $guild = $message->channel->guild_id;

        if (count($args) !== 1) {
            $message->reply('Invalid number of arguments. Usage: !setPrefix <prefix>');
            return;
        }

        $prefix = "!" . ltrim(trim("$args[0]"), '!');
        $guildSettings = $this->guildSettingsRepository->load($guild);
        $guildSettings->setPrefix($prefix);
        $this->guildSettingsRepository->save($guildSettings);

        $message->reply("Prefix set to: `$prefix`");

        $this->logger->info(sprintf(
            'Set prefix to %s in guild "%s" (%s) by user %s (%s)',
            $prefix,
            $message->channel->guild->name,
            $message->channel->guild->id,
            $message->author->username,
            $message->author->id
        ));

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
