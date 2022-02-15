<?php

namespace Hsk99\WebmanGatewayWorker;

use Hsk99\WebmanGatewayWorker\Events;

class BusinessWorker extends \GatewayWorker\BusinessWorker
{
    public function __construct($config)
    {
        foreach ($config as $key => $value) {
            if ('eventHandler' === $key) {
                $this->customizeEventHandler = $value;
                continue;
            }

            $this->$key = $value;
        }
        $this->eventHandler = Events::class;

        $backtrace               = debug_backtrace();
        $this->_autoloadRootPath = dirname($backtrace[0]['file']);
    }

    public function onWorkerStart()
    {
        $this->_onWorkerStart  = $this->onWorkerStart;
        $this->_onWorkerReload = $this->onWorkerReload;
        $this->_onWorkerStop   = $this->onWorkerStop;
        $this->onWorkerStop    = array($this, 'onWorkerStop');
        $this->onWorkerStart   = array($this, 'onWorkerStart');
        $this->onWorkerReload  = array($this, 'onWorkerReload');

        $args = func_get_args();
        $this->id = $args[0]->id;
        parent::onWorkerStart();
    }
}
