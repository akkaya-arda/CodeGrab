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
        // Truncate platforms to start fresh
        PlatformGuardEmailFilter::truncate();

        PlatformGuardEmailFilter::create([
            'name' => 'Steam',
            'regex' => '/(?i:Steam Guard|Guard kodu|Guard code).{1,150}?\b([A-Z0-9]{5})\b/su',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/8/83/Steam_icon_logo.svg',
            'sender' => 'noreply@steampowered.com',
            'subject' => 'Steam Guard Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Epic Games',
            'regex' => '/(?i:Epic Games|security code|güvenlik kodu).{1,150}?\b([0-9]{6})\b/su',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/3/31/Epic_Games_logo.svg',
            'sender' => 'help@accts.epicgames.com',
            'subject' => 'Epic Games Security Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Ubisoft',
            'regex' => '/(?i:Ubisoft|verification code|doğrulama kodu).{1,150}?\b([0-9]{6})\b/su',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/e/e1/Ubisoft_logo.svg',
            'sender' => 'accountsupport@ubi.com',
            'subject' => 'Ubisoft Verification Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Netflix',
            'regex' => '/(?i:Netflix|code|kod).{1,100}?\b([0-9]{4,8})\b/su',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/0/08/Netflix_2015_logo.svg',
            'sender' => 'info@account.netflix.com',
            'subject' => 'Netflix Verification Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Disney+',
            'regex' => '/(?i:Disney|passcode|kod).{1,100}?\b([0-9]{6})\b/su',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/3/3e/Disney%2B_logo.svg',
            'sender' => 'disneyplus@mail.disneyplus.com',
            'subject' => 'Disney+ Verification Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'EA App',
            'regex' => '/(?i:EA|security code|güvenlik kodu).{1,150}?\b([0-9]{6})\b/su',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/e/e5/Electronic_Arts_Logo_2020.svg',
            'sender' => 'EA@e.ea.com',
            'subject' => 'EA Security Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Riot Games',
            'regex' => '/(?i:Riot Games|code|kod).{1,150}?\b([0-9]{6})\b/su',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/1/1a/Riot_Games_logo.svg',
            'sender' => 'noreply@mail.accounts.riotgames.com',
            'subject' => 'Riot Games Verification Code',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);

        PlatformGuardEmailFilter::create([
            'name' => 'Rockstar Games',
            'regex' => '/(?i:Rockstar|Social Club|code|kod).{1,150}?\b([0-9]{6})\b/su',
            'logo' => 'https://upload.wikimedia.org/wikipedia/commons/5/53/Rockstar_Games_Logo.svg',
            'sender' => 'noreply@rockstargames.com',
            'subject' => 'Rockstar Games Social Club',
            'enable_heuristic' => true,
            'grabbing_strategy' => 'heuristic_first'
        ]);
    }
}
