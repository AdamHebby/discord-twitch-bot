<?php

namespace DiscordTwitchBot\ClientWrapper;

use DiscordTwitchBot\Application\Benchmarker;
use DiscordTwitchBot\Model\Stream;
use TwitchApi\HelixGuzzleClient;
use TwitchApi\TwitchApi;

class TwitchWrapper
{
    private TwitchApi $twitchApi;
    private ?string $bearerToken = null;

    public function __construct(
        private string $clientId,
        private string $clientSecret,
    ) {
        $helixGuzzleClient = new HelixGuzzleClient($this->clientId);
        $this->twitchApi   = new TwitchApi($helixGuzzleClient, $this->clientId, $this->clientSecret);
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
