<?php

namespace Tests\Concerns;

use Database\Seeders\HospitalTestSeeder;
use Database\Seeders\PermissionSeeder;

trait SeedsHospital
{
    protected function seedHospital(): void
    {
        $this->seed(HospitalTestSeeder::class);
        $this->seed(PermissionSeeder::class);
    }
}