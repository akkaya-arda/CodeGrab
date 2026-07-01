<?php

namespace Database\Seeders;

use App\Models\PlatformGuardEmailFilter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        PlatformGuardEmailFilter::truncate();
        \Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();

        PlatformGuardEmailFilter::create([
            'name' => 'Steam',
            'regex' => '/(?i:Steam Guard|Guard kodu|Guard code).{1,150}?\b([A-Z0-9]{5})\b/su',
            'logo' => '',
            'sender' => 'noreply@steampowered.com',
            'subject' => 'Steam Guard Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Epic Games',
            'regex' => '/(?i:Epic Games|security code|güvenlik kodu).{1,150}?\b([0-9]{6})\b/su',
            'logo' => '',
            'sender' => 'help@accts.epicgames.com',
            'subject' => 'Epic Games Security Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Ubisoft',
            'regex' => '/(?i:Ubisoft|verification code|doğrulama kodu).{1,150}?\b([0-9]{6})\b/su',
            'logo' => '',
            'sender' => 'accountsupport@ubi.com',
            'subject' => 'Ubisoft Verification Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Netflix',
            'regex' => '/(?i:Netflix|code|kod).{1,100}?\b([0-9]{4,8})\b/su',
            'logo' => '',
            'sender' => 'info@account.netflix.com',
            'subject' => 'Netflix Verification Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Disney+',
            'regex' => '/(?i:Disney|passcode|kod).{1,100}?\b([0-9]{6})\b/su',
            'logo' => '',
            'sender' => 'disneyplus@mail.disneyplus.com',
            'subject' => 'Disney+ Verification Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'EA App',
            'regex' => '/(?i:EA|security code|güvenlik kodu).{1,150}?\b([0-9]{6})\b/su',
            'logo' => '',
            'sender' => 'EA@e.ea.com',
            'subject' => 'EA Security Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Riot Games',
            'regex' => '/(?i:Riot Games|code|kod).{1,150}?\b([0-9]{6})\b/su',
            'logo' => '',
            'sender' => 'noreply@mail.accounts.riotgames.com',
            'subject' => 'Riot Games Verification Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Rockstar Games',
            'regex' => '/(?i:Rockstar|Social Club|code|kod).{1,150}?\b([0-9]{6})\b/su',
            'logo' => '',
            'sender' => 'noreply@rockstargames.com',
            'subject' => 'Rockstar Games Social Club',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);
    }
}
