<?php

// Контейнеры в этом курсе не рассматриваются (это тема связанная с самим ООП), но если вам интересно, то посмотрите DI Container
use DI\Container;
use Slim\Factory\AppFactory;

// Старт PHP сессии
session_start();

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
$container->set(
    'flash',
    function () {
        return new \Slim\Flash\Messages();
    }
);

AppFactory::setContainer($container);

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

// BEGIN (write your solution here)
$router = $app->getRouteCollector()->getRouteParser();

$repoPosts = new App\RepositoryPost();

$app->get(
    '/posts',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) use ($repoPosts, $router) {
        $posts = $repoPosts->all();

        $pageSize = $request->getQueryParam('per') ?: 5;
        $recordsCount = count($posts);

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
        if ($pageRequested * $pageSize >= $recordsCount) {
            return $response->withStatus(404);
        }

        $posts = array_slice($posts, $pageRequested * $pageSize, $pageSize);
        $posts = array_map(
            function ($post) use ($router) {
                $post['urlDetail'] = $router->relativeUrlFor('postshow', ['id' => $post['id']]);
                return $post;
            },
            $posts
        );

        $prevPage = $pageRequested - 1;
        $nextPage = $pageRequested + 1;
        $prevPageUrl = '';
        $nextPageUrl = '';
        if ($pageRequested > 0) {
            $prevPageUrl = sprintf("%s?page=%d", $router->urlFor('posts'), $prevPage + 1);
        }

        if ($nextPage * $pageSize < $recordsCount) {
            $nextPageUrl = sprintf("%s?page=%d", $router->urlFor('posts'), $nextPage + 1);
        }

        $flash = $this->get('flash')->getMessages();

        $params = [
            'flash' => $flash,
            'posts' => $posts,
            'prevPageUrl' => $prevPageUrl,
            'nextPageUrl' => $nextPageUrl,
        ];
        return $this->get('renderer')->render($response, 'posts/index.phtml', $params);
    }
)->setName('posts');
$app->get(
    '/posts/{id}',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response, array $args) use ($repoPosts, $router) {
        $post = $repoPosts->find($args['id']);
        if (empty($post)) {
            return $response->write('Page not found')->withStatus(404);
        }
        $params = [
            'post' => $post,
            'listUrl' => $router->urlFor('posts')
        ];
        return $this->get('renderer')->render($response, 'posts/show.phtml', $params);
    }
)->setName('postshow');
$app->get(
    '/posts/new',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) {
        $params = ['post' => ['name' => '', 'body' => ''], 'errors' => []];
        return $this->get('renderer')->render($response, 'posts/new.phtml', $params);
    }
)->setName('postsnew');
$app->post(
    '/posts',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) use ($repo, $router) {
        $post = $request->getParsedBodyParam('post');
        $errors = (new \App\Validator())->validate($post);
        if (count($errors) === 0) {
            $repo->save($post);
            $this->get('flash')->addMessage('success', 'Post has been created');
            return $response->withRedirect($router->urlFor('posts'), 302);
        }
        $params = ['post' => $post, 'errors' => $errors];
        return $this->get('renderer')->render($response->withStatus(422), 'posts/new.phtml', $params);
    }
);
// END


$companies = App\Generator::generate(100);
$app->get(
    '/',
    function ($request, $response) {
        /** @var \Slim\Flash\Messages $flash */
//        $flash = $this->get('flash');
//        $flash->getMessages();
        $messages = $this->get('flash')->getMessages();
        $params = ['messagesSuccess' => $messages['success'] ?: []];
        return $this->get('renderer')->render($response, 'index.phtml', $params);
    }
);
$app->post(
    '/coursesFlash',
    function (\Slim\Http\ServerRequest $request, \Slim\Http\Response $response) {
        $this->get('flash')->addMessage('success', 'Course Added');
        return $response->withRedirect('/', 302);
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
