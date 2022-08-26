<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

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
$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->delete("/users/{id}", function ($request, $response, $agrs) use ($database, $router) {
    $id = $agrs['id'];
    $database->remove($id);
    $this->get('flash')->addMessage('success', 'User has been deleted');
    return $response->withRedirect($router->urlFor('users'));
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('addUser');

$app->patch('/users/{id}', function ($request, $response, $agrs) use ($router, $database) {
    $id = $agrs['id'];
    $user = $database->findUser($id);
    $data = $request->getParsedBodyParam('user');
    $validator = new App\Validator();
    $errors = $validator->validate($data);
    if (count($errors) === 0) {
        $user['nickname'] = $data['nickname'];
        $user['email'] = $data['email'];
        $this->get('flash')->addMessage('success', 'User has been updated');
        $database->update($user);
        $url = $router->urlFor("users");
        return $response->withStatus(302)->withRedirect($url);
    }
    $params = ['user' => $data, 'errors' => $errors];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

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
        ->render($response->write("Page not find")->withStatus(404), 'users/show.phtml', $params);
})->setName('user');

$app->get('/users/{id}/edit', function ($request, $response, $agrs) use ($database) {
    $id = $agrs['id'];
    $user = $database->findUser($id);
    $params = [
        'user' => $user,
        'errors' => []
    ];
    return $this
        ->get('renderer')
        ->render($response, "/users/edit.phtml", $params);
})->setName('edit_user');

$app->get('/users', function ($request, $response) use ($database) {
    $users = $database->getUsers();
    $page = $request->getQueryParam('page', 1);
    $per = $request->getQueryParam('per', 5);
    $info = array_slice($users, ($page - 1) * $per, $per);
    $messages = $this->get('flash')->getMessages();
    $params = ["users" => $info, 'flash' => $messages, 'page' => $page];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users');

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
