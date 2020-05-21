<?php

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;

require '/composer/vendor/autoload.php';

$container = new Container();
$container->set(
    'renderer',
    function () {
        return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
    }
);

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$app->get(
    '/',
    function ($request, $response) {
        $cart = json_decode($request->getCookieParam('cart', json_encode([])), true);
        $params = [
            'cart' => $cart
        ];
        return $this->get('renderer')->render($response, 'index.phtml', $params);
    }
);

// BEGIN (write your solution here)
$app->post(
    '/cart-items',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) {
        $cart = json_decode($request->getCookieParam('cart', json_encode([])), true);
        $postData = $request->getParsedBodyParam('item');
        $id = $postData['id'];
        $name = $postData['name'];

        if (isset($cart[$id])) {
            $cart[$id]['count']++;
        } else {
            $cart[$id] = ['count' => 1, 'name' => $name];
        }

        $encodedCart = json_encode($cart);
        return $response->withHeader('Set-Cookie', "cart={$encodedCart}")->withRedirect('/');
    }
);
$app->delete(
    '/cart-items',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) {
        $encodedCart = json_encode([]);
        return $response->withHeader('Set-Cookie', "cart={$encodedCart}")->withRedirect('/');
    }
);
// END

$app->run();
