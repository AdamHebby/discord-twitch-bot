<?php

namespace DiscordTwitchBot;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\NotifyDiscordClips;
use DiscordTwitchBot\Application\NotifyDiscordLiveStreams;
use DiscordTwitchBot\Application\Query\DiscordClipEmbedQuery;
use DiscordTwitchBot\Application\Query\DiscordLiveEmbedQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Command\AddStreamer;
use DiscordTwitchBot\Command\CommandHandler;
use DiscordTwitchBot\Command\Help;
use DiscordTwitchBot\Command\ListStreamers;
use DiscordTwitchBot\Command\RemoveStreamer;
use DiscordTwitchBot\Command\UnwatchClips;
use DiscordTwitchBot\Command\WatchClips;
use DiscordTwitchBot\Repository\GameRepository;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\NotifiedClipsRepository;
use DiscordTwitchBot\Repository\NotifiedStreamsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use Psr\Log\LoggerInterface;

class Bot
{
    private CommandHandler $commandHandler;

    public function __construct(
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchWrapper,
        private readonly LoggerInterface $logger,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly NotifiedStreamsRepository $notifiedStreamsRepository,
        private readonly StreamerRepository $streamerRepository,
        private readonly GameRepository $gameRepository,
        private readonly NotifiedClipsRepository $notifiedClipsRepository,
    ) {
        $this->commandHandler = new CommandHandler();

        $this->commandHandler->addCommands([
            new Help($this->logger, $this->commandHandler),
            new AddStreamer(
                $this->logger,
                $this->twitchWrapper,
                $this->guildSettingsRepository,
                $this->streamerRepository,
            ),
            new RemoveStreamer(
                $this->logger,
                $this->guildSettingsRepository,
            ),
            new ListStreamers(
                $this->logger,
                $this->guildSettingsRepository
            ),
            new WatchClips(
                $this->logger,
                $this->twitchWrapper,
                $this->streamerRepository,
                $this->guildSettingsRepository,
            ),
            new UnwatchClips(
                $this->logger,
                $this->guildSettingsRepository,
            )
        ]);
    }

    public function run(): void
    {
        $this->discord->on('ready', function () {
            foreach ($this->commandHandler->getCommands() as $cmd) {
                $this->discord->application->commands->save(
                    $this->discord->application->commands->create(
                        $cmd->getCommand($this->discord)->toArray()
                    )
                );

                $this->logger->info("Command /{$cmd->getName()} registered");
            }

            $this->logger->info('Bot is ready!');
        });

        $this->discord->on(Event::INTERACTION_CREATE, function (Interaction $interaction) {
            $this->logger->info('Interaction received: /' . $interaction->data->name);
            $this->commandHandler->handle($interaction);
        });

        $this->discord->on(Event::MESSAGE_CREATE, function (Message $message) {
            if ($message->content === '!sb restart' && $message->author->id === $_ENV['DISCORD_OWNER_ID']) {
                $message->react('👍')->done(function () {
                    sleep(1);
                    $this->logger->info('Restarting bot...');
                    $this->discord->close();
                    exit();
                });
            }
        });

        $notify = new NotifyDiscordLiveStreams(
            $this->logger,
            $this->discord,
            $this->twitchWrapper,
            $this->guildSettingsRepository,
            $this->notifiedStreamsRepository,
            $this->streamerRepository,
            new DiscordLiveEmbedQuery($this->discord),
            $this->gameRepository
        );

        $clipNotify = new NotifyDiscordClips(
            $this->logger,
            $this->discord,
            $this->twitchWrapper,
            $this->guildSettingsRepository,
            $this->streamerRepository,
            new DiscordClipEmbedQuery($this->discord, $this->gameRepository, $this->streamerRepository),
            $this->notifiedClipsRepository
        );

        $this->discord->getLoop()->addPeriodicTimer(60, function () use ($notify) {
            try {
                $notify->execute();
            } catch (\Throwable $th) {
                $this->logger->error($th->getMessage());
                $this->logger->error($th->getTraceAsString());
            }
        });

        $this->discord->getLoop()->addPeriodicTimer(60 * 5, function () use ($clipNotify) {
            try {
                $clipNotify->execute();
            } catch (\Throwable $th) {
                $this->logger->error($th->getMessage());
                $this->logger->error($th->getTraceAsString());
            }
        });

        $this->discord->getLoop()->addPeriodicTimer(15, function () {
            touch(__DIR__ . '/../lock');
        });

        $this->discord->run();
    }
}
