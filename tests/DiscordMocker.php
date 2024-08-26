<?php

namespace DiscordTwitchBot\Test;

use Discord\Parts\Channel\Channel;
use Discord\Parts\Guild\Guild;
use Discord\Parts\Interactions\Interaction;
use Discord\Parts\User\Member;
use Discord\Repository\Guild\ChannelRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

trait DiscordMocker
{
    public function mockMember(array $data): MockObject&Member
    {
        $member = $this->createMock(Member::class);

        $member
            ->method('__get')
            ->withAnyParameters()
            ->willReturnMap($data);

        return $member;
    }

    public function mockGuild(array $data): MockObject&Guild
    {
        $guild = $this->createMock(Guild::class);

        $guild
            ->method('__get')
            ->withAnyParameters()
            ->willReturnMap($data);

        return $guild;
    }

    public function mockInteraction(array $data): MockObject&Interaction
    {
        $interaction = $this->createMock(Interaction::class);

        $interaction
            ->method('__get')
            ->withAnyParameters()
            ->willReturnMap($data);

        return $interaction;
    }

    public function mockChannel(string $id): MockObject&Channel
    {
        $channel = $this->createMock(Channel::class);
        $channel->method('__get')
            ->willReturnMap([
                ['id', $id],
                ['name', 'channel'],
            ]);

        return $channel;
    }
}
