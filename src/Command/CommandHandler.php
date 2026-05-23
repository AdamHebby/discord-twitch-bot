<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Interaction;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class CommandHandler
{
    /**
     * @var Command[]
     */
    private array $commands = [];

    public function addCommand(Command $command): void
    {
        $this->commands[$command->getName()] = $command;
    }

    /**
     * @param Command[] $commands
     */
    public function addCommands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }
    }

    /**
     * @return Command[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getCommand(string $commandName): ?Command
    {
        return $this->commands[$commandName] ?? null;
    }

    public function handle(Interaction $interaction): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($interaction) {
            $name = $interaction->data->name;
            $command = $this->getCommand($name);

            if ($command === null) {
                return $reject(new \Exception("Command not found: $name"));
            }

            $command->execute($interaction)->then(function() use ($resolve) {
                $resolve(true);
            }, function($error) use ($reject) {
                $reject($error);
            });
        });
    }
}
