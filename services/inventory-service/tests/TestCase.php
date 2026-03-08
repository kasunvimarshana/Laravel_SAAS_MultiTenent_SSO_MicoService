<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

// In Laravel 11 the CreatesApplication concern is built into the base TestCase;
// no additional trait is required.
abstract class TestCase extends BaseTestCase {}
