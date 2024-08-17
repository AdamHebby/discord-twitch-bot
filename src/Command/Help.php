<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Interaction;
use DiscordTwitchBot\Application\Benchmarker;
use Psr\Log\LoggerInterface;

class Help extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly CommandHandler $commandHandler,
    ) {
    }

    public function getName(): string
    {
        return 'help';
    }

    public function getDescription(): string
    {
        return 'Show this message.';
    }

    public function getOptions(): array
    {
        return [];
    }

    public function execute(Interaction $interaction): void
    {
        Benchmarker::getInstance()->start(__CLASS__);

        $commands = $this->commandHandler->getCommands();
        $msg = "**Commands:**\n";

        foreach ($commands as $command) {
            $args = '';

            foreach ($command->getOptions() as $argument) {
                $name = str_replace('_', ' ', $argument->getName());

                if ($argument->isRequired()) {
                    $args .= " <{$name}>";
                } else {
                    $args .= " [{$name}]";
                }
            }

            $msg .= "- `{$command->getName()}{$args}` - {$command->getDescription()}\n";
        }

        $this->interactionReply($interaction, $msg);

        $this->logger->info(sprintf(
            'Help message shown to user %s (%s) in guild "%s" (%s)',
            $interaction->member->username,
            $interaction->member->id,
            $interaction->channel->guild->name,
            $interaction->channel->guild->id
        ));

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
