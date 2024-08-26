<?php

namespace DiscordTwitchBot\Test\ClientWrapper;

use PHPUnit\Framework\TestCase;
use DiscordTwitchBot\ClientWrapper\TwitchWrapper;
use DiscordTwitchBot\Model\Stream;
use DiscordTwitchBot\Model\Streamer;
use DiscordTwitchBot\Model\TwitchClip;
use PHPUnit\Framework\MockObject\MockObject;
use TwitchApi\Auth\OauthApi;
use TwitchApi\Resources\UsersApi;
use TwitchApi\TwitchApi;

class TwitchWrapperTest extends TestCase
{
    public function testCheckStreamerExists(): void
    {
        $twitchApiMock = $this->createMock(\TwitchApi\TwitchApi::class);

        $this->authorize($twitchApiMock);

        $twitchApiMock->expects($this->once())->method('getUsersApi')->willReturn(
            $this->createConfiguredMock(UsersApi::class, [
                'getUserByUsername' => $this->mockResponse(200, json_encode([
                    'data' => [$this->fakeStreamerData()],
                ])),
            ])
        );

        $twitchWrapper = new TwitchWrapper($twitchApiMock);
        $this->assertTrue($twitchWrapper->checkStreamerExists('streamer'));
    }

    public function testGetStreamer(): void
    {
        $twitchApiMock = $this->createMock(\TwitchApi\TwitchApi::class);

        $this->authorize($twitchApiMock);

        $twitchApiMock->expects($this->once())->method('getUsersApi')->willReturn(
            $this->createConfiguredMock(UsersApi::class, [
                'getUserByUsername' => $this->mockResponse(200, json_encode([
                    'data' => [$this->fakeStreamerData()],
                ])),
            ])
        );

        $twitchWrapper = new TwitchWrapper($twitchApiMock);
        $streamer = $twitchWrapper->getStreamer('streamer');

        $this->assertNotNull($streamer);
        $this->assertInstanceOf(Streamer::class, $streamer);

        $this->assertSame('123', $streamer->id);
        $this->assertSame('streamer', $streamer->login);
    }

    public function testGetStreamerReturnsNullWhenNotFound(): void
    {
        $twitchApiMock = $this->createMock(\TwitchApi\TwitchApi::class);

        $this->authorize($twitchApiMock);

        $twitchApiMock->expects($this->once())->method('getUsersApi')->willReturn(
            $this->createConfiguredMock(UsersApi::class, [
                'getUserByUsername' => $this->mockResponse(200, json_encode([
                    'data' => [],
                ])),
            ])
        );

        $twitchWrapper = new TwitchWrapper($twitchApiMock);
        $streamer = $twitchWrapper->getStreamer('streamer');

        $this->assertNull($streamer);
    }

    public function testGetStream(): void
    {
        $twitchApiMock = $this->createMock(\TwitchApi\TwitchApi::class);

        $this->authorize($twitchApiMock);

        $twitchApiMock->expects($this->once())->method('getStreamsApi')->willReturn(
            $this->createConfiguredMock(\TwitchApi\Resources\StreamsApi::class, [
                'getStreamForUsername' => $this->mockResponse(200, json_encode([
                    'data' => [$this->fakeStreamData()],
                ])),
            ])
        );

        $twitchWrapper = new TwitchWrapper($twitchApiMock);
        $stream = $twitchWrapper->getStream('streamer');

        $this->assertNotNull($stream);
        $this->assertInstanceOf(Stream::class, $stream);

        $this->assertSame('123', $stream->id);
        $this->assertSame('user_login', $stream->userLogin);
    }

    public function testGetStreamReturnsNullWhenNotFound(): void
    {
        $twitchApiMock = $this->createMock(\TwitchApi\TwitchApi::class);

        $this->authorize($twitchApiMock);

        $twitchApiMock->expects($this->once())->method('getStreamsApi')->willReturn(
            $this->createConfiguredMock(\TwitchApi\Resources\StreamsApi::class, [
                'getStreamForUsername' => $this->mockResponse(200, json_encode([
                    'data' => [],
                ])),
            ])
        );

        $twitchWrapper = new TwitchWrapper($twitchApiMock);
        $stream = $twitchWrapper->getStream('streamer');

        $this->assertNull($stream);
    }

    public function testGetClips(): void
    {
        $twitchApiMock = $this->createMock(\TwitchApi\TwitchApi::class);

        $this->authorize($twitchApiMock);

        $twitchApiMock->expects($this->once())->method('getClipsApi')->willReturn(
            $this->createConfiguredMock(\TwitchApi\Resources\ClipsApi::class, [
                'getClipsByBroadcasterId' => $this->mockResponse(200, json_encode([
                    'data' => [
                        $this->fakeClip('1'),
                        $this->fakeClip('2'),
                        $this->fakeClip('3'),
                    ],
                ])),
            ])
        );

        $twitchWrapper = new TwitchWrapper($twitchApiMock);
        $dateStart = new \DateTimeImmutable('2024-01-01 00:00:00');
        $dateEnd = new \DateTimeImmutable('2024-01-02 00:00:00');
        $clips = $twitchWrapper->getClips('123', $dateStart, $dateEnd);

        $this->assertNotEmpty($clips);
        $this->assertContainsOnlyInstancesOf(TwitchClip::class, $clips);
        $this->assertCount(3, $clips);

        $this->assertSame('1', $clips[0]->id);
        $this->assertSame('2', $clips[1]->id);
        $this->assertSame('3', $clips[2]->id);
    }

