<?php

namespace DiscordTwitchBot\Test\Command;

use Discord\Builders\MessageBuilder;
use DiscordTwitchBot\Command\CommandHandler;
use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Command\ListStreamers;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Test\DiscordMocker;
use Psr\Log\NullLogger;

use function React\Promise\resolve;

class ListStreamersTest extends TestCase
{
    use DiscordMocker;

    public function testExecute(): void
    {
        $commandHandler = new CommandHandler();
        $logger         = new NullLogger();
        $guildSettings  = new GuildSettings('456');

        $guildSettings->addStreamer('teststreamer', 'message', '1111111111111111');
        $guildSettings->addStreamer('teststreamer2', null, '2222222222222222');
        $guildSettings->addStreamer('teststreamer3', 'message 2 {game} {name}', '3333333333333333');

        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $guildSettingsRepository->expects($this->once())
            ->method('load')
            ->willReturn($guildSettings);

        $interaction = $this->mockInteraction([
            ['guild', $this->mockGuild([
                ['name', 'testguild'],
            ])],
            ['guild_id', '456'],
            ['member', $this->mockMember([
                ['username', 'testuser'],
                ['id', '123'],
            ])],
        ]);

        $interaction->expects($this->once())->method('acknowledgeWithResponse')->willReturn(resolve(true));

        $interaction->expects($this->once())
            ->method('updateOriginalResponse')
            ->with($this->callback(function (MessageBuilder $message) {
                $message = json_decode(json_encode($message), true)['content'];
                return str_contains($message, 'teststreamer') &&
                    str_contains($message, 'teststreamer2') &&
                    str_contains($message, 'teststreamer3') &&
                    str_contains($message, 'message') &&
                    str_contains($message, '1111111111111111') &&
                    str_contains($message, '2222222222222222') &&
                    str_contains($message, 'message 2 {game} {name}') &&
                    str_contains($message, '3333333333333333');
            }));

        $listStreamers = new ListStreamers($logger, $guildSettingsRepository);

        $commandHandler->addCommand($listStreamers);
        $listStreamers->execute($interaction);
    }

    public function testExecuteRespondsWithNoStreamersBeingWatched(): void
    {
        $commandHandler = new CommandHandler();
        $logger         = new NullLogger();
        $guildSettings  = new GuildSettings('456');

        $guildSettingsRepository = $this->createMock(GuildSettingsRepository::class);
        $guildSettingsRepository->expects($this->once())
            ->method('load')
            ->willReturn($guildSettings);

        $interaction = $this->mockInteraction([
            ['guild', $this->mockGuild([
                ['name', 'testguild'],
            ])],
            ['guild_id', '456'],
            ['member', $this->mockMember([
                ['username', 'testuser'],
                ['id', '123'],
            ])],
        ]);

        $interaction->expects($this->once())->method('acknowledgeWithResponse')->willReturn(resolve(true));

        $interaction->expects($this->once())
            ->method('updateOriginalResponse')
            ->with($this->callback(function (MessageBuilder $message) {
                $message = json_decode(json_encode($message), true)['content'];
                return $message === 'No streamers are being watched!';
            }));

        $listStreamers = new ListStreamers($logger, $guildSettingsRepository);

        $commandHandler->addCommand($listStreamers);
        $listStreamers->execute($interaction);
    }
}
