<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\Query\CheckRoleQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Monolog\Logger;

class SetPrefix extends Command
{
    public function __construct(
        private readonly Logger $logger,
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchApi,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly CheckRoleQuery $checkRoleQuery,
    )
    {
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

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
