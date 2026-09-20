<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    /** Where tests may create fake badge artwork; removed after every test. */
    protected const TEST_BADGE_IMAGES = 'images/_test_badges';

    protected function setUp(): void
    {
        parent::setUp();

        // Tests must never see, overwrite or delete the real artwork in
        // public/images/badges.
        config(['badges.images_path' => self::TEST_BADGE_IMAGES]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(public_path(self::TEST_BADGE_IMAGES));

        parent::tearDown();
    }
}
