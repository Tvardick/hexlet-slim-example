<?php

namespace App;

class Validator
{
    public function validate($user): array
    {
        $errors = [];

        if ($user['nickname'] === "") {
            $errors['nickname'] = 'Canno\'t be blanck';
        } elseif ($user['email'] === "") {
            $errors['email'] = 'Canno\'t be blanck';
        }

        return $errors;
    }
}
