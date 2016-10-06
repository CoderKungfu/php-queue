<?php
namespace PHPQueue;

use PHPQueue\Exception\JsonException;

class Json {
    public static function safe_decode( $text ) {
        $data = json_decode($text, true);
        if ( $data === null ) {
            throw new JsonException("JSON could not be decoded: '{$text}'");
        }
        return $data;
    }
}
