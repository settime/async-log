<?php

return [
    'consumer'  => [
        'handler'     => \FlyCms\AsyncLog\LogServer::class,
        'count'       => 1,
        'listen' => 'text://0.0.0.0:8770',
    ]
];
