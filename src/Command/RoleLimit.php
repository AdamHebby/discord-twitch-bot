<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\Query\CheckRoleQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Monolog\Logger;

class RoleLimit extends Command
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
        return 'role';
    }

    public function execute(Message $message): void
    {
        Benchmarker::getInstance()->start(__CLASS__);
        $args              = self::getArgs($message);
        $guildSettings     = $this->guildSettingsRepository->load($message->channel->guild_id);
        $currentRole       = $guildSettings->getRole();
        $currentRoleString = $currentRole === null ? 'none' : $currentRole;

        if (!isset($args[0])) {
            $prefix = $guildSettings->getPrefix();
            $message->reply("Current role limit is `{$currentRoleString}`. Usage: `{$prefix} role <role>`");
            return;
        }

        if ($this->checkRoleQuery->execute($message) === false) {
            $message->reply(
                "You don't have the role `{$currentRoleString}`! " .
                "Only users with the role `{$currentRoleString}` can change the role limit."
            );
            $message->react('⛔');
            return;
        }

        $newRole       = $args[0] ?? 'none';
        $newRoleValue  = $newRole === 'none' ? null : $newRole;

        if ($currentRole === $newRoleValue) {
            $message->reply("`{$newRole}` is already the role limit!");
            return;
        }

        if ($newRoleValue !== null && $message->guild->roles->get('name', $newRoleValue) === null) {
            $message->reply("`{$newRole}` is not a valid role!");
            return;
        }

        $guildSettings->setRole($newRoleValue);
        $this->guildSettingsRepository->save($guildSettings);

        $message->reply("Role limit set to `{$newRole}`!");

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
