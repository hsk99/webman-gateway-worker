<?php

namespace Hsk99\WebmanGatewayWorker;

class Install
{
    const WEBMAN_PLUGIN = true;

    /**
     * @var array
     */
    protected static $pathRelation = array(
        'config/plugin/hsk99/gateway-worker' => 'config/plugin/hsk99/gateway-worker',
    );

    /**
     * Install
     * @return void
     */
    public static function install()
    {
        static::installByRelation();
        self::appendStartupFile();
    }

    /**
     * Uninstall
     * @return void
     */
    public static function uninstall()
    {
        self::uninstallByRelation();
        self::removeStartupFile();
    }

    /**
     * installByRelation
     * @return void
     */
    public static function installByRelation()
    {
        foreach (static::$pathRelation as $source => $dest) {
            if ($pos = strrpos($dest, '/')) {
                $parent_dir = base_path() . '/' . substr($dest, 0, $pos);
                if (!is_dir($parent_dir)) {
                    mkdir($parent_dir, 0777, true);
                }
            }
            //symlink(__DIR__ . "/$source", base_path()."/$dest");
            // copy_dir(__DIR__ . "/$source", base_path() . "/$dest");
            static::copyDir(__DIR__ . "/$source", base_path() . "/$dest");
        }
    }

    /**
     * uninstallByRelation
     * @return void
     */
    public static function uninstallByRelation()
    {
        foreach (static::$pathRelation as $source => $dest) {
            $path = base_path() . "/$dest";
            if (!is_dir($path) && !is_file($path)) {
                continue;
            }
            /*if (is_link($path) {
                unlink($path);
            }*/
            remove_dir($path);
        }
    }

    /**
     * 追加启动加载
     *
     * @author HSK
     * @date 2022-01-13 15:11:48
     *
     * @return void
     */
    public static function appendStartupFile()
    {
        $append           = "require_once base_path() . '/vendor/hsk99/webman-gateway-worker/src/start.php';";
        $startFile        = base_path() . '/start.php';
        $startFileContent = file_get_contents($startFile);

        if (false === strpos($startFileContent, $append)) {
            $search  = "Worker::runAll();";
            $replace = $append . "\n\nWorker::runAll();";
            $startFileContent = str_replace($search, $replace, $startFileContent);

            file_put_contents($startFile, $startFileContent);
        }
    }

    /**
     * 去除启动加载
     *
     * @author HSK
     * @date 2022-01-13 15:11:45
     *
     * @return void
     */
    public static function removeStartupFile()
    {
        $remove           = "require_once base_path() . '/vendor/hsk99/webman-gateway-worker/src/start.php';";
        $startFile        = base_path() . '/start.php';
        $startFileContent = file_get_contents($startFile);

        if (false !== strpos($startFileContent, $remove)) {
            $search  = $remove . "\n\n";
            $replace = '';
            $startFileContent = str_replace($search, $replace, $startFileContent);

            file_put_contents($startFile, $startFileContent);
        }
    }

    /**
     * 拷贝文件，存在不覆盖
     *
     * @author HSK
     * @date 2022-01-13 11:47:17
     *
     * @param string $source
     * @param string $dest
     *
     * @return void
     */
    protected static function copyDir($source, $dest)
    {
        if (is_dir($source)) {
            if (!is_dir($dest)) {
                mkdir($dest);
            }
            $files = scandir($source);
            foreach ($files as $file) {
                if ($file !== "." && $file !== "..") {
                    static::copyDir("$source/$file", "$dest/$file");
                }
            }
        } else if (file_exists($source) && !is_file($dest)) {
            copy($source, $dest);
        }
    }
}
