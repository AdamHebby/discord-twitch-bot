<?php

namespace DiscordTwitchBot\Test\Command;

use Discord\Builders\MessageBuilder;
use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Command\RemoveStreamer;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Test\DiscordMocker;
use Psr\Log\NullLogger;

class RemoveStreamerTest extends TestCase
{
    use DiscordMocker;

    public function testExecuteCorrectlyRemovesStreamer(): void
    {
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);

        $guildSettings = new GuildSettings('123');
        $guildSettings->addStreamer('teststreamer', 'message', '1111111111111111');

        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $removeStreamer = new RemoveStreamer(
            new NullLogger(),
            $guildSettingsRepository,
        );

        $interaction = $this->mockInteraction([
            ['guild', $this->mockGuild([
                ['name', 'testguild'],
            ])],
            ['guild_id', '123'],
            ['member', $this->mockMember([
                ['username', 'testuser'],
                ['id', '123'],
            ])],
            ['data', (object) [
                'options' => [
                    'streamer' => (object) ['value' => 'teststreamer'],
                ]
            ]],
        ]);

        $guildSettingsRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (GuildSettings $guildSettings) {
                return $guildSettings->hasStreamer('teststreamer') === false;
            }));

        $interaction->expects($this->once())
            ->method('respondWithMessage')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === 'teststreamer is no longer being watched!';
            }));

        $removeStreamer->execute($interaction);
    }

    public function testExecuteWarnsWhenStreamerNotBeingWatched(): void
    {
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);

        $guildSettings = new GuildSettings('123');

        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $removeStreamer = new RemoveStreamer(
            new NullLogger(),
            $guildSettingsRepository,
        );

        $interaction = $this->mockInteraction([
            ['guild', $this->mockGuild([
                ['name', 'testguild'],
            ])],
            ['guild_id', '123'],
            ['member', $this->mockMember([
                ['username', 'testuser'],
                ['id', '123'],
            ])],
            ['data', (object) [
                'options' => [
                    'streamer' => (object) ['value' => 'teststreamer'],
                ]
            ]],
        ]);

        $guildSettingsRepository
            ->expects($this->never())
            ->method('save');

        $interaction->expects($this->once())
            ->method('respondWithMessage')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === 'teststreamer is not being watched!';
            }));

        $removeStreamer->execute($interaction);
    }
}
