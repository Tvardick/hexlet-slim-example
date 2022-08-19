<?php

namespace App;

class Parser
{
    public function parseUsers($users)
    {
        $decodeJson = array_map(
            fn($line) => json_decode($line, true),
            explode("\n", $users)
        );
        return array_filter($decodeJson, fn($item) => !empty($item));
    }
}
