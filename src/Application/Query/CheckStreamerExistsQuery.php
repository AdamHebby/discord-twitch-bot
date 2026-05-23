<?php

namespace DiscordTwitchBot\Application\Query;

use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Application\PushoverNotifier;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Model\GuildSettings;
use DiscordTwitchBot\Repository\GuildSettingsRepository;
use DiscordTwitchBot\Repository\StreamerRepository;
use Psr\Log\LoggerInterface;

class CheckStreamerExistsQuery
{
    /**
     * @var array<string,bool> $streamersCache
     */
    private array $streamersCache = [];

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly StreamerRepository $streamerRepository,
        private readonly GuildSettingsRepository $guildSettingsRepository,
        private readonly TwitchWrapper $twitchWrapper,
        private readonly PushoverNotifier $pushoverNotifier,
    ) {
    }

    public function check(GuildSettings $guildSettings, string $streamerLogin): bool
    {
        if (isset($this->streamersCache[$streamerLogin])) {
            return $this->streamersCache[$streamerLogin];
        }

        Benchmarker::getInstance()->start(__CLASS__);

        $exists  = false;

        try {
            $exists = $this->twitchWrapper->checkStreamerExists($streamerLogin);
        } catch (\Throwable $th) {
            $this->logger->error("Error checking if streamer {$streamerLogin} exists: " . $th->getMessage());
            Benchmarker::getInstance()->stop(__CLASS__);
            return false;
        }

        if ($exists === false) {
            $this->streamerRepository->removeByLogin($streamerLogin);
            $guildSettings->removeClipChannel($streamerLogin);
            $guildSettings->removeStreamer($streamerLogin);
            $this->guildSettingsRepository->save($guildSettings);
            $this->logger->error("Streamer {$streamerLogin} does not exist. Removed.");

            $this->pushoverNotifier->sendNotification("Streamer {$streamerLogin} does not exist in guild {$guildSettings->getGuildId()}.");

            Benchmarker::getInstance()->stop(__CLASS__);

            $this->streamersCache[$streamerLogin] = false;
            return false;
        }

        $this->streamersCache[$streamerLogin] = true;
        Benchmarker::getInstance()->stop(__CLASS__);
        return true;
    }
}
