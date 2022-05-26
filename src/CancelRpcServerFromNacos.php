<?php

namespace YuanxinHealthy\RpcHook;
/**
 *服务终止前首先从nacos上下服务.
 */
class CancelRpcServerFromNacos
{
    /**
     * 命名空间.
     * @var
     */
    protected $namespaceId;
    /**
     * 分组
     * @var
     */
    protected $groupName;
    /**
     * 本地服务IP
     * @var
     */
    protected $serverIp;
    /**
     * 本地服务端口
     * @var int
     */
    protected $serverPort = 9503;

    /**
     * nacosAddress
     * @var
     */
    protected $nacosAddress;

    /**
     *初始化操作
     */
    public function __construct()
    {
        $this->serverIp = getenv('POD_IP');
        $this->namespaceId = getenv('APP_NAME');
        $this->groupName = getenv('NACOS_MANAGEMENT_GROUP_NAME');
        $nacosHost = getenv('NACOS_HOST');
        $nacosPort = getenv('NACOS_PORT');
        if ($nacosHost && $nacosPort) {
            $this->nacosAddress = 'http://' . $nacosHost . ':' . $nacosPort;
        }
        //$this->serverIp = '172.17.0.3';
        //$this->namespaceId = 'search-server';
        //$this->groupName = 'dev-api';
        //$this->nacosAddress = 'http://192.168.4.142:8848';
    }

    /**
     * 获取注册进去的服务
     *
     * @return array
     */
    public function getServiceList()
    {
        if (empty($this->serverIp) || empty($this->namespaceId) || empty($this->groupName) || empty($this->nacosAddress)) {
            // 有问题处理不了
            return ['doms' => [], 'count' => 0];
        }
        $list = $this->curl('/nacos/v1/ns/service/list', [
            'pageNo' => 1,
            'groupName' => $this->groupName,
            'namespaceId' => $this->namespaceId,
            'pageSize' => 200,
        ]);
        if (empty($list['doms']) || empty($list['count'])) {
            return ['doms' => [], 'count' => 0];
        }
        return $list;
    }

    /**
     * 修改服务
     * @return void
     */
    public function updateServer($enabled = false)
    {
        $list = $this->getServiceList();
        if (empty($list['doms'])) {
            return;
        }
        foreach ($list['doms'] as $serverName) {
            $this->curl('/nacos/v1/ns/instance', [
                'groupName' => $this->groupName,
                'namespaceId' => $this->namespaceId,
                'serviceName' => $serverName,
                'ip' => $this->serverIp,
                'port' => $this->serverPort,
                'enabled' => $enabled,
            ], CURLOPT_PUT);
        }
        $redis = new \Redis();
        $redis->connect('192.168.4.142', 6380);
        $redis->lPush('nacoslog',json_encode([
            'ip' => $this->serverIp,
            'port' => $this->serverPort,
            'date' => date("Y-m-d H:i:s"),
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param $url
     * @param $method
     * @param $pars
     * @return void
     */
    public function curl($url, $pars = [], $method = CURLOPT_HTTPGET)
    {
        $url = $this->nacosAddress . $url;
        $ch = curl_init();
        curl_setopt($ch, $method, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $url .= '?' . http_build_query($pars);
        curl_setopt($ch, CURLOPT_URL, $url);
        $info = curl_getinfo($ch);
        $res = curl_exec($ch);
        curl_close($ch);
        if (empty($res)) {
            return [];
        }
        $res = json_decode($res, true);
        if (empty($res)) {
            return [];
        }
        return $res;
    }
}

