<?php

namespace App\Extensions;

use Illuminate\Session\DatabaseSessionHandler;

class SessionLogger extends DatabaseSessionHandler
{
    public function read($sessionId): string|false
    {
        $result = parent::read($sessionId);

        if (empty($result)) {
            $this->exists = false;
        }

        return $result;
    }

    public function write($sessionId, $data): bool
    {
        return parent::write($sessionId, $data);
    }

    public function destroy($sessionId): bool
    {
        return parent::destroy($sessionId);
    }

    public function gc($lifetime): int
    {
        return parent::gc($lifetime);
    }
}
