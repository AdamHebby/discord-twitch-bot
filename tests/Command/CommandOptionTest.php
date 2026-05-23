<?php

namespace DiscordTwitchBot\Test\Command;

use Discord\Discord;
use Discord\Parts\Interactions\Command\Option;
use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\Command\CommandOption;
use DiscordTwitchBot\Test\DiscordMocker;

class CommandOptionTest extends TestCase
{
    use DiscordMocker;

    public function testCommandOption(): void
    {
        $data = [
            'name'        => 'channel',
            'description' => 'The Discord channel to post the streamer in.',
            'type'        => Option::CHANNEL,
            'required'    => true,
        ];

        $commandOption = new CommandOption($data);
        $discord       = $this->mockDiscord();
        $option        = $commandOption->toOption($discord);
        $optionData    = $option->getPublicAttributes();

        $this->assertSame($data['name'], $commandOption->getName());
        $this->assertSame($data['description'], $commandOption->getDescription());
        $this->assertSame($data['required'], $commandOption->isRequired());

        $this->assertInstanceOf(Option::class, $option);
        $this->assertSame(
            [
                'name'        => $optionData['name'],
                'description' => $optionData['description'],
                'type'        => $optionData['type'],
                'required'    => $optionData['required'],
            ],
            $data
        );
    }
}
