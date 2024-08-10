<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Channel\Message;

abstract class Command
{
    abstract public function getName(): string;
    // abstract public function getDescription(): string;

    abstract public function execute(Message $message): void;

    /**
     * @return string[]
     */
    public static function getArgs(Message $message): array
    {
        $args = explode(' ', $message->content);
        $args = array_map('trim', $args);
        $args = array_filter($args);
        $args = array_slice($args, 2);
        $args = array_values($args);

        return $args;
    }
}
