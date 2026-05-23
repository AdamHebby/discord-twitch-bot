<?php

namespace DiscordTwitchBot\Test\Command;

use Discord\Builders\CommandBuilder;
use Discord\Discord;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Command\AddStreamer;
use DiscordTwitchBot\Command\CommandHandler;
use DiscordTwitchBot\Command\CommandOption;
use DiscordTwitchBot\Command\Help;
use DiscordTwitchBot\Command\ListStreamers;
use DiscordTwitchBot\Command\RemoveStreamer;
use DiscordTwitchBot\Command\UnwatchClips;
use DiscordTwitchBot\Command\WatchClips;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use DiscordTwitchBot\Test\DiscordMocker;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CommandTest extends TestCase
{
    use DiscordMocker;

    public function testBasicCommands(): void
    {
        $commandHandler = new CommandHandler();
        $commands = [
            new Help(new NullLogger(), $commandHandler),
            new AddStreamer(
                new NullLogger(),
                $this->createMock(TwitchWrapper::class),
                $this->createMock(GuildSettingsRepository::class),
                $this->createMock(StreamerRepository::class),
            ),
            new RemoveStreamer(
                new NullLogger(),
                $this->createMock(GuildSettingsRepository::class),
            ),
            new ListStreamers(
                new NullLogger(),
                $this->createMock(GuildSettingsRepository::class),
            ),
            new WatchClips(
                new NullLogger(),
                $this->createMock(TwitchWrapper::class),
                $this->createMock(StreamerRepository::class),
                $this->createMock(GuildSettingsRepository::class),
            ),
            new UnwatchClips(
                new NullLogger(),
                $this->createMock(GuildSettingsRepository::class),
            )
        ];

        $commandHandler->addCommands($commands);

        $discord = $this->mockDiscord();
        foreach ($commands as $command) {
            $this->assertNotEmpty($command->getName());
            $this->assertNotEmpty($command->getDescription());
            $options = $command->getOptions();

            $this->assertIsArray($options);
            $this->assertContainsOnlyInstancesOf(CommandOption::class, $options);
            $this->assertInstanceOf(CommandBuilder::class, $command->getCommand($discord));
        }

        $this->assertInstanceOf(Help::class, $commandHandler->getCommand('help'));
        $this->assertInstanceOf(AddStreamer::class, $commandHandler->getCommand('add'));
        $this->assertInstanceOf(RemoveStreamer::class, $commandHandler->getCommand('remove'));
        $this->assertInstanceOf(ListStreamers::class, $commandHandler->getCommand('list'));
    }
}
