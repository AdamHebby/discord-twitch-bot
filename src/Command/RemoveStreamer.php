<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Psr\Log\LoggerInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class RemoveStreamer extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GuildSettingsRepository $guildSettingsRepository,
    ) {
    }

    public function getName(): string
    {
        return 'remove';
    }

    public function getDescription(): string
    {
        return 'Remove a streamer from being watched.';
    }

    public function getOptions(): array
    {
        return [
            new CommandOption([
                'name'        => 'streamer',
                'description' => 'The Twitch streamer to stop watching.',
                'type'        => Option::STRING,
                'required'    => true,
            ]),
        ];
    }

    public function execute(Interaction $interaction): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($interaction) {
            Benchmarker::getInstance()->start(__CLASS__);

            $streamer = $interaction->data->options['streamer']->value;
            $guildSettings = $this->guildSettingsRepository->load($interaction->guild_id);

            if ($guildSettings->hasStreamer($streamer) === false) {
                Benchmarker::getInstance()->killActive(__CLASS__);
                $this->interactionReply($interaction, "{$streamer} is not being watched!")->then($resolve, $reject);
                return;
            }

            $guildSettings->removeStreamer($streamer);
            $this->guildSettingsRepository->save($guildSettings);

            $this->logger->info(sprintf(
                'Removed streamer %s from guild "%s" (%s) by user "%s" (%s)',
                $streamer,
                $interaction->guild->name,
                $interaction->guild_id,
                $interaction->member->username,
                $interaction->member->id
            ));

            Benchmarker::getInstance()->stop(__CLASS__);

            $this->interactionReply($interaction, "{$streamer} is no longer being watched!")->then($resolve, $reject);
            return;
        });
    }
}
