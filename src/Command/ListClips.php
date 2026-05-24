<?php

namespace DiscordTwitchBot\Command;

use Discord\Parts\Interactions\Command\Option;
use Discord\Parts\Interactions\Interaction;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Psr\Log\LoggerInterface;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

class ListClips extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly GuildSettingsRepository $guildSettingsRepository,
    ) {
    }

    public function getName(): string
    {
        return 'clipslist';
    }

    public function getDescription(): string
    {
        return 'List all channels being watched for clips.';
    }

    /**
     * @return CommandOption[]
     */
    public function getOptions(): array
    {
        return [
            new CommandOption([
                'name'        => 'page',
                'description' => 'If there are too many channels for a message, you can use this to paginate through them.',
                'type'        => Option::INTEGER,
                'required'    => false,
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

            $settings = $this->guildSettingsRepository->load($interaction->guild_id);
            $channels = $settings->getClipChannels();

            if (empty($channels)) {
                Benchmarker::getInstance()->killActive(__CLASS__);
                $this->interactionReply($interaction, 'No channels are being watched!')->then($resolve, $reject);
                return;
            }

            $pageNumber = ($interaction->data->options['page']->value ?? 1) - 1;
            $messages = [];
            $pageFormat = "[Page %d/%d] Use the page option to see more channels.";
            $message = '**Channels being watched for clips:**' . PHP_EOL;
            foreach ($channels as $streamer => $channel) {
                $addOnMessage = sprintf(
                    '- `%s` posted in <#%s>' . PHP_EOL,
                    $streamer,
                    $channel,
                );

                if (strlen($message . $addOnMessage . $pageFormat) > 2000) {
                    $messages[] = $message;
                    $message = '**Channels being watched for clips:**' . PHP_EOL . $addOnMessage;
                } else {
                    $message .= $addOnMessage;
                }
            }

            $messages[] = $message;

            foreach ($messages as $num => $message) {
                $messages[$num] .= sprintf($pageFormat, $num + 1, count($messages));
            }

            $this->logger->info(sprintf(
                'Clips List shown to user %s (%s) in guild "%s" (%s)',
                $interaction->member?->username,
                $interaction->member?->id,
                $interaction->guild->name,
                $interaction->guild_id
            ));

            Benchmarker::getInstance()->stop(__CLASS__);

            $this->interactionReply($interaction, $messages[$pageNumber])->then($resolve, $reject);
            return;
        });
    }
}
