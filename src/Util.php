<?php

namespace Hsk99\WebmanGatewayWorker;

class Util
{
    /**
     * 字符串命名风格转换
     *
     * @author HSK
     * @date 2022-02-22 10:41:26
     *
     * @param string $name
     * @param integer $type
     * @param boolean $ucfirst
     *
     * @return string
     */
    public static function parseName(string $name, int $type = 0, bool $ucfirst = true): string
    {
        if ($type) {
            $name = preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name);

            return $ucfirst ? ucfirst($name) : lcfirst($name);
        }

        return strtolower(trim(preg_replace("/[A-Z]/", "_\\0", $name), "_"));
    }

    /**
     * 组装数据
     *
     * @author HSK
     * @date 2022-02-22 11:03:43
     *
     * @param string $event
     * @param integer $code
     * @param string $msg
     * @param array $data
     *
     * @return array
     */
    public static function encode($event = '', $code = 200, $msg = 'success', $data = []): array
    {
        $result = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];

        if (!empty($event)) {
            $result = ['event' => $event] + $result;
        }

        return $result;
    }

    /**
     * DEBUG
     *
     * @author HSK
     * @date 2022-03-01 11:32:46
     *
     * @param \Workerman\Connection\TcpConnection $connection
     * @param mixed $buffer
     * @param string $message
     *
     * @return void
     */
    public static function debug(\Workerman\Connection\TcpConnection $connection, $buffer, $message = '')
    {
        $time = microtime(true);
        $data = [
            'worker'         => $connection->worker->name,                         // 运行进程
            'time'           => date('Y-m-d H:i:s.', $time) . substr($time, 11),   // 时间（包含毫秒时间）
            'message'        => $message,                                          // 描述
            'client_address' => $connection->getRemoteAddress(),                   // 客户端地址
            'server_address' => $connection->getLocalAddress(),                    // 服务端地址
            'context'        => $buffer ?? "",                                     // 数据
        ];

        \support\Log::debug($message, $data);

        if (config('plugin.hsk99.gateway-worker.app.debug')) {
            switch ($message) {
                case 'response':
                    $color = 34;
                    break;
                case 'request':
                default:
                    $color = 31;
                    break;
            }
            echo "\033[$color;1m" . date('Y-m-d H:i:s', $time) . "\t"
                . $connection->worker->name . "\t"
                . var_export($buffer, true) . PHP_EOL . "\033[0m";
        }
    }
}
