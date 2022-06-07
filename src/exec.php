<?php
include_once __DIR__.'/CancelRpcServerFromNacos.php';
use YuanxinHealthy\RpcHook\CancelRpcServerFromNacos;
try {
    (new CancelRpcServerFromNacos())->updateServer(); // 把自己服务下线
} catch (\Exception $ex) {
    var_dump($ex->getCode());
}