<?php
include_once __DIR__.'/CancelRpcServerFromNacos.php';
use YuanxinHealthy\RpcHook\CancelRpcServerFromNacos;
(new CancelRpcServerFromNacos())->updateServer(); // 把自己服务下线