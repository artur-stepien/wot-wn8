<?php

namespace Wargaming\WoT\Tests\Mockup;

use Wargaming\API as PAPI;
use Wargaming\Language\LanguagePrototype;
use Wargaming\Server\ServerPrototype;

class API extends PAPI
{
    public function __construct(LanguagePrototype $language = null, ServerPrototype $server = null)
    {
        $this->language = $language;
        $this->server = $server;
    }

    public function get(string $namespace, array $options = [], bool $assoc = false, string $ETag = null, bool $HTTPHeaders = false)
    {

        switch ($namespace) {
            case 'wot/account/list':
                return json_decode(json_encode([['account_id' => 530190876]]), false);
                break;
            case 'wot/account/info':
                return json_decode(file_get_contents(dirname(__DIR__) . '\Fixtures\account_info.json'), false);
                break;
            case 'wot/account/tanks':
                return json_decode(file_get_contents(dirname(__DIR__) . '\Fixtures\tanks_info.json'), false);
                break;
            default:
                var_dump($namespace, $options);
                die;
        }
    }
}