<?php

namespace DiscordTwitchBot\Test\Repository;

use PHPUnit\Framework\TestCase;

class RepositoryTestCase extends TestCase
{
    public const TEST_DIR = '/tmp/discord-twitch-bot-test';

    protected function tearDown(): void
    {
        // empty the test directory
        exec('rm -rf ' . self::TEST_DIR . '/*');

        parent::tearDown();
    }

    protected function setUp(): void
    {
        if (!file_exists(self::TEST_DIR)) {
            mkdir(self::TEST_DIR);
        }

        parent::setUpBeforeClass();
    }

    public function getPdo(string $path): \PDO
    {
        return new \PDO('sqlite:' . $path);
    }
}
