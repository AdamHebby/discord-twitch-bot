<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Interaction;
use DiscordTwitchBot\Application\Benchmarker;
use Psr\Log\LoggerInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

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

    public function execute(Interaction $interaction): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($interaction) {
            Benchmarker::getInstance()->start(__CLASS__);

            if ($interaction->guild_id === null || $interaction->guild === null) {
                Benchmarker::getInstance()->killActive(__CLASS__);
                $this->interactionReply($interaction, "This command can only be used in a server.")->then($resolve, $reject);
                return;
            }

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

            $this->interactionReply($interaction, $msg)->then(function() use ($interaction) {
                $this->logger->info(sprintf(
                    'Help message shown to user %s (%s) in guild "%s" (%s)',
                    $interaction->member?->username,
                    $interaction->member?->id,
                    $interaction->guild->name,
                    $interaction->guild_id
                ));

                Benchmarker::getInstance()->stop(__CLASS__);
            })->then($resolve, $reject);
        });
    }
}
