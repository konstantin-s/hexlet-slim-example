<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;

require '/composer/vendor/autoload.php';

session_start();

$container = new Container();
$container->set(
    'renderer',
    function () {
        return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates/sessions');
    }
);
$container->set(
    'flash',
    function () {
        return new \Slim\Flash\Messages();
    }
);

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);
$app->add(MethodOverrideMiddleware::class);

$users = [
    ['name' => 'admin', 'passwordDigest' => hash('sha256', 'secret')],
    ['name' => 'mike', 'passwordDigest' => hash('sha256', 'superpass')],
    ['name' => 'kate', 'passwordDigest' => hash('sha256', 'strongpass')]
];

// BEGIN (write your solution here)
$router = $app->getRouteCollector()->getRouteParser();
$app->get(
    '/',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) use ($users) {
        $user = isset($_SESSION['user']) ? $_SESSION['user'] : null;

        $flash = $this->get('flash')->getMessages();
        $params = [
            'user' => $user,
            'flash' => $flash,
        ];
        return $this->get('renderer')->render($response, 'index.phtml', $params);
    }
)->setName('main');

$app->post(
    '/session',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response, array $args) use ($users, $router) {
        $postData = $request->getParsedBodyParam('user');
        $passwordDigest = hash('sha256', $postData['password']);

        $user = \Tightenco\Collect\Support\Collection::make($users)->firstWhere('name', $postData['name']);
        if (!$user || $user['passwordDigest'] != $passwordDigest) {
            $this->get('flash')->addMessage('error', 'Wrong password or name');
        } else {
            $_SESSION['user'] = $user;
        }
        return $response->withRedirect($router->urlFor('main'));
    }
);
$app->delete(
    '/session',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response, array $args) use ($router) {
        $_SESSION = [];
        session_destroy();
        return $response->withRedirect($router->urlFor('main'));
    }
);
// END


$app->post(
    '/',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) use ($users, $router) {
        $postData = $request->getParsedBodyParam('user');
        $passwordDigest = hash('sha256', $postData['password']);

        $user = \Tightenco\Collect\Support\Collection::make($users)->firstWhere('name', $postData['name']);
        if (!$user || $user['passwordDigest'] != $passwordDigest) {
            $this->get('flash')->addMessage('error', 'Wrong password or name');
        } else {
            $_SESSION['user'] = $user;
        }
        return $response->withRedirect($router->urlFor('main'));
    }
);


$app->run();
