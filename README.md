#async-log 基于webman的异步日志写入组件

## 概述

基于 **webman**  的异步日志写入组件<br>


## 注意事项
不保证日志100%不丢失,不保证日志100%不丢失,不保证日志100%不丢失. 重要数据千万别拿这个来用


安装

```shell
composer require fly-cms/async-log
```

配置

````shell
return [
    'enable' => true,
    'register_address' => 'text://127.0.0.1:8770',//client连接地址
    'token' => '',// 连接校验密码
    "model_set" =>[
        'request_log' => \app\model\log\RequestLogModel::class,
        'user_log' => \app\admin\controller\log\UserLog::class,
        'admin_log' => \app\model\log\AdminLogModel::class,
    ]
];
````

使用
send方法接受两个参数,key对应上面model_set里面的数组key,第二个参数是一个数组,需要写入mysql的数组数据
````shell
\FlyCms\AsyncLog\LogClient::send('request_log',[]);
````
