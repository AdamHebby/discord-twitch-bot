<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\Query\DiscordLiveEmbedQuery;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Psr\Log\LoggerInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class ListStreamers extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GuildSettingsRepository $guildSettingsRepository,
    ) {
    }

    public function getName(): string
    {
        return 'list';
    }

    public function getDescription(): string
    {
        return 'List all streamers being watched.';
    }

    /**
     * @return CommandOption[]
     */
    public function getOptions(): array
    {
        return [
            new CommandOption([
                'name'        => 'page',
                'description' => 'If there are too many streamers for a message, you can use this to paginate through them.',
                'type'        => Option::INTEGER,
                'required'    => false,
            ]),
        ];
    }

    public function execute(Interaction $interaction): PromiseInterface
    {
        return new Promise(function (callable $resolve, callable $reject) use ($interaction) {
            Benchmarker::getInstance()->start(__CLASS__);

            $settings = $this->guildSettingsRepository->load($interaction->guild_id);
            $streamers = $settings->getStreamers();

            if (empty($streamers)) {
                Benchmarker::getInstance()->killActive(__CLASS__);
                return $this->interactionReply($interaction, 'No streamers are being watched!');
            }

            $pageNumber = ($interaction->data->options['page']->value ?? 1) - 1;
            $messages = [];
            $pageFormat = "[Page %d/%d] Use the page option to see more streamers.";
            $message = '**Streamers being watched:**' . PHP_EOL;
            foreach ($streamers as $streamer) {
                $streamerChannel = $settings->getStreamerChannel($streamer);
                $streamerMessage = $settings->getStreamerMessage($streamer);

                $addOnMessage = sprintf(
                    '- `%s` in <#%s> with message: `%s`' . PHP_EOL,
                    $streamer,
                    $streamerChannel,
                    $streamerMessage ?? DiscordLiveEmbedQuery::DEFAULT_MSG
                );

                if (strlen($message . $addOnMessage . $pageFormat) > 2000) {
                    $messages[] = $message;
                    $message = '**Streamers being watched:**' . PHP_EOL . $addOnMessage;
                } else {
                    $message .= $addOnMessage;
                }
            }

            $messages[] = $message;

            foreach ($messages as $num => $message) {
                $messages[$num] .= sprintf($pageFormat, $num + 1, count($messages));
            }

            $this->logger->info(sprintf(
                'Streamers List shown to user %s (%s) in guild "%s" (%s)',
                $interaction->member->username,
                $interaction->member->id,
                $interaction->guild->name,
                $interaction->guild_id
            ));

            Benchmarker::getInstance()->stop(__CLASS__);
            return $this->interactionReply($interaction, $messages[$pageNumber] ?? $messages[count($messages) - 1]);
        });
    }
}
