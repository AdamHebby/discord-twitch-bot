<?php

namespace DiscordTwitchBot\Test\Repository;

use DiscordTwitchBot\Repository\NotifiedClipsRepository;
use DiscordTwitchBot\Test\Repository\RepositoryTestCase;

class NotifiedClipsRepositoryTest extends RepositoryTestCase
{
    private $databaseFile = self::TEST_DIR . '/notified_clips.db';

    public function testFileAndTableCreation(): void
    {
        $repository = new NotifiedClipsRepository($this->databaseFile);

        $this->assertFileExists($this->databaseFile);
        $pdo = $this->getPdo($this->databaseFile);

        $tables = $pdo->query(
            'SELECT count(*) FROM sqlite_master WHERE type="table" AND name="notified_clips"'
        )->fetchColumn();

        $this->assertEquals(1, $tables);
    }

    public function testIsNotifiedAddRemove(): void
    {
        $repository = new NotifiedClipsRepository($this->databaseFile);
        $repository->add('clip_id', 'guild_id');

        $this->assertTrue($repository->isNotified('clip_id', 'guild_id'));

        $repository->remove('clip_id', 'guild_id');

        $this->assertFalse($repository->isNotified('clip_id', 'guild_id'));
    }
}
