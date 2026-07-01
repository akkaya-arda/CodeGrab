<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_redirects_to_install_when_not_installed(): void
    {
        $lockFile = storage_path('installed');
        $existed = file_exists($lockFile);
        if ($existed) {
            unlink($lockFile);
        }
        try {
            $response = $this->get('/');
            $response->assertRedirect('/install');

            $installResponse = $this->get('/install');
            $installResponse->assertStatus(200);
        } finally {
            if ($existed) {
                file_put_contents($lockFile, 'test-lock');
            }
        }
    }

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
            $response->assertRedirect('/grab-code');
        } finally {
            if ($createdTempLock && file_exists($lockFile)) {
                unlink($lockFile);
            }
        }
    }
}
