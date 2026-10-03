<?php

declare(strict_types=1);

namespace Assessment\Controller;

use Assessment\Core\Auth;
use Assessment\Core\Request;
use Assessment\Core\Response;
use Assessment\Service\CustomerService;

final class CustomerController
{
    private Auth $auth;
    private CustomerService $customers;

    public function __construct(Auth $auth, CustomerService $customers)
    {
        $this->auth = $auth;
        $this->customers = $customers;
    }

    public function index(Request $request): Response
    {
        $user = $this->auth->user($request);

        return Response::json(array('data' => $this->customers->listVisible($user)));
    }
}

