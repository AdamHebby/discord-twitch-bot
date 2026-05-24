<?php

namespace DiscordTwitchBot\ClientWrapper;

use DateTimeImmutable;
use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Model\Stream;
use DiscordTwitchBot\Model\Streamer;
use DiscordTwitchBot\Model\TwitchClip;
use TwitchApi\HelixGuzzleClient;
use TwitchApi\TwitchApi;

class TwitchWrapper
{
    private ?string $bearerToken = null;

    public function __construct(private TwitchApi $twitchApi)
    {
    }

    public function checkStreamerExists(string $streamer): bool
    {
        Benchmarker::getInstance()->start(__CLASS__ . '::' . __FUNCTION__);

        try {
            $response = $this->twitchApi->getUsersApi()->getUserByUsername($this->getBearerToken(), $streamer);
            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new \Exception("Twitch API returned status code {$statusCode}");
            }

            $contents = json_decode($response->getBody()->getContents());
            $exists = !empty($contents->data) && $contents->data[0]->login === $streamer;
        } catch (\Throwable $th) {
            Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
            throw $th;
        }

        Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);

        return $exists;
    }

    public function getStreamer(string $streamerLogin): ?Streamer
    {
        Benchmarker::getInstance()->start(__CLASS__ . '::' . __FUNCTION__);

        $streamer = null;

        try {
            $response = $this->twitchApi->getUsersApi()->getUserByUsername($this->getBearerToken(), $streamerLogin);
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                throw new \Exception("Twitch API returned status code {$statusCode}");
            }

            $contents = json_decode($response->getBody()->getContents(), true);

            if (
                !empty($contents['data']) &&
                isset($contents['data'][0]['login']) &&
                $contents['data'][0]['login'] === $streamerLogin
            ) {
                $streamer = Streamer::fromJsonArray($contents['data'][0]);
            }
        } catch (\Throwable $th) {
            Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
            throw $th;
        }

        Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);

        return $streamer;
    }

    public function getStream(string $streamer): ?Stream
    {
        Benchmarker::getInstance()->start(__CLASS__ . '::' . __FUNCTION__);

        try {
            $response = $this->twitchApi->getStreamsApi()->getStreamForUsername($this->getBearerToken(), $streamer);
            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new \Exception("Twitch API returned status code {$statusCode}");
            }

            $contents = json_decode($response->getBody()->getContents(), true);

            if (empty($contents['data'])) {
                Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
                return null;
            }

            $stream = Stream::fromJsonArray($contents['data'][0]);
        } catch (\Throwable $th) {
            Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
            throw $th;
        }

        Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);

        return $stream;
    }

    /**
     * Get clips for a streamer between two dates
     *
     * @return TwitchClip[]
     */
    public function getClips(string $streamerId, DateTimeImmutable $started, DateTimeImmutable $ended): array
    {
        Benchmarker::getInstance()->start(__CLASS__ . '::' . __FUNCTION__);

        try {
            $response = $this->twitchApi->getClipsApi()->getClipsByBroadcasterId(
                $this->getBearerToken(),
                $streamerId,
                20,
                null,
                null,
                // API Docs state RFC3339 format but that 400s. This format works.
                $started->format('Y-m-d\TH:i:s\Z'),
                $ended->format('Y-m-d\TH:i:s\Z'),
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new \Exception("Twitch API returned status code {$statusCode}");
            }

            $contents = json_decode($response->getBody()->getContents(), true);

            if (empty($contents['data'])) {
                Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
                return [];
            }
        } catch (\Throwable $th) {
            Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
            throw $th;
        }

        Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);

        return array_map(static fn(array $clip) => TwitchClip::fromJsonArray($clip), $contents['data']);
    }

    /**
     * Get the top 500 games on Twitch
     *
     * @return array<string,string> id => name map
     */
    public function getTopGames(int $count = 500): array
    {
        Benchmarker::getInstance()->start(__CLASS__ . '::' . __FUNCTION__);

        $first = 100;
        $after = null;
        $games = [];

        try {
            for ($i = 0; $i < $count / $first; $i++) {
                $response = $this->twitchApi->getGamesApi()->getTopGames($this->getBearerToken(), $first, null, $after);
                $statusCode = $response->getStatusCode();

                if ($statusCode !== 200) {
                    throw new \Exception("Twitch API returned status code {$statusCode}");
                }

                $contents = json_decode($response->getBody()->getContents(), true);

                if (empty($contents['data'])) {
                    Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
                    return array_column($games, 'name', 'id');
                }

                $games = array_merge($contents['data'], $games);

                if ($contents['pagination']['cursor'] === null) {
                    break;
                }

                $after = $contents['pagination']['cursor'];
            }
        } catch (\Throwable $th) {
            Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
            throw $th;
        }

        Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
        return array_column($games, 'name', 'id');
    }

    /**
     * @param int $count
     *
     * @return array<array{id: string, name: string, box_art_url: string, igdb_id: string|null}>
     */
    public function getTopGamesData(int $count = 500): array
    {
        Benchmarker::getInstance()->start(__CLASS__ . '::' . __FUNCTION__);

        $first = 100;
        $after = null;
        $games = [];

        try {
            for ($i = 0; $i < $count / $first; $i++) {
                $response = $this->twitchApi->getGamesApi()->getTopGames($this->getBearerToken(), $first, null, $after);
                $statusCode = $response->getStatusCode();

                if ($statusCode !== 200) {
                    throw new \Exception("Twitch API returned status code {$statusCode}");
                }

                $contents = json_decode($response->getBody()->getContents(), true);

                if (empty($contents['data'])) {
                    Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
                    return $games;
                }

                $games = array_merge($contents['data'], $games);

                if ($contents['pagination']['cursor'] === null) {
                    break;
                }

                $after = $contents['pagination']['cursor'];
            }
        } catch (\Throwable $th) {
            Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
            throw $th;
        }

        Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
        return $games;
    }

    private function authorizeTwitchApi(): void
    {
        Benchmarker::getInstance()->start(__CLASS__ . '::' . __FUNCTION__);

        if ($this->bearerToken !== null) {
            Benchmarker::getInstance()->killActive(__CLASS__ . '::' . __FUNCTION__);
            return;
        }

        $oauth = $this->twitchApi->getOauthApi();

        $token = $oauth->getAppAccessToken(); // lasts for 60 days
        $statusCode = $token->getStatusCode();

        if ($statusCode !== 200) {
            throw new \Exception("Twitch API returned status code {$statusCode}");
        }

        $data = json_decode($token->getBody()->getContents());

        if (!isset($data->access_token) || empty($data->access_token)) {
            throw new \Exception("Failed to authorize Twitch API");
        }

        // Your bearer token
        $this->bearerToken = $data->access_token;

        Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
    }

    private function getBearerToken(): string
    {
        if ($this->bearerToken === null) {
            $this->authorizeTwitchApi();
        }

        if ($this->bearerToken === null) {
            throw new \Exception("Failed to authorize Twitch API");
        }

        return $this->bearerToken;
    }
}
