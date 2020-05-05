<?php

// Контейнеры в этом курсе не рассматриваются (это тема связанная с самим ООП), но если вам интересно, то посмотрите DI Container
use DI\Container;
use Slim\Factory\AppFactory;

require 'vendor/autoload.php';

$repo = new App\Repository();

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
        return $response->write('<a href="/courses">/courses</a>');
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
        $term = $request->getQueryParam('term');
        if (strlen($term)) {
            $users = collect($users)->filter(
                function ($user) use ($term) {
                    return \Stringy\Stringy::create($user['firstName'])->startsWith($term, false);
                }
            );
        }
        $params = [
            'users' => $users,
            'term' => htmlspecialchars($term)
        ];
        return $this->get('renderer')->render($response, 'users/index.phtml', $params);
    }
);
// BEGIN (write your solution here)
$app->get(
    '/courses/new',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) use ($repo) {
        $params = ['course' => ['title' => '', 'paid' => ''], 'errors' => []];
        return $this->get('renderer')->render($response, 'courses/new.phtml', $params);
    }
);
$app->post(
    '/courses',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) use ($repo) {
        $course = $request->getParsedBodyParam('course');
        $errors = (new \App\Validator())->validate($course);
        if (count($errors) === 0) {
            $repo->save($course);
            return $response->withRedirect('/courses', 302);
        }

        $params = ['course' => $course, 'errors' => $errors];
        return $this->get('renderer')->render($response->withStatus(422), 'courses/new.phtml', $params);
    }
);
$app->get(
    '/courses',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) use ($repo) {
        $params = ['courses' => $repo->all()];
        return $this->get('renderer')->render($response, 'courses/index.phtml', $params);
    }
);
// END

$app->run();
