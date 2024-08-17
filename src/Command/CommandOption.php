<?php

namespace DiscordTwitchBot\Command;

use Discord\Discord;
use Discord\Parts\Interactions\Command\Option;

class CommandOption
{
    /**
     * @param array{
     *   type: int,
     *   name: string,
     *   name_localizations: array,
     *   description: string,
     *   description_localizations: array,
     *   required: bool,
     *   choices: array,
     *   options: string,
     *   channel_types: array,
     *   min_value: int,
     *   max_value: int,
     *   min_length: int,
     *   max_length: int,
     *   autocomplete: bool,
     * } $data
     */
    public function __construct(public array $data)
    {
    }

    public function toOption(Discord $discord): Option
    {
        return new Option($discord, $this->data);
    }

    public function getName(): string
    {
        return $this->data['name'];
    }

    public function getDescription(): string
    {
        return $this->data['description'];
    }

    public function isRequired(): bool
    {
        return $this->data['required'];
    }
}