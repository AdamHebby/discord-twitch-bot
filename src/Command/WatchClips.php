<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Model\Streamer;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use Psr\Log\LoggerInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class WatchClips extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TwitchWrapper $twitchApi,
        private readonly StreamerRepository $streamerRepository,
        private readonly GuildSettingsRepository $guildSettingsRepository,
    ) {
    }

    public function getName(): string
    {
        return 'clipswatch';
    }

    public function getDescription(): string
    {
        return 'Watch for new clips for a particular streamer and post them in a channel.';
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
        ];
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

            // @phpstan-ignore-next-line - Options are guaranteed to be present due to command definition
            $options       = $interaction->data->options;
            $streamer      = strtolower($options['streamer']->value);
            $channelId     = $options['channel']->value;
            $streamerObj   = $this->streamerRepository->getStreamerByLogin($streamer);

            if ($streamerObj === null) {
                $streamerObj = $this->twitchApi->getStreamer($streamer);
            }

            if ($streamerObj === null) {
                Benchmarker::getInstance()->killActive(__CLASS__);
                $this->interactionReply($interaction, "Streamer `$streamer` does not exist.")->then($resolve, $reject);
                return;
            }

            $this->streamerRepository->add($streamerObj);

            $guildId             = $interaction->guild_id;
            $guildSettings       = $this->guildSettingsRepository->load($guildId);
            $existingClipChannel = $guildSettings->getClipChannel($streamer);

            if ($existingClipChannel && $existingClipChannel === $channelId) {
                Benchmarker::getInstance()->killActive(__CLASS__);
                $this->interactionReply($interaction, "Clips for streamer `$streamer` are already being posted in <#$existingClipChannel>.")->then($resolve, $reject);
                return;
            }

            if ($interaction->guild->channels->get('id', $channelId) === null) {
                Benchmarker::getInstance()->killActive(__CLASS__);
                $this->interactionReply($interaction, "Channel <#$channelId> not found.")->then($resolve, $reject);
                return;
            }

            $guildSettings->addClipChannel($streamer, $channelId);

            $this->guildSettingsRepository->save($guildSettings);

            $this->logger->info(sprintf(
                'Added clips channel "%s" to guild "%s" (%s) by user "%s" (%s)',
                $streamer,
                $interaction->guild->name,
                $interaction->guild_id,
                $interaction->member?->username,
                $interaction->member?->id
            ));

            Benchmarker::getInstance()->stop(__CLASS__);

            $this->interactionReply($interaction, "Clips for streamer `$streamer` will now be posted in <#$channelId>.")->then($resolve, $reject);
            return;
        });
    }
}
