<?php

use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use support\Container;

foreach (config('plugin.hsk99.gateway-worker.server', []) as $processName => $config) {
    if (!empty($config['type']) && 'Register' === $config['type']) {
        $register             = new Register("text://" . $config['registerAddress']);
        $register->name       = $processName;
        $register->secretKey  = $config['secretKey'] ?? '';
        $register->reloadable = $config['reloadable'] ?? false;
    }

    if (!empty($config['type']) && 'Gateway' === $config['type']) {
        $gateway        = new Gateway($config['listen'], $config['context'] ?? []);
        $gateway->name  = $processName;
        $gateway->count = $config['count'];

        $propertyMap = [
            'transport',
            'lanIp',
            'startPort',
            'pingInterval',
            'pingNotResponseLimit',
            'pingData',
            'registerAddress',
            'secretKey',
            'reloadable',
            'router',
            'sendToWorkerBufferSize',
            'sendToClientBufferSize',
            'protocolAccelerate',
        ];
        foreach ($propertyMap as $property) {
            if (isset($config[$property])) {
                $gateway->$property = $config[$property];
            }
        }

        if (isset($config['handler'])) {
            if (!class_exists($config['handler'])) {
                echo "process error: class {$config['handler']} not exists\r\n";
                continue;
            }

            $instance = Container::make($config['handler'], $config['constructor'] ?? []);
            worker_bind($gateway, $instance);
        }
    }

    if (!empty($config['type']) && 'BusinessWorker' === $config['type']) {
        if (isset($config['handler']) && !class_exists($config['handler'])) {
            echo "process error: class {$config['handler']} not exists\r\n";
            continue;
        }

        $bussiness               = new BusinessWorker();
        $bussiness->name         = $processName;
        $bussiness->count        = $config['count'];
        $bussiness->eventHandler = "\\Hsk99\\WebmanGatewayWorker\\Callback\\BusinessWorker";

        $propertyMap = [
            'registerAddress',
            'processTimeout',
            'processTimeoutHandler',
            'secretKey',
            'sendToGatewayBufferSize',
        ];
        foreach ($propertyMap as $property) {
            if (isset($config[$property])) {
                $bussiness->$property = $config[$property];
            }
        }
    }
}
