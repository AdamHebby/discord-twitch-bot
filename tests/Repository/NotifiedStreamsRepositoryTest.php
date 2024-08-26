<?php

namespace DiscordTwitchBot\Test\Repository;

use DiscordTwitchBot\Repository\NotifiedStreamsRepository;
use DiscordTwitchBot\Test\Repository\RepositoryTestCase;

class NotifiedStreamsRepositoryTest extends RepositoryTestCase
{
    private $databaseFile = self::TEST_DIR . '/notified_streams.db';

    public function testFileAndTableCreation(): void
    {
        $repository = new NotifiedStreamsRepository($this->databaseFile);

        $this->assertFileExists($this->databaseFile);
        $pdo = $this->getPdo($this->databaseFile);

        $tables = $pdo->query(
            'SELECT count(*) FROM sqlite_master WHERE type="table" AND name="notified_streams"'
        )->fetchColumn();

        $this->assertEquals(1, $tables);
    }

    public function testAddIsNotified(): void
    {
        $repository = new NotifiedStreamsRepository($this->databaseFile);
        $repository->add('streamer1', 'stream1', 'guild1');

        $this->assertTrue($repository->isNotified('stream1', 'guild1'));
        $this->assertTrue($repository->isNotifiedRestartedStream('streamer1', 'guild1'));

        $repository->remove('stream1', 'guild1');

        $this->assertFalse($repository->isNotified('stream1', 'guild1'));
    }

    public function testIsNotifiedRestartedStream(): void
    {
        $pdo = $this->getPdo($this->databaseFile);
        $repository = new NotifiedStreamsRepository($this->databaseFile);
        $repository->add('streamer1', 'stream1', 'guild1');
        $pdo->prepare(
            'UPDATE notified_streams SET notified_at = datetime("now", "-10 minutes")
            WHERE streamer_name = "streamer1" AND guild_id = "guild1"'
        )->execute();

        $this->assertFalse($repository->isNotified('stream2', 'guild1'));
        $this->assertTrue($repository->isNotifiedRestartedStream('streamer1', 'guild1'));

        $pdo->prepare(
            'UPDATE notified_streams SET notified_at = datetime("now", "-30 minutes")
            WHERE streamer_name = "streamer1" AND guild_id = "guild1"'
        )->execute();

        $this->assertFalse($repository->isNotifiedRestartedStream('streamer1', 'guild1'));
    }
}
