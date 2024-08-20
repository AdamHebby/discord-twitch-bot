<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use Psr\Log\LoggerInterface;
use React\Promise\Promise;

class AddStreamer extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TwitchWrapper $twitchApi,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly StreamerRepository $streamerRepository,
    ) {
    }

    public function getName(): string
    {
        return 'add';
    }

    public function getDescription(): string
    {
        return 'Add a streamer to watch.';
    }

    /**
     * @return CommandOption[]
     */
    public function getOptions(): array
    {
        return [
            new CommandOption([
                'name'        => 'streamer',
                'description' => 'The Twitch streamer to watch.',
                'type'        => Option::STRING,
                'required'    => true,
            ]),
            new CommandOption([
                'name'        => 'channel',
                'description' => 'The Discord channel to post the streamer in.',
                'type'        => Option::CHANNEL,
                'required'    => true,
            ]),
            new CommandOption([
                'name'        => 'custom_message',
                'description' => 'A custom message to post when the streamer goes live. Use {name} and {game} for dynamic content.',
                'type'        => Option::STRING,
                'required'    => false,
            ]),
        ];
    }

    public function execute(Interaction $interaction): void
    {
        Benchmarker::getInstance()->start(__CLASS__);

        $options       = $interaction->data->options;
        $streamer      = strtolower($options['streamer']->value);
        $channel       = $options['channel']->value;
        $customMessage = $options['custom_message']?->value;
        $guildSettings = $this->guildSettingsRepository->load($interaction->guild_id);

        if (empty($customMessage) || $customMessage === null) {
            $customMessage = null;
        }

        $channel = $interaction->guild->channels->get('id', $channel);
        if ($channel === null) {
            $this->interactionReply($interaction, "I can't see the channel {$channel}.");
            return;
        }

        $alreadyWatching = $guildSettings->hasStreamer($streamer);

        if ($alreadyWatching) {
            $oldChannel = $guildSettings->getStreamerChannel($streamer);
            $oldMessage = $guildSettings->getStreamerMessage($streamer);

            if ($oldChannel === $channel->id && $oldMessage === $customMessage) {
                $this->interactionReply($interaction, "{$streamer} is already being watched in that channel with that message!");
                return;
            }
        }

        $streamerObject = $this->twitchApi->getStreamer($streamer);

        if ($streamerObject === null) {
            $this->interactionReply($interaction, "{$streamer} is not a valid Twitch streamer!");
            return;
        }

        $this->streamerRepository->add($streamerObject);

        $guildSettings->addStreamer($streamer, $customMessage, $channel->id);
        $this->guildSettingsRepository->save($guildSettings);

        if ($alreadyWatching) {
            $this->interactionReply($interaction, "{$streamer} is already being watched, but I've updated the channel and message!");
        } else {
            $this->interactionReply($interaction, "{$streamer} is now being watched!");
        }

        $this->logger->info(sprintf(
            'Added streamer %s to guild "%s" (%s) by user %s (%s) with custom message "%s"',
            $streamer,
            $interaction->guild->name,
            $interaction->guild_id,
            $interaction->member->username,
            $interaction->member->id,
            $customMessage
        ));

        Benchmarker::getInstance()->stop(__CLASS__);
    }
}
