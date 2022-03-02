<?php

namespace Hsk99\WebmanGatewayWorker\Protocols;

use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Hsk99\WebmanGatewayWorker\Protocols\JsonTcpHead;
use Hsk99\WebmanGatewayWorker\Protocols\JsonTcpEof;
use Hsk99\WebmanGatewayWorker\Protocols\JsonWebSocket;

/**
 * Json 组合协议
 *
 * @author HSK
 * @date 2022-01-07 10:41:52
 */
class Json
{
    /**
     * 分包
     *
     * @author HSK
     * @date 2022-01-07 10:42:09
     *
     * @param string $buffer
     * @param TcpConnection $connection
     *
     * @return integer
     */
    public static function input(string $buffer, TcpConnection $connection): int
    {
        if (!isset($connection->packetProtocol)) {
            $request = new Request($buffer);

            switch (true) {
                case 'upgrade' === strtolower($request->header('connection')):
                    $protocol = 'JsonWebSocket';
                    break;
                case chr(65) === substr($buffer, 0, 1):
                    $protocol = 'JsonTcpHead';
                    break;
                case chr(66) === substr($buffer, 0, 1):
                    $protocol = 'JsonTcpEof';
                    break;
                default:
                    $connection->close(json_encode(['cmd' => 'error', 'msg' => '非法连接'], 320), true);
                    return 0;
                    break;
            }

            $connection->packetProtocol = $protocol;
        }

        switch ($connection->packetProtocol) {
            case 'JsonWebSocket':
                return JsonWebSocket::input($buffer, $connection);
                break;
            case 'JsonTcpHead':
                return 1 + JsonTcpHead::input(substr($buffer, 1), $connection);
                break;
            case 'JsonTcpEof':
                return 1 + JsonTcpEof::input(substr($buffer, 1), $connection);
                break;
        }
    }

    /**
     * 打包
     *
     * @author HSK
     * @date 2022-01-07 10:42:14
     *
     * @param array $buffer
     * @param TcpConnection $connection
     *
     * @return string
     */
    public static function encode(array $buffer, TcpConnection $connection): string
    {
        if (!isset($connection->packetProtocol)) $connection->packetProtocol = 'JsonTcpHead';

        switch ($connection->packetProtocol) {
            case 'JsonWebSocket':
                return JsonWebSocket::encode($buffer, $connection);
                break;
            case 'JsonTcpHead':
                return chr(65) + JsonTcpHead::encode($buffer, $connection);
                break;
            case 'JsonTcpEof':
                return chr(66) + JsonTcpEof::encode($buffer, $connection);
                break;
        }
    }

    /**
     * 解包
     *
     * @author HSK
     * @date 2022-01-07 10:42:18
     *
     * @param string $buffer
     * @param TcpConnection $connection
     *
     * @return array
     */
    public static function decode(string $buffer, TcpConnection $connection): array
    {
        if (!isset($connection->packetProtocol)) $connection->packetProtocol = 'JsonTcpHead';

        switch ($connection->packetProtocol) {
            case 'JsonWebSocket':
                return JsonWebSocket::decode($buffer, $connection);
                break;
            case 'JsonTcpHead':
                return JsonTcpHead::decode(substr($buffer, 1), $connection);
                break;
            case 'JsonTcpEof':
                return JsonTcpEof::decode(substr($buffer, 1), $connection);
                break;
        }
    }
}
