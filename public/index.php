<?php

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

$container = new Container();

$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

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
    $users = $database->getUsers();
    $filterUsers = collect($users)->filter(fn($user) => $user['id'] !== $id)->all();
    $encodedUser = json_encode($filterUsers);
    $this->get('flash')->addMessage('success', 'User has been deleted');
    return $response->withRedirect($router->urlFor('users'))->withHeader('Set-Cookie', "users={$encodedUser}");
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
    $data = $request->getParsedBodyParam('user');
    $validator = new App\Validator();
    $errors = $validator->validate($data);
    if (count($errors) === 0) {
        $users = $database->getUsers();
        $usersNew = collect($users)->map(function ($value) use ($id, $data) {
            if ($value['id'] === $id) {
                $value['nickname'] = $data['nickname'];
                $value['email'] = $data['email'];
            }
            return $value;
        })->all();
        $encodedUser = json_encode($usersNew);
        $this->get('flash')->addMessage('success', 'User has been updated');
        $url = $router->urlFor("users");
        return $response->withStatus(302)->withRedirect($url)->withHeader('Set-Cookie', "users={$encodedUser}");
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
            ->render($response, 'users/edit.phtml', $params);
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
        $users = json_decode($request->getCookieParam("users", json_encode([])), true);
        $user['id'] = uniqid();
        $users[] = $user;
        $encodedUser = json_encode($users);
        $this->get('flash')->addMessage('success', 'Registration done');
        $url = $router->urlFor('users');
        return $response->withRedirect($url, 302)->withHeader('Set-Cookie', "users={$encodedUser}");
    }
    $params = ['user' => $user, 'errors' => $errors];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->run();
