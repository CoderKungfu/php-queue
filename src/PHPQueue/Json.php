<?php
namespace PHPQueue;

use PHPQueue\Exception\JsonException;

class Json {
    public static function safe_decode( $text ) {
        if ( $text === null ) {
            return null;
        }
        $data = json_decode($text, true);
        if ( $data === null ) {
            throw new JsonException("JSON could not be decoded: '{$text}'");
        }
        return $data;
    }
}
