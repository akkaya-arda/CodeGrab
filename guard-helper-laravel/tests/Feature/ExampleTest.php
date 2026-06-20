<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test redirect to /install when application is not installed.
     */
    public function test_redirects_to_install_when_not_installed(): void
    {
        if (!file_exists(storage_path('installed'))) {
            $response = $this->get('/');
            $response->assertRedirect('/install');

            $installResponse = $this->get('/install');
            $installResponse->assertStatus(200);
        } else {
            $response = $this->get('/');
            $response->assertStatus(200);
        }
    }

    /**
     * Test successful response when application is installed.
     */
    public function test_returns_successful_response_when_installed(): void
    {
        $lockFile = storage_path('installed');
        $createdTempLock = false;

        if (!file_exists($lockFile)) {
            file_put_contents($lockFile, 'test-lock');
            $createdTempLock = true;
        }

        try {
            $response = $this->get('/');
            $response->assertStatus(200);
        } finally {
            if ($createdTempLock && file_exists($lockFile)) {
                unlink($lockFile);
            }
        }
    }
}

