<?php

namespace App\Exceptions;

use Exception;

class UserException extends Exception
{
    public static function userNotFound($email)
    {
        return new static("User with this email {$email} does not exist", 404);
    }

    public static function failedGetUsers()
    {
        return new static("Failed to get users", 400);
    }
    public static function someOtherError($message)
    {
        return new static($message, 400);
    }
}
