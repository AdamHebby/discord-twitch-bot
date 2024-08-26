<?php

namespace DiscordTwitchBot\Test\Command;

use Discord\Parts\Interactions\Interaction;
use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Command\CommandHandler;
use DiscordTwitchBot\Command\Help;
use DiscordTwitchBot\Test\DiscordMocker;

class CommandHandlerTest extends TestCase
{
    use DiscordMocker;

    public function testHandler(): void
    {
        $help = $this->createPartialMock(Help::class, ['execute']);
        $commandHandler = new CommandHandler();
        $commandHandler->addCommands([$help]);

        $help->expects($this->once())->method('execute');

        $this->assertCount(1, $commandHandler->getCommands());

        $helpData = new \stdClass();
        $helpData->name = 'help';

        $testData = new \stdClass();
        $testData->name = 'test';

        $interaction = $this->createMock(Interaction::class);
        $interaction->expects($this->once())->method('__get')->with('data')->willReturn($helpData);

        $commandHandler->handle($interaction);

        $interactionTwo = $this->createMock(Interaction::class);
        $interactionTwo->expects($this->once())->method('__get')->with('data')->willReturn($testData);
        $commandHandler->handle($interactionTwo);
    }
}
