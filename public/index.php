<?php

declare(strict_types=1);

use Assessment\Controller\CustomerController;
use Assessment\Controller\FollowupController;
use Assessment\Controller\HealthController;
use Assessment\Core\Auth;
use Assessment\Core\Database;
use Assessment\Core\Request;
use Assessment\Core\Response;
use Assessment\Core\Router;
use Assessment\Model\CustomerModel;
use Assessment\Model\FollowupModel;
use Assessment\Model\UserModel;
use Assessment\Service\CustomerService;
use Assessment\Service\FollowupService;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require dirname(__DIR__) . '/vendor/autoload.php';

$db = Database::connect();

$users = new UserModel($db);
$customerModel = new CustomerModel($db);
$followupModel = new FollowupModel($db);

$auth = new Auth($users);

$customerService = new CustomerService($customerModel);
$followupService = new FollowupService($followupModel);

$customerController = new CustomerController($auth, $customerService);
$followupController = new FollowupController($auth, $followupService);

$twig = new Environment(
    new FilesystemLoader(dirname(__DIR__) . '/app/View')
);

$router = new Router();

$router->get('/', static function () use ($twig): Response {
    return Response::html($twig->render('home.twig'));
});

$router->get('/health', new HealthController());

$router->get('/api/customers', array($customerController, 'index'));

$router->post('/api/followups', array($followupController, 'create'));
$router->get('/api/followups', array($followupController, 'index'));
$router->get('/api/followups/{id}', array($followupController, 'show'));
$router->put('/api/followups/{id}', array($followupController, 'update'));
$router->post('/api/followups/{id}/complete', array($followupController, 'complete'));
$router->post('/api/followups/{id}/reopen', array($followupController, 'reopen'));

$router->dispatch(Request::fromGlobals())->send();
