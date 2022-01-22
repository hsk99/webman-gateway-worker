<?php

return [
    'register' => [
        'type'            => 'Register',
        'registerAddress' => '127.0.0.1:8810',
        'secretKey'       => 'hsk99',
    ],
    'event' => [
        'type'            => 'BusinessWorker',
        'registerAddress' => '127.0.0.1:8810',
        'secretKey'       => 'hsk99',
        'count'           => 1,
        // 'handler'         => process\Event::class,
        // 'bootstrap'       => []
    ],
    'Json' => [
        'type'                 => 'Gateway',
        'listen'               => 'tcp://0.0.0.0:8801',
        'protocol'             => \Hsk99\WebmanGatewayWorker\Protocols\Json::class,
        'count'                => 1,
        'lanIp'                => '127.0.0.1',
        'startPort'            => 8820,
        'pingInterval'         => 10,
        'pingNotResponseLimit' => 2,
        'pingData'             => '',
        'registerAddress'      => '127.0.0.1:8810',
        'secretKey'            => 'hsk99',
    ],
];
