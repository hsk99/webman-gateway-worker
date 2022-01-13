<?php

namespace Hsk99\WebmanGatewayWorker\Callback;

use Webman\Config;
use support\Log;
use GatewayWorker\Lib\Gateway;

/**
 * BusinessWorker 回调设置
 *
 * @author HSK
 * @date 2021-12-30 16:48:58
 */
class BusinessWorker
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
        self::$workerName = $worker->name;

        Config::reload(config_path(), ['route', 'container']);

        $config = config('gateway_worker.' . $worker->name, []);

        self::$handler = $config['handler'] ?? null;

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
        if (config('plugin.hsk99.gateway-worker.app.debug')) {
            echo "\033[31;1m" . date('Y-m-d H:i:s') . "\tDebug：" . self::$workerName . "\t" . var_export($message, true) . PHP_EOL . "\033[0m";
        }

        $time = microtime(true);
        Log::debug('', [
            'worker'         => self::$workerName,                                           // 运行进程
            'time'           => date('Y-m-d H:i:s.', $time) . substr($time, 11),             // 请求时间（包含毫秒时间）
            'channel'        => 'request',                                                   // 日志通道
            'level'          => 'DEBUG',                                                     // 日志等级
            'message'        => '',                                                          // 描述
            'client_address' => $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REMOTE_PORT'],     // 请求客户端地址
            'server_address' => $_SERVER['GATEWAY_ADDR'] . ':' . $_SERVER['GATEWAY_PORT'],   // 请求服务端地址
            'context'        => $message ?? "",                                              // 请求数据
        ]);

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
            Gateway::sendToClient($clientId, [
                'event' => 'error',
                'code'  => $th->getCode() ?? 500,
                'msg'   => config('app.debug') ? $th->getMessage() : 'Server internal error',
                'data'  => [],
            ]);
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
                Gateway::sendToClient($clientId, [
                    'event' => 'error',
                    'code'  => 500,
                    'msg'   => '非法操作，传输数据不是JSON格式',
                    'data'  => [],
                ]);
                return;
            }
        } else {
            $messageData = $message;
        }

        if ('ping' === $messageData['event']) {
            return;
        }

        $event   = $messageData['event'];
        $piece = count(explode('.', $event));

        switch ($piece) {
            case '1':
                $module     = "";
                $controller = parse_name($event, 1);
                $action     = parse_name($event, 1, false);
                break;
            case '2':
                list($controller, $action) = explode('.', $event, 2);
                $module     = "";
                $controller = parse_name($controller, 1);
                $action     = parse_name($action, 1, false);
                break;
            case '3':
                list($module, $controller, $action) = explode('.', $event, 3);
                $module     = "\\" . parse_name($module, 1);
                $controller = parse_name($controller, 1);
                $action     = parse_name($action, 1, false);
                break;
            default:
                $module = $controller = $action = "";
                break;
        }

        if (is_callable("\\app\\BusinessWorker\\" . self::$workerName . "\\Base::check")) {
            $check = call_user_func(["\\app\\BusinessWorker\\" . self::$workerName . "\\Base", 'check'], $clientId, $messageData, $event, $module, $controller, $action);
            if (!empty($check) && 400 === $check['code']) {
                Gateway::sendToClient($clientId, [
                    'event' => 'error',
                    'code'  => 400,
                    'msg'   => $check['msg'],
                    'data'  => [],
                ]);
                return;
            }
        }

        if (!empty($controller) && !empty($action) && is_callable("\\app\\BusinessWorker\\" . self::$workerName . "{$module}\\{$controller}::{$action}")) {
            $result = call_user_func(["\\app\\BusinessWorker\\" . self::$workerName . "{$module}\\{$controller}", $action], $clientId, $messageData);
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

        $result = ['event' => $event] + $result;

        Gateway::sendToClient($clientId, $result);
    }
}
