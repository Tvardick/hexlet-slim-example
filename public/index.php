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

$app->delete("/users/{id}/cabinet", function ($request, $response, $agrs) use ($database, $router) {
    $id = $agrs['id'];
    $_SESSION['users'][$id]['auth'] = null;
    $this->get('flash')->addMessage('success', 'you are sign out');
    return $response->withRedirect($router->urlFor('users'));
});

$app->get('/users/{id}/cabinet', function ($request, $response, $agrs) use ($database, $router) {
    $id = $agrs['id'];
    $params = [
        'user' => $_SESSION['users'][$id],
    ];
    return $this->get('renderer')->render($response, "users/cabinet.phtml", $params);
})->setName('cabinet');

$app->get('/users/auth', function ($request, $response) {
    $messages = $this->get('flash')->getMessages();
    $params = [
        'flash' => $messages,
    ];
    return $this->get('renderer')->render($response, "users/auth.phtml", $params);
})->setName('auth');

$app->post('/users/auth', function ($request, $response) use ($router, $database) {
    $email = $request->getParsedBodyParam('email');
    $user = $database->findUserViaEmail($email);
    if ($user) {
        $this->get('flash')->addMessage('success', 'Email Access');
        $url = $router->urlFor('cabinet', $user);
        $_SESSION['users'][$user['id']]['auth'] = true;
        return $response->withRedirect($url, 302);
    }
    $this->get('flash')->addMessage('error', 'Email denied');
    return $response->withRedirect('auth');
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
        if ($users[$id]) {
            $_SESSION['users'][$id]['nickname'] = $data['nickname'];
            $_SESSION['users'][$id]['email'] = $data['email'];
        }
        $this->get('flash')->addMessage('success', 'User has been updated');
        $url = $router->urlFor("users");
        return $response
            ->withStatus(302)
            ->withRedirect($url);
    }
    $params = ['user' => $data, 'errors' => $errors];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

$app->get('/users/{id}', function ($request, $response, $agrs) use ($database) {
    $id = $agrs['id'];
    $user = $database->findUser($id);
    if ($user) {
        $params = ["user" => $user, 'id' => $id];
        return $this
            ->get('renderer')
            ->render($response, 'users/edit.phtml', $params);
    }
    $params = ["users" => []];
    return $this
        ->get('renderer')
        ->render($response->write("Page not find")->withStatus(404), 'users/show.phtml', $params);
})->setName('user');

$app->get('/users', function ($request, $response) use ($database) {
    $users = $database->getUsers();
    if ($users) {
        $page = $request->getQueryParam('page', 1);
        $per = $request->getQueryParam('per', 5);
        $info = array_slice($users, ($page - 1) * $per, $per);
        $messages = $this->get('flash')->getMessages();
        $params = ["users" => $info, 'flash' => $messages, 'page' => $page];
        return $this->get('renderer')->render($response, 'users/show.phtml', $params);
    }
    $params = ['users' => [], 'flash' => []];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users');

$app->post('/users', function ($request, $response) use ($router, $database) {
    $user = $request->getParsedBodyParam('user');
    $validator = new App\Validator();
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        $user['auth'] = null;
        $database->save($user);
        $this->get('flash')->addMessage('success', 'Registration done');
        $url = $router->urlFor('users');
        return $response->withRedirect($url, 302);
    }
    $params = ['user' => $user, 'errors' => $errors];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->delete("/users/{id}", function ($request, $response, $agrs) use ($database, $router) {
    $id = $agrs['id'];
    unset($_SESSION['users'][$id]);
    $this->get('flash')->addMessage('success', 'User has been deleted');
    return $response->withRedirect($router->urlFor('users'));
});

$app->run();
