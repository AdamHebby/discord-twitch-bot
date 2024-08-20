<?php

namespace DiscordTwitchBot\Repository;

abstract class JsonRepository implements RepositoryInterface
{
    /**
     * @param array<mixed> $data
     */
    protected function saveJson(string $path, array $data): void
    {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * @return array<mixed>
     */
    protected function loadJson(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }

        $data = file_get_contents($path);

        if ($data === false || empty($data)) {
            return [];
        }

        return json_decode($data, true);
    }
}
