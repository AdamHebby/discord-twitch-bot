<?php

namespace DiscordTwitchBot;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use DiscordTwitchBot\Application\NotifyDiscordGuilds;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Command\AddStreamer;
use DiscordTwitchBot\Command\CommandHandler;
use DiscordTwitchBot\Command\Help;
use DiscordTwitchBot\Command\ListStreamers;
use DiscordTwitchBot\Command\RemoveStreamer;
use DiscordTwitchBot\Command\SetChannel;
use DiscordTwitchBot\Command\SetPrefix;
use DiscordTwitchBot\Repository\GuildSettingsRepository;

class Bot
{
    private GuildSettingsRepository $guildSettingsRepository;
    private CommandHandler $commandHandler;

    public function __construct(
        private readonly Discord $discord,
        private readonly TwitchWrapper $twitchWrapper,
    ) {
        $this->guildSettingsRepository = new GuildSettingsRepository(__DIR__ . '/../data/');

        $this->commandHandler = new CommandHandler();
        $this->commandHandler->addCommands([
            new AddStreamer($this->discord, $this->twitchWrapper, $this->guildSettingsRepository),
            new RemoveStreamer($this->discord, $this->twitchWrapper, $this->guildSettingsRepository),
            new SetPrefix($this->discord, $this->twitchWrapper, $this->guildSettingsRepository),
            new SetChannel($this->discord, $this->twitchWrapper, $this->guildSettingsRepository),
            new Help($this->discord),
            new ListStreamers($this->discord, $this->guildSettingsRepository),
        ]);
    }

    public function run(): void
    {
        $this->discord->on('ready', function () {
            echo "Bot is ready!", PHP_EOL;
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
                echo $th->getMessage(), PHP_EOL;
            }
        });

        $notify = new NotifyDiscordGuilds($this->discord, $this->twitchWrapper, $this->guildSettingsRepository);
        $this->discord->getLoop()->addPeriodicTimer(60, function () use ($notify) {
            try {
                $notify->execute();
            } catch (\Throwable $th) {
                echo $th->getMessage(), PHP_EOL;
            }
        });

        $this->discord->getLoop()->addPeriodicTimer(5, function () {
            touch(__DIR__ . '/../lock');
        });

        $this->discord->run();
    }
}
