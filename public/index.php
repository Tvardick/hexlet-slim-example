<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$companies = [];

//$users = App\Generator::generate(100);
$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/users/{id}', function ($request, $response, $agrs) use ($users) {
    $id = $agrs['id'];
    $params = ["user" => $users[$id]];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->get("/users", function ($request, $response, $agrs) use ($users) {
    $term = $request->getQueryParam('term', "");
    $filteredUsers = array_filter($users, fn($user) => str_contains($user, $term));
    $params = ['users' => $filteredUsers, 'term' => $term];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->get("/companies", function ($request, $response) use ($companies) {
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 5);
    $info = array_slice($companies, ($page - 1) * $per, $per);
    return $response->write(json_encode($info));
});

$app->get("/courses/{id}", function ($request, $response, $agrs) {
    $id = $agrs['id'];
    return $response->write("courses - id -> {$id}");
});

$app->run();
