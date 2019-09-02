<?php

namespace App\Tests\Routes\Wiki\Managers;

use App\Tests\TestCase;
use App\Tests\Routes\Traits\OptionsRequestAllowed;
use App\Tests\Routes\Traits\CrossSiteHeadersOnOptions;

class CreateTest extends TestCase
{
    protected $route = 'wiki/create';

    use CrossSiteHeadersOnOptions;
    use OptionsRequestAllowed;
}
