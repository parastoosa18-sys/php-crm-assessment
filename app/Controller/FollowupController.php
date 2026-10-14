<?php

declare(strict_types=1);

namespace Assessment\Controller;

use Assessment\Core\Auth;
use Assessment\Core\HttpException;
use Assessment\Core\Request;
use Assessment\Core\Response;
use Assessment\Service\FollowupService;

final class FollowupController
{
    private Auth $auth;
    private FollowupService $followups;

    public function __construct(Auth $auth, FollowupService $followups)
    {
        $this->auth = $auth;
        $this->followups = $followups;
    }

    public function index(Request $request): Response
    {
        $user = $this->auth->user($request);

        return Response::json(
            $this->followups->list($user, $_GET)
        );
    }

    public function show(Request $request, array $params): Response
    {
        $user = $this->auth->user($request);
        $id = $this->idFromParams($params);

        return Response::json(
            $this->followups->get($id, $user)
        );
    }

    public function create(Request $request): Response
    {
        $user = $this->auth->user($request);

        return Response::json(
            $this->followups->create($request->body(), $user),
            201
        );
    }

    public function update(Request $request, array $params): Response
    {
        $user = $this->auth->user($request);
        $id = $this->idFromParams($params);

        return Response::json(
            $this->followups->update($id, $request->body(), $user)
        );
    }

    public function complete(Request $request, array $params): Response
    {
        $user = $this->auth->user($request);
        $id = $this->idFromParams($params);

        return Response::json(
            $this->followups->complete($id, $user)
        );
    }

    public function reopen(Request $request, array $params): Response
    {
        $user = $this->auth->user($request);
        $id = $this->idFromParams($params);

        return Response::json(
            $this->followups->reopen($id, $user)
        );
    }

    private function idFromParams(array $params): int
    {
        $id = filter_var(
            $params['id'] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id === false || $id <= 0) {
            throw new HttpException(404, 'Follow-up not found.');
        }

        return (int) $id;
    }
}
