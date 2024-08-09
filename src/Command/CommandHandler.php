<?php

namespace DiscordTwitchBot\Command;

class CommandHandler
{
    /**
     * @var Command[]
     */
    private array $commands = [];

    public function addCommand(Command $command): void
    {
        $this->commands[] = $command;
    }

    public function addCommands(array $commands): void
    {
        foreach ($commands as $command) {
            $this->addCommand($command);
        }
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getCommand(string $commandName): ?Command
    {
        foreach ($this->commands as $command) {
            if ($command->getName() === $commandName) {
                return $command;
            }
        }

        return null;
    }

    public function parseCommand(string $prefix, string $message): ?Command
    {
        if (strpos(trim($message), trim($prefix)) !== 0) {
            return null;
        }

        $parts = explode(' ', trim($message));

        if (count($parts) < 2) {
            return null;
        }

        return $this->getCommand($parts[1]);
    }
}
