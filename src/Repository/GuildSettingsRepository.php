<?php

namespace DiscordTwitchBot\Repository;

use DiscordTwitchBot\Model\GuildSettings;

class GuildSettingsRepository extends JsonRepository
{
    public function __construct(
        private string $path,
    ) {
    }

    public function save(GuildSettings $settings): void
    {
        $path = $this->path . '/' . $settings->getGuildId() . '.json';
        $this->saveJson($path, $settings->toJsonArray());
    }

    public function load(string $guildId): GuildSettings
    {
        $path = $this->path . '/' . $guildId . '.json';
        $data = $this->loadJson($path);

        if (empty($data)) {
            return new GuildSettings($guildId);
        }

        return GuildSettings::fromJsonArray($data);
    }
}
