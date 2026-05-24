<?php

namespace DiscordTwitchBot\Command;

use Discord\Builders\CommandBuilder;
use Discord\Builders\MessageBuilder;
use Discord\Discord;
use Discord\Parts\Interactions\Interaction;
use DiscordTwitchBot\Application\Benchmarker;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

abstract class Command
{
    abstract public function getName(): string;

    abstract public function getDescription(): string;

    /**
     * @return PromiseInterface<void>
     */
    abstract public function execute(Interaction $message): PromiseInterface;

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

    /**
     * @return PromiseInterface<void>
     */
    public function interactionReply(Interaction $interaction, string $message): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($interaction, $message) {
            Benchmarker::getInstance()->start('interactionReply');
            if ($interaction->isResponded()) {
                $interaction->updateOriginalResponse(MessageBuilder::new()->setContent($message))
                    ->then($resolve, $reject)
                    ->finally(function() {
                        Benchmarker::getInstance()->stop('interactionReply');
                    });

                return;
            }

            $interaction->respondWithMessage(MessageBuilder::new()->setContent($message))
                ->then($resolve, $reject)
                ->finally(function() {
                    Benchmarker::getInstance()->stop('interactionReply');
                });
        });
    }
}
