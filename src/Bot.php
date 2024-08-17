<?php

namespace DiscordTwitchBot;

use Discord\Discord;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\NotifyDiscordGuilds;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Command\AddStreamer;
use DiscordTwitchBot\Command\CommandHandler;
use DiscordTwitchBot\Command\Help;
use DiscordTwitchBot\Command\ListStreamers;
use DiscordTwitchBot\Command\RemoveStreamer;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
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
        ]);
    }

    public function run(): void
    {
        $this->discord->on('ready', function (Discord $discord) {
            foreach ($this->commandHandler->getCommands() as $cmd) {
                $this->discord->application->commands->save(
                    $this->discord->application->commands->create(
                        $cmd->getCommand($this->discord)->toArray()
                    )
                );
            }

            $this->logger->info('Bot is ready!');
        });

        $this->discord->on(Event::INTERACTION_CREATE, function (Interaction $interaction) {
            $this->commandHandler->handle($interaction);
        });

        $notify = new NotifyDiscordGuilds(
            $this->logger,
            $this->discord,
            $this->twitchWrapper,
            $this->guildSettingsRepository,
            $this->notifiedStreamsRepository,
            $this->streamerRepository,
        );

        $this->discord->getLoop()->addPeriodicTimer(60, function () use ($notify) {
            try {
                $notify->execute();
            } catch (\Throwable $th) {
                $this->logger->error($th->getMessage());
            }
        });

        $this->discord->getLoop()->addPeriodicTimer(15, function () {
            touch(__DIR__ . '/../lock');
        });

        $this->discord->getLoop()->addPeriodicTimer(60 * 15, function () {
            $avgBenches = Benchmarker::getInstance()->avgAll();

            if ($avgBenches !== []) {
                foreach ($avgBenches as $key => $value) {
                    $this->logger->info("AVG time: {$key}: {$value}ms", $avgBenches);
                }
            }

            Benchmarker::getInstance()->reset();
        });

        $this->discord->run();
    }
}
