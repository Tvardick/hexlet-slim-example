<?php

namespace App;

class Database
{
    public function __construct()
    {
        session_start();
        if (!isset($_SESSION['users'])) {
            $_SESSION['users'] = [];
        }
    }

    public function getUsers()
    {
        if (!$_SESSION['users']) {
            throw new \Exception("We dont have any user");
        }
        return $_SESSION['users'];
    }

    public function findUser($id)
    {
        $users = $this->getUsers();
        $user = $users[$id];
        if (!$user) {
            $error = json_encode($user);
            throw new \Exception("Can't find user {$error}");
        }
        return $user;
    }

    public function save($user)
    {
        $id = uniqid();
        if (!isset($_SESSION['users'][$id])) {
            $_SESSION['users'][$id] = $user;
        } else {
            $error = json_encode($_SESSION['users'][$id]);
            throw new \Exception("This user was create {$error}");
        }
    }

    public function findUserViaEmail($email)
    {
        $users = $this->getUsers();
        $id = [];
        foreach ($users as $key => $user) {
            if (array_key_exists('email', $user)) {
                if ($user['email'] === $email) {
                    $id = $key;
                    break;
                }
            }
        }
        if ($id === []) {
            throw new \Exception("user by email not found");
        }
        return ['id' => $id, 'user' => $users[$id]];
    }
}
