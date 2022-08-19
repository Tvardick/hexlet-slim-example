<?php

namespace App;

class Database
{
    private $path = "/Users/tvard/www/tvardTest/html/hexlet-slim-example/accounts/users.json";

    private static function genID(): array
    {
        $range = range(1, 99);
        return collect($range)->shuffle(1)->toArray();
    }

    public function save(string $user): void
    {
        file_put_contents($this->path, $user, FILE_APPEND);
    }

    public function create(array $user): void
    {
        $userAcc = [
            'id' => array_rand(self::genID()),
            'nickname' => $user['nickname'],
            'email' => $user['email'],
        ];
        $json = json_encode($userAcc) . PHP_EOL;
        $this->save($json);
    }

    public function getUsers(): array
    {
        $users = file_get_contents($this->path);
        $parser = new Parser();
        return $parser->parseUsers($users);
    }

    public function findUser($id)
    {
        $users = $this->getUsers();
        return collect($users)->firstWhere('id', $id);
    }
}
