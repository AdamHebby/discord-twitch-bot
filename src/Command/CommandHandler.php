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

    /**
     * @return PromiseInterface<void>
     */
    public function handle(Interaction $interaction): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($interaction) {
            // @phpstan-ignore-next-line - name is guaranteed to be present due to command definition
            $name = $interaction->data->name;
            $command = $this->getCommand($name);

            if ($command === null) {
                $reject(new \Exception("Command not found: $name"));
                return;
            }

            $command->execute($interaction)->then(function() use ($resolve) {
                $resolve(true);
            }, function($error) use ($reject) {
                $reject($error);
            });
        });
    }
}
