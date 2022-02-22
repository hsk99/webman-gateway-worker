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
}
