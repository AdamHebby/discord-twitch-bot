<?php

namespace DiscordTwitchBot;

use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Interaction;
use Discord\WebSockets\Event;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\PushoverNotifier;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Command\AddStreamer;
use DiscordTwitchBot\Command\CommandHandler;
use DiscordTwitchBot\Command\Help;
use DiscordTwitchBot\Command\ListClips;
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
            new WatchClips(
                $this->logger,
                $this->twitchWrapper,
                $this->streamerRepository,
                $this->guildSettingsRepository,
            ),
            new UnwatchClips(
                $this->logger,
                $this->guildSettingsRepository,
            ),
            new ListClips(
                $this->logger,
                $this->guildSettingsRepository,
            ),
        ]);
    }

    public function run(): void
    {
        $this->discord->on('init', function () {
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
            try {
                Benchmarker::getInstance()->start('Interaction: ' . $interaction->data->name);
                $interaction->acknowledge()->then(function () use ($interaction) {
                    $this->logger->debug('Interaction acknowledged: ' . $interaction->data->name);

                    $this->logger->info('Interaction received: /' . $interaction->data->name);
                    $this->commandHandler->handle($interaction);
                })->catch(function (\Throwable $e) use ($interaction) {
                    $this->logger->error('Failed to acknowledge interaction: ' . $interaction->data->name . ' - ' . $e->getMessage());
                })->finally(function () use ($interaction) {
                    Benchmarker::getInstance()->stop('Interaction: ' . $interaction->data->name);
                });
            } catch (\Throwable $th) {
                Benchmarker::getInstance()->killActive('Interaction: ' . $interaction->data->name);
                $this->logger->error($th->getMessage());
                $this->logger->error($th->getTraceAsString());
            }
        });

        $this->discord->on(Event::MESSAGE_CREATE, function (Message $message) {
            if ($message->content === '!sb restart' && $message->author->id === $_ENV['DISCORD_OWNER_ID']) {
                $message->react('👍')->then(function () {
                    sleep(1);
                    $this->logger->info('Restarting bot...');
                    $this->discord->close();
                    exit();
                })->catch(function (\Throwable $e) {
                    $this->logger->error($e);
                });
            }
        });

        $this->discord->on(Event::GUILD_CREATE, function (Guild $guild) {
            $this->logger->info("Joined a new guild: {$guild->name} (ID: {$guild->id})");

            $whitelist = $_ENV['DISCORD_GUILD_WHITELIST'];

            if (empty($whitelist)) {
                $this->logger->warning("Guild whitelist is empty.");
                return;
            }

            if (!in_array($guild->id, explode(',', $whitelist))) {
                $this->logger->warning("Guild {$guild->name} (ID: {$guild->id}) is not whitelisted.");
                $this->notifyPushover("Guild {$guild->name} (ID: {$guild->id}) is not whitelisted. Leaving...");
                $guild->leave()->then(
                    fn () => $this->logger->info("Left guild: {$guild->name} (ID: {$guild->id})")
                )->catch(
                    fn (\Throwable $e) => $this->logger->error("Failed to leave guild: {$guild->name} (ID: {$guild->id}) - " . $e->getMessage())
                );
                return;
            } else {
                $this->logger->info("Guild {$guild->name} (ID: {$guild->id}) is whitelisted.");
            }
        });

        $this->discord->getLoop()->addPeriodicTimer(60 * 5, function () {
            Benchmarker::writeToFile(__DIR__ . '/../benchmarks.txt');
        });

        $this->discord->getLoop()->addPeriodicTimer(15, function () {
            touch(__DIR__ . '/../lock');
        });

        $this->discord->run();
    }

    private function notifyPushover(string $message): void
    {
        PushoverNotifier::factory()->sendNotification($message);
    }
}
