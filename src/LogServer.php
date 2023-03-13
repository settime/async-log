<?php

namespace FlyCms\AsyncLog;

use Workerman\Lib\Timer;

class LogServer
{

    /**
     * @var array 日志内容
     */
    private $log_data = [];

    /**
     * @var int 每次最多批量插入几条
     */
    private $max_limit = 1000;

    /**
     * @var string
     */
    private $token = '';

    private $config = [];

    /**
     * @param $worker
     * @throws \Exception
     * 每个进程启动
     */
    public function onWorkerStart($worker)
    {
        $this->config =  config('plugin.fly-cms.async-log.app');
        $this->token = md5( $this->config['token']?? '');

        Timer::add(2, function () {
            $this->writeLog();
        });
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {
        //添加一个定时器,如果没有验证,2秒后删除该连接
        $connection->auth_timer_id = Timer::add(2, function ($connection) {
            $connection->close();
        }, array($connection), false);
    }

    /**
     * @param $connection
     * @param $json
     * @return void
     */
    public function onMessage($connection, $json)
    {
        $data_arr = json_decode($json, true);

        $type = $data_arr['type'] ?? '';
        $item = $data_arr['send_data'] ?? [];

        if (!$type) {
            return;
        }
        if ($type == 'ping') {
            return;
        }

        if ($type == 'auth') {
            if ($this->token != $item) {
                $connection->close();
                return;
            }
            //删除定时器
            Timer::del($connection->auth_timer_id);
            return;
        }

        if(!isset($this->log_data[$type])){
            $this->log_data[$type] = [];
        }
        $this->log_data[$type][] = $item;
    }

    public function onWorkerStop()
    {
        $this->writeLog();
    }


    /**
     * @return void
     * 写入日志
     */
    private function writeLog()
    {

        foreach ($this->log_data as $key => $list) {

            $this->log_data[$key] = [];
            if (!$list) {
                continue;
            }
            $model = $this->config['model_set'][$key] ??'';
            if (!$model){ // 获取不到模型处理,直接舍弃数据
                continue;
            }

            $insert_data = [];
            $j = 0;
            foreach ($list as $item) {
                $insert_data[] = $item;
                $j++;
                if ( $j >= $this->max_limit) {
                    $model::insertAll($insert_data);
                    $insert_data = [];
                    $j = 0;
                }
            }
            if ($insert_data) {
                $model::insertAll($insert_data);
            }
        }
    }

}
