<?php

namespace DiscordTwitchBot\Application\Query;

use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Repository\GuildSettingsRepository;

class CheckRoleQuery
{
    public function __construct(
        private readonly GuildSettingsRepository $guildSettingsRepository,
    ) {
    }

    public function execute(Message $message): bool
    {
        Benchmarker::getInstance()->start(__CLASS__);
        $guild = $message->channel->guild_id;

        if ($guild === null || $message->member === null) {
            Benchmarker::getInstance()->stop(__CLASS__);
            return true;
        }

        $guildSettings = $this->guildSettingsRepository->load($guild);
        $roleRequired  = $guildSettings->getRole();

        if ($roleRequired === null) {
            Benchmarker::getInstance()->stop(__CLASS__);
            return true;
        }

        if ($message->guild->roles->get('name', $roleRequired) === null) {
            Benchmarker::getInstance()->stop(__CLASS__);
            return true;
        }

        $hasRole = $message->member->roles->get('name', $roleRequired) !== null;

        Benchmarker::getInstance()->stop(__CLASS__);

        return $hasRole;
    }
}
