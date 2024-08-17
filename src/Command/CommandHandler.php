<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Interaction;

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

    public function handle(Interaction $interaction): void
    {
        $name = $interaction->data->name;
        $command = $this->getCommand($name);

        if ($command === null) {
            return;
        }

        $command->execute($interaction);
    }
}
