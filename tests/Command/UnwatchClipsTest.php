<?php

namespace DiscordTwitchBot\Test\Command;

use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Command\UnwatchClips;
use Discord\Builders\MessageBuilder;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Test\DiscordMocker;
use Psr\Log\NullLogger;

use function React\Promise\resolve;

class UnwatchClipsTest extends TestCase
{
    use DiscordMocker;

    public function testExecuteUnwatchesClips(): void
    {
        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);

        $guildSettings = new GuildSettings('123');
        $guildSettings->addClipChannel('teststreamer', '123');
        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $unwatchClips = new UnwatchClips(
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
                return $guildSettings->getClipChannel('teststreamer') === null;
            }));

        $interaction->expects($this->once())->method('acknowledgeWithResponse')->willReturn(resolve(true));

        $interaction->expects($this->once())
            ->method('updateOriginalResponse')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === 'Clips for streamer teststreamer will no longer be posted.';
            }));

        $unwatchClips->execute($interaction);
    }

    public function testExecuteHandlesNotWatchedChannels(): void
    {

        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);

        $guildSettings = new GuildSettings('123');
        $guildSettingsRepository
            ->method('load')
            ->with('123')
            ->willReturn($guildSettings);

        $unwatchClips = new UnwatchClips(
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

        $interaction->expects($this->once())->method('acknowledgeWithResponse')->willReturn(resolve(true));

        $interaction->expects($this->once())
            ->method('updateOriginalResponse')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return $data['content'] === 'Clips for streamer teststreamer are not being posted.';
            }));

        $unwatchClips->execute($interaction);
    }

}
