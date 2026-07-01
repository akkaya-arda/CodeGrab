<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class InstallTest extends TestCase
{
    public function test_database_connection_validation_detects_mac_mismatch(): void
    {
        $lockFile = storage_path('installed');
        $existed = file_exists($lockFile);
        if ($existed) {
            unlink($lockFile);
        }

        $tempDb = storage_path('temp_test_db.sqlite');
        if (file_exists($tempDb)) {
            unlink($tempDb);
        }
        touch($tempDb);

        try {
            config([
                'database.connections.temp_check' => [
                    'driver' => 'sqlite',
                    'database' => $tempDb,
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                ]
            ]);

            Schema::connection('temp_check')->create('account_bundles', function ($table) {
                $table->id();
                $table->string('name');
                $table->string('email');
                $table->string('platform');
                $table->text('password');
                $table->timestamps();
            });

            $oldKey = 'base64:' . base64_encode(random_bytes(32));
            config(['app.key' => $oldKey]);
            $encryptedPassword = Crypt::encryptString('secret-password');

            \Illuminate\Support\Facades\DB::connection('temp_check')->table('account_bundles')->insert([
                'name' => 'Test',
                'email' => 'test@test.com',
                'platform' => 'Steam',
                'password' => $encryptedPassword,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $newKey = 'base64:' . base64_encode(random_bytes(32));

            $response = $this->postJson('/install/database', [
                'db_connection' => 'sqlite',
                'db_database' => $tempDb,
                'app_key' => $newKey,
            ]);

            $response->assertStatus(400)
                ->assertJsonPath('error_type', 'mac_mismatch');

            $response = $this->postJson('/install/database', [
                'db_connection' => 'sqlite',
                'db_database' => $tempDb,
                'app_key' => $newKey,
                'wipe_database' => true,
            ]);

            $response->assertStatus(200);

            $this->assertFalse(Schema::connection('temp_check')->hasTable('account_bundles'));

        } finally {
            if (file_exists($tempDb)) {
                unlink($tempDb);
            }
            if ($existed) {
                file_put_contents($lockFile, 'test-lock');
            }
        }
    }
}
