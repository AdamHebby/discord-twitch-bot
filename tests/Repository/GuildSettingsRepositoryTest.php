<?php

namespace DiscordTwitchBot\Test\Repository;

use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Test\Repository\RepositoryTestCase;

class GuildSettingsRepositoryTest extends RepositoryTestCase
{
    public function testSaveAndLoadFromFile(): void
    {
        $repository = new GuildSettingsRepository(self::TEST_DIR);

        $guildSettings = new GuildSettings('123');
        $repository->save($guildSettings);

        $this->assertFileExists(self::TEST_DIR . '/123.json');

        $this->assertEquals($guildSettings, $repository->load('123'));
    }

    public function testLoadFileNotFoundReturnsNewGuildSettings(): void
    {
        $repository = new GuildSettingsRepository(self::TEST_DIR);

        $this->assertEquals(new GuildSettings('123'), $repository->load('123'));
    }

    public function testLoadEmptyFileReturnsNewGuildSettings(): void
    {
        $repository = new GuildSettingsRepository(self::TEST_DIR);

        file_put_contents(self::TEST_DIR . '/123.json', '');

        $this->assertEquals(new GuildSettings('123'), $repository->load('123'));
    }
}
