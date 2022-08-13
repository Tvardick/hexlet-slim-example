<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
});
$app->get("/users", function ($request, $response) {
    return $response->write("GET /users");
});

$app->post("/users", function ($request, $response) {
    return $response->withStatus(302);
});

$app->get("/companies", function ($request, $response) use ($companies) {
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 5);
    $info = array_slice($companies, ($page - 1) * $per, $per);
    return $response->write(json_encode($info));
});

$app->run();
