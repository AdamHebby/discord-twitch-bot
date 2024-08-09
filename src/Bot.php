<?php

namespace DiscordTwitchBot;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\NotifyDiscordGuilds;
use DiscordTwitchBot\Application\Query\CheckRoleQuery;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Command\AddStreamer;
use DiscordTwitchBot\Command\CommandHandler;
use DiscordTwitchBot\Command\Help;
use DiscordTwitchBot\Command\ListStreamers;
use DiscordTwitchBot\Command\RemoveStreamer;
use DiscordTwitchBot\Command\Restart;
use DiscordTwitchBot\Command\RoleLimit;
use DiscordTwitchBot\Command\SetChannel;
use DiscordTwitchBot\Command\SetPrefix;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use Monolog\Logger;

class Bot
{
    private GuildSettingsRepository $guildSettingsRepository;
    private CommandHandler $commandHandler;
    private CheckRoleQuery $checkRoleQuery;

    public function __construct(
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchWrapper,
        private readonly Logger $logger,
    ) {
        $this->guildSettingsRepository = new GuildSettingsRepository(__DIR__ . '/../data/');
        $this->checkRoleQuery = new CheckRoleQuery($this->guildSettingsRepository);
        $this->commandHandler = new CommandHandler();

        $this->commandHandler->addCommands([
            new Restart($this->logger, $this->discord),
            new Help($this->logger, $this->discord),
            new AddStreamer(
                $this->logger,
                $this->discord,
                $this->twitchWrapper,
                $this->guildSettingsRepository,
                $this->checkRoleQuery,
            ),
            new RemoveStreamer(
                $this->logger,
                $this->discord,
                $this->twitchWrapper,
                $this->guildSettingsRepository,
                $this->checkRoleQuery,
            ),
            new SetPrefix(
                $this->logger,
                $this->discord,
                $this->twitchWrapper,
                $this->guildSettingsRepository,
                $this->checkRoleQuery,
            ),
            new SetChannel(
                $this->logger,
                $this->discord,
                $this->twitchWrapper,
                $this->guildSettingsRepository,
                $this->checkRoleQuery,
            ),
            new ListStreamers(
                $this->logger,
                $this->discord,
                $this->guildSettingsRepository
            ),
            new RoleLimit(
                $this->logger,
                $this->discord,
                $this->twitchWrapper,
                $this->guildSettingsRepository,
                $this->checkRoleQuery,
            ),
        ]);
    }

    public function run(): void
    {
        $this->discord->on('ready', function () {
            $this->logger->info('Bot is ready!');
        });

        $this->discord->on('message', function (Message $message) {
            try {
                $guild = $message->channel->guild_id;
                $guildSettings = $this->guildSettingsRepository->load($guild);
                $command = $this->commandHandler->parseCommand($guildSettings->getPrefix(), $message->content);

                if ($command !== null) {
                    $command->execute($message);
                }
            } catch (\Throwable $th) {
                $this->logger->error($th->getMessage());
            }
        });

        $notify = new NotifyDiscordGuilds(
            $this->logger,
            $this->discord,
            $this->twitchWrapper,
            $this->guildSettingsRepository
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
