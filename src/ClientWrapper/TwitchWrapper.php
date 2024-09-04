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
        $this->authorizeTwitchApi();

        $response = $this->twitchApi->getUsersApi()->getUserByUsername($this->bearerToken, $streamer);
        $contents = json_decode($response->getBody()->getContents());

        $exists = $response->getStatusCode() === 200 &&
            !empty($contents->data) &&
            $contents->data[0]->login === $streamer;

        Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);

        return $exists;
    }

    public function getStreamer(string $streamerLogin): ?Streamer
    {
        Benchmarker::getInstance()->start(__CLASS__ . '::' . __FUNCTION__);
        $this->authorizeTwitchApi();

        $response = $this->twitchApi->getUsersApi()->getUserByUsername($this->bearerToken, $streamerLogin);
        $contents = json_decode($response->getBody()->getContents(), true);
        $streamer = null;

        if (
            $response->getStatusCode() === 200 &&
            !empty($contents['data']) &&
            isset($contents['data'][0]['login']) &&
            $contents['data'][0]['login'] === $streamerLogin
        ) {
            $streamer = Streamer::fromJsonArray($contents['data'][0]);
        }

        Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);

        return $streamer;
    }

    public function getStream(string $streamer): ?Stream
    {
        Benchmarker::getInstance()->start(__CLASS__ . '::' . __FUNCTION__);

        $this->authorizeTwitchApi();

        $response = $this->twitchApi->getStreamsApi()->getStreamForUsername($this->bearerToken, $streamer);
        $contents = json_decode($response->getBody()->getContents(), true);

        if (empty($contents['data'])) {
            Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
            return null;
        }

        $stream = Stream::fromJsonArray($contents['data'][0]);

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

        $this->authorizeTwitchApi();

        $response = $this->twitchApi->getClipsApi()->getClipsByBroadcasterId(
            $this->bearerToken,
            $streamerId,
            20,
            null,
            null,
            // API Docs state RFC3339 format but that 400s. This format works.
            $started->format('Y-m-d\TH:i:s\Z'),
            $ended->format('Y-m-d\TH:i:s\Z'),
        );

        $contents = json_decode($response->getBody()->getContents(), true);

        if (empty($contents['data'])) {
            Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
            return [];
        }

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

        $this->authorizeTwitchApi();

        $first = 100;
        $after = null;
        $games = [];

        for ($i = 0; $i < $count / $first; $i++) {
            $response = $this->twitchApi->getGamesApi()->getTopGames($this->bearerToken, $first, null, $after);
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

        Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
        return array_column($games, 'name', 'id');
    }

    private function authorizeTwitchApi(): void
    {
        Benchmarker::getInstance()->start(__CLASS__ . '::' . __FUNCTION__);

        if ($this->bearerToken !== null) {
            Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
            return;
        }

        $oauth = $this->twitchApi->getOauthApi();

        $token = $oauth->getAppAccessToken();
        $data = json_decode($token->getBody()->getContents());

        // Your bearer token
        $this->bearerToken = $data->access_token ?? null;

        Benchmarker::getInstance()->stop(__CLASS__ . '::' . __FUNCTION__);
    }
}