    public function testGetClipsReturnsEmptyArrayWhenNotFound(): void
    {
        $twitchApiMock = $this->createMock(\TwitchApi\TwitchApi::class);

        $this->authorize($twitchApiMock);

        $twitchApiMock->expects($this->once())->method('getClipsApi')->willReturn(
            $this->createConfiguredMock(\TwitchApi\Resources\ClipsApi::class, [
                'getClipsByBroadcasterId' => $this->mockResponse(200, json_encode([
                    'data' => [],
                ])),
            ])
        );

        $twitchWrapper = new TwitchWrapper($twitchApiMock);
        $dateStart = new \DateTimeImmutable('2024-01-01 00:00:00');
        $dateEnd = new \DateTimeImmutable('2024-01-02 00:00:00');
        $clips = $twitchWrapper->getClips('123', $dateStart, $dateEnd);

        $this->assertEmpty($clips);
    }

    public function testGetClipsPassesDatesCorrectlyToApi(): void
    {
        $twitchApiMock = $this->createMock(\TwitchApi\TwitchApi::class);

        $this->authorize($twitchApiMock);
        $clipsApi = $this->createMock(\TwitchApi\Resources\ClipsApi::class);
        $clipsApi->expects($this->once())->method('getClipsByBroadcasterId')->with(
            'token',
            '123',
            $this->anything(),
            $this->anything(),
            $this->anything(),
            '2024-01-01T00:00:00Z',
            '2024-01-02T00:00:00Z',
        )->willReturn(
            $this->mockResponse(200, json_encode([
                'data' => [],
            ]))
        );

        $twitchApiMock->expects($this->once())->method('getClipsApi')->willReturn($clipsApi);

        $twitchWrapper = new TwitchWrapper($twitchApiMock);
        $dateStart = new \DateTimeImmutable('2024-01-01 00:00:00');
        $dateEnd = new \DateTimeImmutable('2024-01-02 00:00:00');
        $clips = $twitchWrapper->getClips('123', $dateStart, $dateEnd);

        $this->assertEmpty($clips);
    }

    public function testAuthorizeTwitchApiDoesNotRequestTokenTwice(): void
    {
        $twitchApiMock = $this->createMock(\TwitchApi\TwitchApi::class);
        $twitchApiMock->expects($this->once())->method('getOauthApi')->willReturn(
            $this->createConfiguredMock(OauthApi::class, [
                'getAppAccessToken' => $this->mockResponse(200, json_encode([
                    'access_token' => 'token',
                ])),
            ])
        );

        $twitchApiMock->expects($this->exactly(2))->method('getUsersApi')->willReturn(
            $this->createConfiguredMock(UsersApi::class, [
                'getUserByUsername' => $this->mockResponse(200, json_encode([
                    'data' => [],
                ])),
            ])
        );

        $twitchWrapper = new TwitchWrapper($twitchApiMock);
        $twitchWrapper->getStreamer('streamer');
        $twitchWrapper->getStreamer('streamer');
    }

    private function mockResponse(int $statusCode, string $body): \Psr\Http\Message\ResponseInterface
    {
        $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn(
            $this->createConfiguredMock(\Psr\Http\Message\StreamInterface::class, [
                'getContents' => $body,
            ])
        );

        return $response;
    }

    private function authorize(TwitchApi&MockObject $twitchApi): void
    {
        $twitchApi->expects($this->once())->method('getOauthApi')->willReturn(
            $this->createConfiguredMock(OauthApi::class, [
                'getAppAccessToken' => $this->mockResponse(200, json_encode([
                    'access_token' => 'token',
                ])),
            ])
        );
    }

    private function fakeStreamerData(): array
    {
        return [
            'id' => '123',
            'login' => 'streamer',
            'display_name' => 'streamer',
            'type' => 'unknown',
            'broadcaster_type' => 'affiliate',
            'description' => 'Test',
            'profile_image_url' => 'https://example.com/profile.jpg',
            'offline_image_url' => 'https://example.com/offline.jpg',
            'view_count' => 100,
            'created_at' => '2024-01-01T00:00:00+00:00',
        ];
    }

    private function fakeStreamData(): array
    {
        return [
            "id" => "123",
            "user_id" => "456",
            "user_login" => "user_login",
            "user_name" => "user_name",
            "game_id" => "789",
            "game_name" => "game_name",
            "type" => "live",
            "title" => "title",
            "tags" => [],
            "viewer_count" => 100,
            "started_at" => "2024-01-01T00:00:00+00:00",
            "language" => "en",
            "thumbnail_url" => "https://example.com/{width}x{height}.jpg",
            "tag_ids" => [],
            "is_mature" => false,
        ];
    }

    private function fakeClip(string $id): array
    {
        return [
            "id" => $id,
            "url" => 'https://clips.twitch.tv/' . $id,
            "embed_url" => 'https://clips.twitch.tv/embed?clip=' . $id,
            "broadcaster_id" => '123',
            "broadcaster_name" => 'streamer',
            "creator_id" => '456',
            "creator_name" => 'creator',
            "video_id" => '',
            "game_id" => '19030',
            "language" => 'en',
            "title" => 'Clip Title',
            "view_count" => 123,
            "created_at" => '2024-08-20T19:12:14Z',
            "thumbnail_url" => 'https://example.com/1920x1080.jpg',
            "duration" => 12.1,
            "vod_offset" => null,
            "is_featured" => false,
        ];
    }
}
