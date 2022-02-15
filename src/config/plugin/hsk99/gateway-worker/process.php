<?php

use Hsk99\WebmanGatewayWorker\Gateway;
use Hsk99\WebmanGatewayWorker\BusinessWorker;
use Hsk99\WebmanGatewayWorker\Register;
use Hsk99\WebmanGatewayWorker\Protocols\Json;

return [
    'register' => [
        'handler'     => Register::class,
        'listen'      => 'text://127.0.0.1:8810',
        'count'       => 1,
        'constructor' => ['config' => [
            'secretKey' => 'hsk99',
        ]]
    ],
    'event' => [
        'handler'     => BusinessWorker::class,
        'count'       => 1,
        'constructor' => ['config' => [
            'registerAddress' => '127.0.0.1:8810',
            'secretKey'       => 'hsk99',
            // 'eventHandler'    => '',
            'name'            => 'event',
        ]],
        'bootstrap'   => []
    ],
    'gateway' => [
        'handler'     => Gateway::class,
        'listen'      => 'tcp://0.0.0.0:8801',
        'protocol'    => Json::class,
        'count'       => 1,
        'constructor' => ['config' => [
            'registerAddress'      => '127.0.0.1:8810',
            'secretKey'            => 'hsk99',
            'lanIp'                => '127.0.0.1',
            'startPort'            => 8820,
            'pingInterval'         => 10,
            'pingNotResponseLimit' => 2,
            'pingData'             => '',
        ]]
    ],
];
