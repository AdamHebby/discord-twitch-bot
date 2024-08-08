<?php

namespace DiscordTwitchBot\ClientWrapper;

use DiscordTwitchBot\Model\Stream;
use Exception;
use TwitchApi\HelixGuzzleClient;
use TwitchApi\TwitchApi;

class TwitchWrapper
{
    private TwitchApi $twitchApi;
    private self $instance;
    private ?string $bearerToken = null;

    public function __construct(
        private string $clientId,
        private string $clientSecret,
    ) {
        $helixGuzzleClient = new HelixGuzzleClient($clientId);
        $this->twitchApi = new TwitchApi($helixGuzzleClient, $clientId, $clientSecret);
    }

    public function getInstance(): self
    {
        if ($this->instance === null) {
            $this->instance = new self($this->clientId, $this->clientSecret);
        }

        return $this->instance;
    }

    public function checkStreamerExists(string $streamer): bool
    {
        $this->authorizeTwitchApi();

        $response = $this->twitchApi->getUsersApi()->getUserByUsername($this->bearerToken, $streamer);
        $contents = json_decode($response->getBody()->getContents());

        return $response->getStatusCode() === 200 &&
            !empty($contents->data) &&
            $contents->data[0]->login === $streamer;
    }

    public function getStream(string $streamer): ?Stream
    {
        $this->authorizeTwitchApi();

        $response = $this->twitchApi->getStreamsApi()->getStreamForUsername($this->bearerToken, $streamer);
        $contents = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 200 || empty($contents['data'])) {
            return null;
        }

        return Stream::fromJsonArray($contents['data'][0]);
    }

    private function authorizeTwitchApi(): void
    {
        if ($this->bearerToken !== null) {
            return;
        }

        $oauth = $this->twitchApi->getOauthApi();

        $token = $oauth->getAppAccessToken($twitch_scopes ?? '');
        $data = json_decode($token->getBody()->getContents());

        // Your bearer token
        $this->bearerToken = $data->access_token ?? null;
    }
}
