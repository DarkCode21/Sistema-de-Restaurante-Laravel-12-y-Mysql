<?php

namespace Tests;

use App\Models\Company;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (config('permission.teams') && Company::query()->exists()) {
            setPermissionsTeamId(Company::query()->value('id'));
        }
    }
}
