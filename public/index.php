<?php

use Slim\Factory\AppFactory;

require 'vendor/autoload.php';

$companies = App\Generator::generate(100);

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

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

$app->run();
