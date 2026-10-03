<?php

declare(strict_types=1);

use Assessment\Controller\CustomerController;
use Assessment\Controller\HealthController;
use Assessment\Core\Auth;
use Assessment\Core\Database;
use Assessment\Core\Request;
use Assessment\Core\Response;
use Assessment\Core\Router;
use Assessment\Model\CustomerModel;
use Assessment\Model\UserModel;
use Assessment\Service\CustomerService;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require dirname(__DIR__) . '/vendor/autoload.php';

$db = Database::connect();
$users = new UserModel($db);
$customerModel = new CustomerModel($db);
$auth = new Auth($users);
$customerService = new CustomerService($customerModel);
$customerController = new CustomerController($auth, $customerService);

$twig = new Environment(new FilesystemLoader(dirname(__DIR__) . '/app/View'));
$router = new Router();

$router->get('/', static function () use ($twig): Response {
    return Response::html($twig->render('home.twig'));
});
$router->get('/health', new HealthController());
$router->get('/api/customers', array($customerController, 'index'));

$router->dispatch(Request::fromGlobals())->send();

