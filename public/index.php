<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

session_start();

$container = new Container();

$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$companies = [];

$users = App\Generator::generate(100);

$database = new App\Database();

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('addUser');

$app->get('/users', function ($request, $response) use ($database) {
    $users = $database->getUsers();
    $messages = $this->get('flash')->getMessages();
    $params = ["users" => $users, 'flash' => $messages];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users');

$app->get('/users/{id}', function ($request, $response, $agrs) use ($database) {
    $id = $agrs['id'];
    $user = $database->findUser($id);
    if ($user) {
        $params = ["user" => $user];
        return $this
            ->get('renderer')
            ->render($response, 'users/show_user.phtml', $params);
    }
    $params = ["users" => []];
    return $this
        ->get('renderer')
        ->render($response->withStatus(404), 'users/show.phtml', $params);
})->setName('user');

$app->post('/users', function ($request, $response) use ($router, $database) {
    $user = $request->getParsedBodyParam('user');
    $validator = new App\Validator();
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        $database->create($user);
        $this->get('flash')->addMessage('success', 'Registration done');
        return $response->withRedirect($router->urlFor('users'), 302);
    }
    $params = ['user' => $user, 'errors' => $errors];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get("/companies", function ($request, $response) use ($companies) {
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 5);
    $info = array_slice($companies, ($page - 1) * $per, $per);
    return $response->write(json_encode($info));
})->setName('companies');

$app->get("/courses/{id}", function ($request, $response, $agrs) {
    $id = $agrs['id'];
    return $response->write("courses - id -> {$id}");
})->setName('course');

$app->run();
