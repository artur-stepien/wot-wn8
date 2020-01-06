<?php

namespace Wargaming\WoT\Tests\Mockup;

use Wargaming\API as PAPI;
use const Wargaming\LANGUAGE_ENGLISH;
use const Wargaming\SERVER_EU;

class API extends PAPI
{
    public function __construct(string $application_id, string $language = LANGUAGE_ENGLISH, string $server = SERVER_EU)
    {

    }

    public function get(string $namespace, array $options = [], bool $assoc = false, string $ETag = null, bool $HTTPHeaders = false)
    {

        switch ($namespace) {
            case 'wot/account/list':
                return json_decode(json_encode([['account_id' => 530190876]]));
                break;
            case 'wot/account/info':
                return json_decode(file_get_contents(dirname(__DIR__) . '\Fixtures\account_info.json'));
                break;
            case 'wot/account/tanks':
                return json_decode(file_get_contents(dirname(__DIR__) . '\Fixtures\tanks_info.json'));
                break;
            default:
                var_dump($namespace, $options);
                die;
        }
    }
}