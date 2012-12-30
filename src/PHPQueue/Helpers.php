<?php
namespace PHPQueue;
class Helpers
{
    public static function output($data=null, $code=200, $message="")
    {
        if ( is_array($data) ) {
            $return = array(
                  'code' => $code
                , 'data'   => $data
            );
            if (!empty($message)) $return['message'] = $message;
        } elseif ( is_object($data) ) {
            $return = new \stdClass();
            $return->code = $code;
            $return->data = $data;
            if (!empty($message)) $return->message = $message;
        } else {
            $return = new \stdClass();
            $return->code = $code;
            if (!empty($message)) $return->message = $message;
        }

        return $return;
    }
}
