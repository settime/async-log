<?php

namespace FlyCms\AsyncLog;

use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer;

class LogClient
{

    /**
     * @var AsyncTcpConnection
     */
    private static $connection = null;

    /**
     * @var int|bool 心跳定时器id
     */
    private static $time_id;

    /**
     * @var array
     * 临时缓存
     */
    private static $temp_cache = [];

    /**
     * @var bool 链接是否准备好
     */
    private static $is_init = false;

    /**
     * @return void
     * @throws \Exception
     * 执行初始化链接
     */
    private static function tryInit()
    {
        $log_address =  config('plugin.fly-cms.async-log.app.register_address');
        self::$connection = new AsyncTcpConnection($log_address);
        self::$connection->onConnect = function (){
            self::auth();//验证
            self::ping();
            //标记已准备好
            self::$is_init = true;
            //清空临时缓存的数据
            if (self::$temp_cache){
                foreach (self::$temp_cache as $k=>$item){
                    self::$connection->send( $item['send_data']);
                    unset(self::$temp_cache[$k]);
                }
            }
        };
        self::$connection->onClose = function () {
            Timer::del(self::$time_id);
            self::$is_init = false;
            self::$connection = null;
            //重新初始化
            self::tryInit();
        };
        self::$connection->connect();
    }

    /**
     * @return void
     */
    private static function auth(){
        $token= config('plugin.fly-cms.async-log.app.token');
        self::send('auth',md5($token));
    }

    /**
     * @return void
     * 添加心跳
     */
    private static function ping()
    {
        self::$time_id = Timer::add(20, function () {
            self::send('ping', []);
        });
    }

    /**
     * @param $key string 所属类别
     * @param array $send_data 需要添加的日志数据
     * @return void
     */
    public static function send($key,$send_data = [])
    {
        if (!self::$connection){
            self::tryInit();
        }
        $send_data = json_encode([
            'type' => $key,
            'send_data' => $send_data
        ]);
        if (self::$is_init){
            self::$connection->send($send_data);
        }else{
            // 链接没准备好,直接写临时缓存里面去
            self::$temp_cache[] = [
                'key' => $key,
                'send_data' => $send_data,
            ];
        }
    }



}
