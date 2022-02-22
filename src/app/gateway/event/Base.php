<?php

namespace app\gateway\event;

use Hsk99\WebmanGatewayWorker\Util;

class Base
{
    /**
     * 操作校验
     *
     * @author HSK
     * @date 2022-02-22 11:13:30
     *
     * @param string $clientId
     * @param array $messageData
     * @param string $event
     * @param string $module
     * @param string $controller
     * @param string $action
     *
     * @return void|array
     */
    public static function check($clientId, $messageData, $event, $module, $controller, $action)
    {
        return Util::encode();
    }
}
