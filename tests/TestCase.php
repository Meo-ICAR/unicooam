<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // I file temporanei di Excel non vanno nella cache dell'app (cartella creata dal web server, non scrivibile da chi lancia i test).
        $temporary = sys_get_temp_dir().'/unicooam-tests-laravel-excel';
        @mkdir($temporary, 0775, true);
        config(['excel.temporary_files.local_path' => $temporary]);
    }
}
