<?php

namespace App\Security;


class TokenGenerator
{
    const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

    public function getRandomSecureToken(int $length = 30) : string
    {
        $token = '';
        $maxNulber = strlen(self::ALPHABET);

        for ($i = 0; $i < $length; $i++) {
            $token .= self::ALPHABET[random_int(0, $maxNulber - 1)];
        }

        return $token;
    }
}