<?php

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Response;

$app = new \AdamQuaile\SlimStack\App();

$app->get('/hello/:name', function($name) {

    $data = [
        "name" => $name,
        "ids" => [1,2,3]
    ];

    $response = new Response(json_encode($data));
    return $response;

});

$app->run();