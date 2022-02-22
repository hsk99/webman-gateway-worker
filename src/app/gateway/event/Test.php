<?php

namespace app\gateway\event;

use GatewayWorker\Lib\Gateway;
use Hsk99\WebmanGatewayWorker\Util;

class Test
{
    /**
     * 测试
     *
     * @author HSK
     * @date 2022-02-22 11:19:35
     *
     * @param string $clientId
     * @param array $messageData
     *
     * @return void|array
     */
    public static function test($clientId, $messageData)
    {
        // 向所有连接推送
        Gateway::sendToAll(Util::encode('test', 200, 'success', [1, 2, 3, 4, 5, 6, 7, 8, 9]));

        return Util::encode();
    }
}
