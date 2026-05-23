<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Psr\Log\LoggerInterface;

class UnwatchClips extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GuildSettingsRepository $guildSettingsRepository,
    ) {
    }

    public function getName(): string
    {
        return 'clipsunwatch';
    }

    public function getDescription(): string
    {
        return 'Stop watching for new clips for a particular streamer.';
    }

    /**
     * @return CommandOption[]
     */
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

    public function execute(Interaction $interaction): void
    {
        Benchmarker::getInstance()->start(__CLASS__);

        $options       = $interaction->data->options;
        $streamer      = strtolower($options['streamer']->value);
        $guildId       = $interaction->guild_id;
        $guildSettings = $this->guildSettingsRepository->load($guildId);

        if ($guildSettings->getClipChannel($streamer) === null) {
            $this->interactionReply($interaction, "Clips for streamer $streamer are not being posted.");
            Benchmarker::getInstance()->killActive(__CLASS__);
            return;
        }

        $guildSettings->removeClipChannel($streamer);

        $this->guildSettingsRepository->save($guildSettings);

        $this->interactionReply($interaction, "Clips for streamer $streamer will no longer be posted.");

        $this->logger->info(sprintf(
            'Removed clip channel for streamer %s from guild "%s" (%s) by user "%s" (%s)',
            $streamer,
            $interaction->guild->name,
            $interaction->guild_id,
            $interaction->member->username,
            $interaction->member->id,
        ));

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
