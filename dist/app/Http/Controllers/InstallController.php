<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Setting;

class InstallController extends Controller
{

    public function index()
    {
        if (file_exists(storage_path('installed'))) {
            return redirect('/');
        }

        return view('install');
    }


    public function checkRequirements()
    {
        if (file_exists(storage_path('installed'))) {
            return response()->json(['success' => false, 'message' => 'Application is already installed.'], 403);
        }

        $phpVersion = PHP_VERSION;
        $phpOk = version_compare($phpVersion, '8.3.0', '>=');

        $requiredExtensions = [
            'pdo' => extension_loaded('pdo'),
            'openssl' => extension_loaded('openssl'),
            'mbstring' => extension_loaded('mbstring'),
            'tokenizer' => extension_loaded('tokenizer'),
            'xml' => extension_loaded('xml'),
            'ctype' => extension_loaded('ctype'),
            'json' => extension_loaded('json'),
            'bcmath' => extension_loaded('bcmath'),
            'zip' => extension_loaded('zip'),
        ];

        $extensionsOk = !in_array(false, $requiredExtensions);


        $permissions = [
            'storage' => is_writable(storage_path()),
            'bootstrap_cache' => is_writable(base_path('bootstrap/cache')),
            'env' => is_writable(base_path('.env')) || is_writable(base_path('.env.example')) || is_writable(base_path()),
        ];

        $permissionsOk = !in_array(false, $permissions);

        return response()->json([
            'success' => $phpOk && $extensionsOk && $permissionsOk,
            'data' => [
                'php' => [
                    'current' => $phpVersion,
                    'required' => '>= 8.3.0',
                    'ok' => $phpOk,
                ],
                'extensions' => $requiredExtensions,
                'permissions' => $permissions,
            ]
        ]);
    }


    public function configureDatabase(Request $request)
    {
        if (file_exists(storage_path('installed'))) {
            return response()->json(['success' => false, 'message' => 'Application is already installed.'], 403);
        }

        $data = $request->validate([
            'db_connection' => 'required|string|in:mysql,pgsql,sqlite',
            'db_host' => 'required_unless:db_connection,sqlite|string|nullable',
            'db_port' => 'required_unless:db_connection,sqlite|integer|nullable',
            'db_database' => 'required|string',
            'db_username' => 'required_unless:db_connection,sqlite|string|nullable',
            'db_password' => 'nullable|string',
        ]);

        $connection = $data['db_connection'];
        $database = $data['db_database'];


        if ($connection === 'sqlite') {
            $dbPath = $database;
            if (!str_starts_with($dbPath, '/')) {
                $dbPath = database_path($database);
            }
            if (!file_exists($dbPath)) {
                if (!file_exists(dirname($dbPath))) {
                    mkdir(dirname($dbPath), 0755, true);
                }
                touch($dbPath);
            }
            $database = $dbPath;
        }


        try {
            if ($connection === 'sqlite') {
                $pdo = new \PDO("sqlite:{$database}");
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            } else {
                $host = $data['db_host'];
                $port = $data['db_port'];
                $username = $data['db_username'];
                $password = $data['db_password'] ?? '';

                $pdo = new \PDO("{$connection}:host={$host};port={$port};dbname={$database}", $username, $password, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_TIMEOUT => 4
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ], 400);
        }


        $this->updateEnvFile('DB_CONNECTION', $connection);
        if ($connection === 'sqlite') {
            $this->updateEnvFile('DB_DATABASE', $database);

            $this->updateEnvFile('DB_HOST', '');
            $this->updateEnvFile('DB_PORT', '');
            $this->updateEnvFile('DB_USERNAME', '');
            $this->updateEnvFile('DB_PASSWORD', '');
        } else {
            $this->updateEnvFile('DB_HOST', $data['db_host']);
            $this->updateEnvFile('DB_PORT', $data['db_port']);
            $this->updateEnvFile('DB_DATABASE', $data['db_database']);
            $this->updateEnvFile('DB_USERNAME', $data['db_username']);
            $this->updateEnvFile('DB_PASSWORD', $data['db_password'] ?? '');
        }

        return response()->json([
            'success' => true,
            'message' => 'Database configuration is correct and has been saved.'
        ]);
    }


    public function runInstallation(Request $request)
    {
        if (file_exists(storage_path('installed'))) {
            return response()->json(['success' => false, 'message' => 'Application is already installed.'], 403);
        }

        $data = $request->validate([
            'system_name' => 'required|string|max:255',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'admin_password' => 'required|string|min:8',
            'telegram_bot_token' => 'nullable|string|max:255',
            'telegram_chat_id' => 'nullable|string|max:255',
        ]);

        try {
            Artisan::call('config:clear');

            Artisan::call('migrate', ['--force' => true]);

            Artisan::call('db:seed', ['--force' => true]);
            Artisan::call('db:seed', ['--class' => 'PlatformSeeder']);

            if (empty(env('APP_KEY'))) {
                Artisan::call('key:generate', ['--force' => true]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run migrations and seeds: ' . $e->getMessage()
            ], 500);
        }

        try {
            Setting::setValue('system_name', $data['system_name']);

            if (!empty($data['telegram_bot_token'])) {
                Setting::setValue('telegram_bot_token', $data['telegram_bot_token']);
                Setting::setValue('telegram_enabled', '1');
            }
            if (!empty($data['telegram_chat_id'])) {
                Setting::setValue('telegram_chat_id', $data['telegram_chat_id']);
            }

            $user = User::where('email', $data['admin_email'])->first();
            if ($user) {
                $user->update([
                    'name' => $data['admin_name'],
                    'password' => Hash::make($data['admin_password']),
                ]);
            } else {
                User::create([
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => Hash::make($data['admin_password']),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to write configurations to database: ' . $e->getMessage()
            ], 500);
        }

        try {
            $this->updateEnvFile('ADMIN_NAME', $data['admin_name']);
            $this->updateEnvFile('ADMIN_EMAIL', $data['admin_email']);
            $this->updateEnvFile('ADMIN_PASSWORD', $data['admin_password']);
        } catch (\Exception $e) {
            // Ignore env write failure here if DB config succeeded
        }

        try {
            file_put_contents(storage_path('installed'), date('Y-m-d H:i:s'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to write installation lock file (storage/installed): ' . $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Installation completed successfully!'
        ]);
    }


    private function updateEnvFile($key, $value)
    {
        $path = base_path('.env');

        if (!file_exists($path)) {
            if (file_exists(base_path('.env.example'))) {
                copy(base_path('.env.example'), $path);
            } else {
                touch($path);
            }
        }

        $content = file_get_contents($path);

        if (preg_match('/\s/', $value) || str_contains($value, '#') || str_contains($value, '$')) {
            $value = '"' . str_replace('"', '\\"', $value) . '"';
        }

        $quotedKey = preg_quote($key, '/');
        if (preg_match("/^(?:#\s*)?{$quotedKey}=/m", $content)) {
            $escapedValue = str_replace(['\\', '$'], ['\\\\', '\\$'], $value);
            $content = preg_replace("/^(?:#\s*)?{$quotedKey}=.*/m", "{$key}={$escapedValue}", $content);
        } else {
            $content .= "\n{$key}={$value}";
        }

        file_put_contents($path, $content);
    }
}
