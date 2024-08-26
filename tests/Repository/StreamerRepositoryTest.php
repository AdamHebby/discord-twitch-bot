<?php

namespace DiscordTwitchBot\Test\Repository;

use DiscordTwitchBot\Model\Streamer;
use DiscordTwitchBot\Repository\StreamerRepository;
use DiscordTwitchBot\Test\Repository\RepositoryTestCase;

class StreamerRepositoryTest extends RepositoryTestCase
{
    private $databaseFile = self::TEST_DIR . '/streamer.db';

    public function testFileAndTableCreation(): void
    {
        $repository = new StreamerRepository($this->databaseFile);

        $this->assertFileExists($this->databaseFile);
        $pdo = $this->getPdo($this->databaseFile);

        $tables = $pdo->query(
            'SELECT count(*) FROM sqlite_master WHERE type="table" AND name="streamer"'
        )->fetchColumn();

        $this->assertEquals(1, $tables);
    }

    public function testAddRemoveGetById(): void
    {
        $streamer   = $this->getStreamer('1', 'streamer1');
        $repository = new StreamerRepository($this->databaseFile);
        $repository->add($streamer);

        $this->assertEquals($streamer, $repository->getStreamerById('1'));

        $repository->removeById('1');

        $this->assertNull($repository->getStreamerById('1'));
    }

    public function testAddRemoveGetByLogin(): void
    {
        $streamer   = $this->getStreamer('1', 'streamer1');
        $repository = new StreamerRepository($this->databaseFile);
        $repository->add($streamer);

        $this->assertEquals($streamer, $repository->getStreamerByLogin('streamer1'));

        $repository->removeByLogin('streamer1');

        $this->assertNull($repository->getStreamerByLogin('streamer1'));
    }

    public function testHasStreamer(): void
    {
        $streamer   = $this->getStreamer('1', 'streamer1');
        $repository = new StreamerRepository($this->databaseFile);
        $repository->add($streamer);

        $this->assertTrue($repository->hasStreamer('streamer1'));
        $this->assertTrue($repository->hasStreamerById('1'));
        $this->assertFalse($repository->hasStreamer('streamer2'));

        $repository->removeByLogin('streamer1');

        $this->assertFalse($repository->hasStreamer('streamer1'));
    }

    public function testGetAll(): void
    {
        $repository = new StreamerRepository($this->databaseFile);
        $repository->add($this->getStreamer('1', 'streamer1'));
        $repository->add($this->getStreamer('2', 'streamer2'));

        $streamers = $repository->getAll();

        $this->assertContainsOnlyInstancesOf(Streamer::class, $streamers);
        $this->assertCount(2, $streamers);

        $this->assertSame('streamer1', $streamers[0]->login);
        $this->assertSame('streamer2', $streamers[1]->login);
    }

    private function getStreamer(string $id, string $username): Streamer
    {
        return Streamer::fromJsonArray([
            'id' => $id,
            'login' => $username,
            'display_name' => $username,
            'type' => 'unknown',
            'broadcaster_type' => 'affiliate',
            'description' => 'Test',
            'profile_image_url' => 'https://example.com/profile.jpg',
            'offline_image_url' => 'https://example.com/offline.jpg',
            'view_count' => 100,
            'created_at' => '2024-01-01T00:00:00+00:00',
        ]);
    }
}
