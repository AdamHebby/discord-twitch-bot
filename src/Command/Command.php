<?php

namespace DiscordTwitchBot\Command;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Interaction;

abstract class Command
{
    abstract public function getName(): string;

    abstract public function getDescription(): string;

    abstract public function execute(Interaction $message): void;

    /**
     * @return CommandOption[]
     */
    abstract public function getOptions(): array;

    public function getCommand(Discord $discord): CommandBuilder
    {
        $command = CommandBuilder::new()
            ->setName($this->getName())
            ->setDescription($this->getDescription());

        foreach ($this->getOptions() as $option) {
            $command->addOption($option->toOption($discord));
        }

        return $command;
    }

    public function interactionReply(Interaction $interaction, string $message): void
    {
        $interaction->acknowledgeWithResponse()->then(function() use ($interaction, $message) {
            $interaction->updateOriginalResponse(MessageBuilder::new()->setContent($message));
        });
    }
}
