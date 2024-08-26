<?php

namespace DiscordTwitchBot\Test\Command;

use Discord\Builders\MessageBuilder;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Command\AddStreamer;
use DiscordTwitchBot\Command\CommandHandler;
use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Command\Help;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use DiscordTwitchBot\Test\DiscordMocker;
use Psr\Log\NullLogger;

class HelpTest extends TestCase
{
    use DiscordMocker;

    public function testExecute(): void
    {
        $commandHandler = new CommandHandler();
        $logger         = new NullLogger();
        $help           = new Help($logger, $commandHandler);
        $testCommand    = new AddStreamer(
            $logger,
            $this->createMock(TwitchWrapper::class),
            $this->createMock(GuildSettingsRepository::class),
            $this->createMock(StreamerRepository::class),
        );

        $commandHandler->addCommand($help);
        $commandHandler->addCommand($testCommand);

        $interaction = $this->mockInteraction([
            ['member', $this->mockMember([
                ['username', 'testuser'],
                ['id', '123'],
            ])],
            ['guild', $this->mockGuild([
                ['name', 'testguild'],
                ['id', '456'],
            ])],
        ]);

        $interaction->expects($this->once())
            ->method('respondWithMessage')
            ->with($this->callback(function (MessageBuilder $message) {
                $data = json_decode(json_encode($message), true);
                return str_contains($data['content'], '`add <streamer> <channel> [custom message]`');
            }));

        $help->execute($interaction);
    }
}
