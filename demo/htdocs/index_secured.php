<?php
/*
 * Secured REST server using Authorization: Token.
 *
 * Example cURL commands:
 *
 *   curl -XPOST http://<server>/<queueName> -H "Content-Type: application/json" -H "Authorization: Token ki*ksjdu^GDjc\nk" -d '{"people":["Talia","Tabitha","Tolver"]}'
 *
 *   curl -XPUT http://<server>/<queueName> -H "Authorization: Token ki*ksjdu^GDjc\nk"
 *
 */
require_once dirname(__DIR__) . '/config.php';
class SecuredREST implements \PHPQueue\Interfaces\Auth
{
    public static $valid_token = 'ki*ksjdu^GDjc\nk';

    public function isAuth()
    {
        $token = $this->getToken();
        if ( !empty($token) && ($token == self::$valid_token)) {
            return true;
        }

        return false;
    }

    private function getToken()
    {
        $authHeader = null;
        if ( function_exists( 'apache_request_headers' ) ) {
            $apacheHeaders = apache_request_headers();
            if ( isset( $apacheHeaders['Authorization'] ) )
            $authHeader = $apacheHeaders['Authorization'];
        } else {
            if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) )
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if ( isset( $authHeader ) ) {
            $m = array();
            $tokenPattern = '/^(?P<authscheme>Token)\s(?P<token>[a-zA-Z0-9\!\@\#\$\%\^\&\*\(\)\\\]+)$/';
            $match = preg_match( $tokenPattern, $authHeader, $m );
            if ($match > 0) {
                return $m['token'];
            }
        }

        return false;
    }
}
$options = array('auth'=>new SecuredREST);
PHPQueue\REST::defaultRoutes($options);
