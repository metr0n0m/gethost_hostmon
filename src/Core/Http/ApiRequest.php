<?php

declare(strict_types=1);

namespace GetHost\Core\Http;

final class ApiRequest
{
    private string $module;

    private string $search;

    public function __construct(string $module, string $search)
    {
        $this->module = trim(strtolower($module));
        $this->search = trim($search);
    }

    public function module(): string
    {
        return $this->module;
    }

    public function search(): string
    {
        return $this->search;
    }
}

