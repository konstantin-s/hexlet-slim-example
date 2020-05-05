<?php

// Контейнеры в этом курсе не рассматриваются (это тема связанная с самим ООП), но если вам интересно, то посмотрите DI Container
use DI\Container;
use Slim\Factory\AppFactory;

require 'vendor/autoload.php';

$container = new Container();
$container->set(
    'renderer',
    function () {
        // Параметром передается базовая директория в которой будут храниться шаблоны
        return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
    }
);
AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$companies = App\Generator::generate(100);
$app->get(
    '/',
    function ($request, $response) {
        return $response->write('go to the /companies');
    }
);

// BEGIN (write your solution here)
$app->get(
    '/companies',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) use ($companies) {
        $pageSize = $request->getQueryParam('per') ?: 5;
        $recordsCount = count($companies);

        if ($pageSize <= 0) {
            return $response->withStatus(404);
        }
        if (strlen($request->getQueryParam('page')) && (int)$request->getQueryParam('page') <= 0) {
            return $response->withStatus(404);
        }

        $pageRequested = (int)$request->getQueryParam('page');
        if ($pageRequested > 0) {
            $pageRequested--;
        }
        if ($pageRequested * $pageSize > $recordsCount) {
            return $response->withStatus(404);
        }

        return $response->write(json_encode(array_slice($companies, $pageRequested * $pageSize, $pageSize)));
    }
);
// END


$app->get(
    '/companies/{id}',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response, array $args) use ($companies) {
        $company = collect($companies)->firstWhere('id', $args['id']);
        if (empty($company)) {
            return $response->write('Page not found')->withStatus(404);
        }

        return $response->write(json_encode($company));
    }
);
//

$users = App\Generator::generateUsers(100);
$app->get(
    '/users/{id}',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response, $args) use ($users) {
        $user = collect($users)->firstWhere('id', $args['id']);
        if (empty($user)) {
            return $response->write('Page not found')->withStatus(404);
        }
        $params = [
            'user' => $user
        ];
        return $this->get('renderer')->render($response, 'users/show.phtml', $params);
    }
);
$app->get(
    '/users',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) use ($users) {
        $params = [
            'users' => $users
        ];
        return $this->get('renderer')->render($response, 'users/index.phtml', $params);
    }
);

$courses = [['id' => 123, 'name' => 'coursename-' . rand()], ['id' => 423, 'name' => 'coursename-' . rand()]];
$app->get(
    '/courses',
    function ($request, $response) use ($courses) {
        $params = [
            'courses' => $courses
        ];
        return $this->get('renderer')->render($response, 'courses/index.phtml', $params);
    }
);

$app->run();
