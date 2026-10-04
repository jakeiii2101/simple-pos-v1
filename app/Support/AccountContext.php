<?php

namespace App\Support;

class AccountContext
{
    private ?int $accountId = null;

    public function set(?int $accountId): void
    {
        $this->accountId = $accountId;
    }

    public function id(): ?int
    {
        return $this->accountId;
    }

    public function clear(): void
    {
        $this->accountId = null;
    }
}
