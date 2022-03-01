<?php

namespace Hsk99\WebmanGatewayWorker;

use Webman\Config;
use GatewayWorker\Lib\Gateway;
use Hsk99\WebmanGatewayWorker\Util;

/**
 * 业务处理
 *
 * @author HSK
 * @date 2022-02-15 16:02:50
 */
class Events
{
    /**
     * 自定义回调类
     *
     * @var string
     */
    protected static $handler = null;

    /**
     * 进程名称
     *
     * @var string
     */
    protected static $workerName = '';

    /**
     * 服务启动回调
     *
     * @author HSK
     * @date 2021-12-31 15:46:26
     *
     * @param \GatewayWorker\BusinessWorker $worker
     *
     * @return void
     */
    public static function onWorkerStart(\GatewayWorker\BusinessWorker $worker)
    {
        Config::reload(config_path(), ['route', 'container']);

        self::$workerName = $worker->name;

        self::$handler = $worker->customizeEventHandler ?? null;

        if (isset(self::$handler) && !class_exists(self::$handler)) {
            echo "process error: class " . self::$handler . " not exists\r\n";
        }

        require_once base_path() . '/support/bootstrap.php';

        if (is_callable([self::$handler, 'onWorkerStart'])) {
            call_user_func([self::$handler, 'onWorkerStart'], $worker);
        }
    }

    /**
     * 服务关闭回调
     *
     * @author HSK
     * @date 2021-12-31 15:47:46
     *
     * @param \GatewayWorker\BusinessWorker $worker
     *
     * @return void
     */
    public static function onWorkerStop(\GatewayWorker\BusinessWorker $worker)
    {
        if (is_callable([self::$handler, 'onWorkerStop'])) {
            call_user_func([self::$handler, 'onWorkerStop'], $worker);
        }
    }

    /**
     * 客户端连接回调
     *
     * @author HSK
     * @date 2021-12-31 15:47:51
     *
     * @param string $clientId
     *
     * @return void
     */
    public static function onConnect(string $clientId)
    {
        if (is_callable([self::$handler, 'onConnect'])) {
            call_user_func([self::$handler, 'onConnect'], $clientId);
        }
    }

    /**
     * 客户端关闭回调
     *
     * @author HSK
     * @date 2021-12-31 15:47:57
     *
     * @param string $clientId
     *
     * @return void
     */
    public static function onClose(string $clientId)
    {
        if (is_callable([self::$handler, 'onClose'])) {
            call_user_func([self::$handler, 'onClose'], $clientId);
        }
    }

    /**
     * WebSocket 握手触发回调
     *
     * @author HSK
     * @date 2021-12-31 15:48:04
     *
     * @param string $clientId
     * @param array $data
     *
     * @return void
     */
    public static function onWebSocketConnect(string $clientId, array $data)
    {
        if (is_callable([self::$handler, 'onWebSocketConnect'])) {
            call_user_func([self::$handler, 'onWebSocketConnect'], $clientId, $data);
        }
    }

    /**
     * 收到数据回调
     *
     * @author HSK
     * @date 2021-12-31 15:48:10
     *
     * @param string $clientId
     * @param mixed $message
     *
     * @return void
     */
    public static function onMessage(string $clientId, $message)
    {
        try {
            if (
                null === self::$handler
                || !is_callable([self::$handler, 'onMessage'])
            ) {
                self::jsonTcpRequest($clientId, $message);
                return;
            }

            call_user_func([self::$handler, 'onMessage'], $clientId, $message);
        } catch (\Throwable $th) {
            Gateway::sendToClient($clientId, Util::encode(
                'error',
                $th->getCode() ?? 500,
                config('app.debug') ? $th->getMessage() : 'Server internal error',
                []
            ));
        }
    }

    /**
     * 使用默认 JsonTcp 解析规则
     *
     * @author HSK
     * @date 2021-12-31 15:48:18
     *
     * @param string $clientId
     * @param mixed $message
     *
     * @return void
     */
    protected static function jsonTcpRequest(string $clientId, $message)
    {
        if (!is_array($message)) {
            $messageData = json_decode($message, true);
            if (empty($messageData) || !is_array($messageData)) {
                Gateway::sendToClient($clientId, Util::encode(
                    'error',
                    500,
                    '非法操作，传输数据不是JSON格式',
                    [],
                ));
                return;
            }
        } else {
            $messageData = $message;
        }

        if ('ping' === $messageData['event']) {
            return;
        }

        $event = $messageData['event'];
        $piece = count(explode('.', $event));

        switch ($piece) {
            case 1:
                $module     = "";
                $controller = Util::parseName($event, 1);
                $action     = Util::parseName($event, 1, false);
                break;
            case 2:
                list($controller, $action) = explode('.', $event, 2);
                $module     = "";
                $controller = Util::parseName($controller, 1);
                $action     = Util::parseName($action, 1, false);
                break;
            case 3:
                list($module, $controller, $action) = explode('.', $event, 3);
                $module     = "\\" . Util::parseName($module, 1, false);
                $controller = Util::parseName($controller, 1);
                $action     = Util::parseName($action, 1, false);
                break;
            default:
                $module = $controller = $action = "";
                break;
        }

        if (is_callable("\\app\\gateway\\" . self::$workerName . "\\Base::check")) {
            $check = call_user_func(["\\app\\gateway\\" . self::$workerName . "\\Base", 'check'], $clientId, $messageData, $event, $module, $controller, $action);
            if (!empty($check) && 400 === $check['code']) {
                Gateway::sendToClient($clientId, Util::encode(
                    'error',
                    400,
                    $check['msg'],
                    [],
                ));
                return;
            }
        }

        if (!empty($controller) && !empty($action) && is_callable("\\app\\gateway\\" . self::$workerName . "{$module}\\{$controller}::{$action}")) {
            $result = call_user_func(["\\app\\gateway\\" . self::$workerName . "{$module}\\{$controller}", $action], $clientId, $messageData);
            if (empty($result)) {
                return;
            }
        } else {
            $result = [
                'code' => 400,
                'msg'  => '非法操作，方法不存在',
                'data' => [],
            ];
        }

        Gateway::sendToClient($clientId, Util::encode(
            $event,
            $result['code'] ?? 500,
            $result['msg'] ?? '',
            $result['data'] ?? []
        ));
    }
}
