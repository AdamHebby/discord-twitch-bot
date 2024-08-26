<?php

namespace DiscordTwitchBot\Test\Model;

use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Model\GuildSettings;
use PHPUnit\Framework\Attributes\DataProvider;

class GuildSettingsTest extends TestCase
{
    private array $guildSettingsData = [
        'guildId' => '123456789101112',
        'streamers' => [
            'streamer' => 'message',
            'streamer2' => null,
        ],
        'channels' => [
            'streamer'  => '111111111111',
            'streamer2' => '222222222222',
        ],
        'clipChannels' => [
        ],
    ];

    public function testGuildSettingsJsonArray(): void
    {
        $guildSettings = GuildSettings::fromJsonArray($this->guildSettingsData);

        $this->assertSame($this->guildSettingsData, $guildSettings->toJsonArray());

        $this->assertSame($this->guildSettingsData['guildId'], $guildSettings->getGuildId());
    }

    public function testGuildSettingsStreamers(): void
    {
        $guildSettings = GuildSettings::fromJsonArray($this->guildSettingsData);

        $this->assertSame('message', $guildSettings->getStreamerMessage('streamer'));
        $this->assertNull($guildSettings->getStreamerMessage('streamer2'));
        $this->assertSame(['streamer', 'streamer2'], $guildSettings->getStreamers());

        $guildSettings->addStreamer('streamer3', null, '87661523123');
        $this->assertSame('87661523123', $guildSettings->getStreamerChannel('streamer3'));
        $this->assertSame(null, $guildSettings->getStreamerMessage('streamer3'));

        $this->assertTrue($guildSettings->hasStreamer('streamer'));

        $guildSettings->removeStreamer('streamer');

        $this->assertFalse($guildSettings->hasStreamer('streamer'));
    }

    public function testGuildSettingsChannels(): void
    {
        $guildSettings = GuildSettings::fromJsonArray($this->guildSettingsData);

        $this->assertSame('111111111111', $guildSettings->getStreamerChannel('streamer'));
        $this->assertSame('222222222222', $guildSettings->getStreamerChannel('streamer2'));

        $this->assertSame($this->guildSettingsData['channels'], $guildSettings->getChannels());
    }

    public function testClipChannels(): void
    {
        $guildSettings = GuildSettings::fromJsonArray($this->guildSettingsData);

        $this->assertSame([], $guildSettings->getClipChannels());

        $guildSettings->addClipChannel('streamer', '123456789101112');

        $this->assertSame(['streamer' => '123456789101112'], $guildSettings->getClipChannels());
        $this->assertSame('123456789101112', $guildSettings->getClipChannel('streamer'));

        $guildSettings->removeClipChannel('streamer');

        $this->assertSame([], $guildSettings->getClipChannels());
    }
}
