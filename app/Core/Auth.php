<?php

declare(strict_types=1);

namespace Assessment\Core;

use Assessment\Model\UserModel;

final class Auth
{
    private UserModel $users;

    public function __construct(UserModel $users)
    {
        $this->users = $users;
    }

    public function user(Request $request): array
    {
        $userId = filter_var($request->header('X-User-Id'), FILTER_VALIDATE_INT);
        if (!$userId) {
            throw new HttpException(401, 'The X-User-Id header is required.');
        }

        $user = $this->users->find((int) $userId);
        if ($user === null) {
            throw new HttpException(401, 'Unknown assessment user.');
        }

        return $user;
    }
}

