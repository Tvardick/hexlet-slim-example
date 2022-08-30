<?php

namespace App;

class Database
{
    public function __construct()
    {
        session_start();
        if (!array_key_exists("users", $_COOKIE)) {
            setcookie("users", "", 0, "/");
        }
    }

    public function save($user)
    {
        if (!$user['nickname'] || !$user['email']) {
            $json = json_encode($user);
            throw new \Exception("Wrong data: {$json}");
        }
        if (!isset($user['id'])) {
            $user['id'] = uniqid();
        }
    }

    public function getUsers(): array
    {
        return json_decode($_COOKIE['users'], true);
    }

    public function findUser($id)
    {
        $users = $this->getUsers();
        $find = collect($users)->firstWhere('id', $id);
        if (!isset($find)) {
            $user = json_encode($find);
            throw new \Exception("Can't find user {$user}");
        }
        return $find;
    }
}
