<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Helper - Installation Wizard</title>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 10% 20%, rgb(18, 16, 33) 0%, rgb(31, 23, 50) 90%);
        }

        .font-outfit {
            font-family: 'Outfit', sans-serif;
        }

        .glass-panel {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
        }

        .glass-input:focus {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(99, 102, 241, 0.6);
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.2);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.01);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>
</head>

<body
    class="min-h-screen text-slate-200 flex flex-col justify-center items-center p-4 relative overflow-x-hidden selection:bg-indigo-500 selection:text-white">

    <!-- Floating Glow Effects -->
    <div
        class="absolute w-[400px] h-[400px] rounded-full bg-indigo-650/15 blur-[120px] top-10 left-10 pointer-events-none animate-pulse duration-4000">
    </div>
    <div
        class="absolute w-[400px] h-[400px] rounded-full bg-pink-600/10 blur-[120px] bottom-10 right-10 pointer-events-none animate-pulse duration-3000">
    </div>

    <div class="w-full max-w-3xl z-10">
        <!-- Logo Header -->
        <div class="text-center mb-8">
            <div
                class="inline-flex items-center gap-3 bg-white/5 border border-white/10 px-4 py-2 rounded-full mb-3 backdrop-blur-md">
                <i class="fa-solid fa-wand-magic-sparkles text-indigo-400 text-sm animate-pulse"></i>
                <span class="text-xs font-bold font-outfit uppercase tracking-widest text-slate-300">Installation
                    Wizard</span>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight font-outfit">Setup Guard
                Helper</h1>
            <p class="text-slate-400 text-xs sm:text-sm mt-2 max-w-md mx-auto">Get your security code
                interception platform running in minutes.</p>
        </div>

        <!-- Main Card -->
        <div class="glass-panel rounded-2xl shadow-2xl overflow-hidden flex flex-col md:flex-row min-h-[500px]">

            <!-- Left Steps Panel -->
            <div class="w-full md:w-1/3 bg-black/30 border-r border-white/5 p-6 flex flex-col justify-between">
                <div class="space-y-6">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-server text-indigo-400 text-lg"></i>
                        <span class="font-bold text-white tracking-wide font-outfit">Setup Steps</span>
                    </div>

                    <div class="space-y-5 pt-2 select-none">
                        <!-- Step 1 Indicator -->
                        <div id="step-ind-1"
                            class="flex items-center gap-3 text-xs text-indigo-400 font-bold transition-all duration-300">
                            <div
                                class="w-7 h-7 rounded-full flex items-center justify-center border border-indigo-500/30 bg-indigo-500/10 font-mono">
                                1</div>
                            <span>Requirements Check</span>
                        </div>

                        <!-- Step 2 Indicator -->
                        <div id="step-ind-2"
                            class="flex items-center gap-3 text-xs text-slate-500 font-medium transition-all duration-300">
                            <div
                                class="w-7 h-7 rounded-full flex items-center justify-center border border-white/10 bg-white/5 font-mono">
                                2</div>
                            <span>Database Setup</span>
                        </div>

                        <!-- Step 3 Indicator -->
                        <div id="step-ind-3"
                            class="flex items-center gap-3 text-xs text-slate-500 font-medium transition-all duration-300">
                            <div
                                class="w-7 h-7 rounded-full flex items-center justify-center border border-white/10 bg-white/5 font-mono">
                                3</div>
                            <span>System Settings</span>
                        </div>

                        <!-- Step 4 Indicator -->
                        <div id="step-ind-4"
                            class="flex items-center gap-3 text-xs text-slate-500 font-medium transition-all duration-300">
                            <div
                                class="w-7 h-7 rounded-full flex items-center justify-center border border-white/10 bg-white/5 font-mono">
                                4</div>
                            <span>Execution Progress</span>
                        </div>

                        <!-- Step 5 Indicator -->
                        <div id="step-ind-5"
                            class="flex items-center gap-3 text-xs text-slate-500 font-medium transition-all duration-300">
                            <div
                                class="w-7 h-7 rounded-full flex items-center justify-center border border-white/10 bg-white/5 font-mono">
                                5</div>
                            <span>Complete</span>
                        </div>
                    </div>
                </div>

                <div class="text-[10px] text-slate-500 font-medium mt-6">
                    Guard Helper v1.0.0
                </div>
            </div>

            <!-- Right Content Panel -->
            <div class="w-full md:w-2/3 p-6 sm:p-8 flex flex-col justify-between bg-black/10 relative">

                <!-- STEP 1: Requirements Check -->
                <div id="step-card-1" class="step-card flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white font-outfit">System Check</h3>
                        <p class="text-slate-400 text-xs mt-1">Verify that your PHP server meets all required
                            configurations and folder permissions.</p>

                        <div class="mt-5 space-y-4 max-h-[300px] overflow-y-auto pr-1">
                            <!-- PHP Version -->
                            <div
                                class="flex justify-between items-center p-3 rounded-lg bg-white/2 border border-white/5">
                                <div>
                                    <span class="text-xs font-bold text-slate-200">PHP Version</span>
                                    <span class="block text-[10px] text-slate-400">Required: >= 8.3.0</span>
                                </div>
                                <span id="req-php-val" class="text-xs font-bold text-slate-400">Checking...</span>
                            </div>

                            <!-- PHP Extensions Title -->
                            <div>
                                <span
                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">PHP
                                    Extensions</span>
                                <div id="req-extensions" class="grid grid-cols-2 gap-2 text-xs">
                                    <!-- Dynamic Extension list -->
                                    <div
                                        class="flex justify-between items-center p-2 rounded-lg bg-white/2 border border-white/5 opacity-50">
                                        <span>Checking...</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Folder Permissions Title -->
                            <div class="pt-2">
                                <span
                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Folder
                                    Permissions</span>
                                <div id="req-permissions" class="space-y-2 text-xs">
                                    <!-- Dynamic Permissions list -->
                                    <div
                                        class="flex justify-between items-center p-2 rounded-lg bg-white/2 border border-white/5 opacity-50">
                                        <span>Checking...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-5 border-t border-white/5 mt-5">
                        <button type="button" id="btn-next-1" disabled onclick="nextStep()"
                            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:hover:bg-indigo-600 text-white font-bold text-xs tracking-wider rounded-lg transition cursor-pointer shadow-md flex items-center gap-2">
                            <span>Continue</span> <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Database Settings -->
                <div id="step-card-2" class="step-card hidden flex-1 flex flex-col justify-between">
                    <form id="form-database" onsubmit="submitDatabase(event)"
                        class="flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white font-outfit">Database Configuration</h3>
                            <p class="text-slate-400 text-xs mt-1">Configure your database driver and connection
                                credentials.</p>

                            <div class="mt-5 space-y-4">
                                <!-- Connection Driver -->
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Database
                                        Connection</label>
                                    <select name="db_connection" onchange="toggleDatabaseFields()"
                                        class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none cursor-pointer">
                                        <option value="mysql">MySQL / MariaDB</option>
                                        <option value="pgsql">PostgreSQL</option>
                                        <option value="sqlite">SQLite (File-based)</option>
                                    </select>
                                </div>

                                <!-- Host & Port Grid -->
                                <div id="db-host-port-row" class="grid grid-cols-3 gap-3">
                                    <div class="col-span-2">
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Host
                                            IP / Domain</label>
                                        <input type="text" name="db_host" value="127.0.0.1" placeholder="e.g. 127.0.0.1"
                                            autocomplete="off"
                                            class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none" />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Port</label>
                                        <input type="number" name="db_port" value="3306" placeholder="3306"
                                            autocomplete="off"
                                            class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none" />
                                    </div>
                                </div>

                                <!-- Database name or file -->
                                <div>
                                    <label id="db-name-label"
                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Database
                                        Name</label>
                                    <input type="text" name="db_database" value="guard_helper"
                                        placeholder="e.g. guard_helper" autocomplete="off" required
                                        class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none" />
                                </div>

                                <!-- User & Password row -->
                                <div id="db-credentials-row" class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Username</label>
                                        <input type="text" name="db_username" value="root" placeholder="e.g. root"
                                            autocomplete="off"
                                            class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none" />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Password</label>
                                        <input type="password" name="db_password" placeholder="Leave empty if none"
                                            autocomplete="off"
                                            class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-5 border-t border-white/5 mt-5">
                            <button type="button" onclick="prevStep()"
                                class="px-4 py-2 border border-white/10 hover:bg-white/5 text-slate-400 font-semibold text-xs rounded-lg transition cursor-pointer">
                                Back
                            </button>
                            <button type="submit" id="btn-db-submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs tracking-wider rounded-lg transition cursor-pointer shadow-md flex items-center gap-2">
                                <i class="fa-solid fa-spinner animate-spin hidden" id="db-spinner"></i>
                                <span>Verify Database</span> <i class="fa-solid fa-chevron-right text-[10px]"
                                    id="db-chevron"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- STEP 3: Admin & System Setup -->
                <div id="step-card-3" class="step-card hidden flex-1 flex flex-col justify-between">
                    <form id="form-admin" onsubmit="submitAdmin(event)" class="flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white font-outfit">System & Admin Setup</h3>
                            <p class="text-slate-400 text-xs mt-1">Configure your system brand name and default
                                administrator details.</p>

                            <div class="mt-5 space-y-4 max-h-[300px] overflow-y-auto pr-1">
                                <!-- System Name -->
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">System
                                        Name</label>
                                    <input type="text" name="system_name" value="Guard Helper"
                                        placeholder="e.g. Guard Helper" autocomplete="off" required
                                        class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none" />
                                </div>

                                <div class="border-t border-white/5 my-2"></div>

                                <!-- Administrator Credentials Title -->
                                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">ADMIN
                                    ACCOUNT CREDENTIALS</span>

                                <!-- Admin Name -->
                                <div>
                                    <label
                                        class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Full
                                        Name</label>
                                    <input type="text" name="admin_name" value="Administrator"
                                        placeholder="e.g. John Doe" autocomplete="off" required
                                        class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none" />
                                </div>

                                <!-- Email & Password -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Email
                                            Address</label>
                                        <input type="email" name="admin_email" value="admin@example.com"
                                            placeholder="admin@domain.com" autocomplete="off" required
                                            class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none" />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Admin
                                            Password</label>
                                        <input type="password" name="admin_password" value="admin12345"
                                            placeholder="Min. 8 characters" autocomplete="off" required
                                            class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none" />
                                    </div>
                                </div>

                                <div class="border-t border-white/5 my-2"></div>

                                <!-- Optional Telegram Channel Module -->
                                <span
                                    class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">OPTIONAL
                                    TELEGRAM NOTIFICATION BOT</span>

                                <!-- Bot token & Chat ID -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Bot
                                            Token</label>
                                        <input type="text" name="telegram_bot_token" placeholder="BotFather Token"
                                            autocomplete="off"
                                            class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none" />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Chat
                                            ID</label>
                                        <input type="text" name="telegram_chat_id" placeholder="Telegram Chat ID"
                                            autocomplete="off"
                                            class="w-full glass-input text-xs text-slate-200 rounded-lg px-3 py-2 outline-none" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-5 border-t border-white/5 mt-5">
                            <button type="button" onclick="prevStep()"
                                class="px-4 py-2 border border-white/10 hover:bg-white/5 text-slate-400 font-semibold text-xs rounded-lg transition cursor-pointer">
                                Back
                            </button>
                            <button type="submit"
                                class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs tracking-wider rounded-lg transition cursor-pointer shadow-md flex items-center gap-2">
                                <span>Save & Install</span> <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- STEP 4: Execution Progress -->
                <div id="step-card-4" class="step-card hidden flex-1 flex flex-col justify-between">
                    <div class="flex-1 flex flex-col justify-center py-6 text-center space-y-6">
                        <!-- Spinning loader -->
                        <div class="relative w-16 h-16 mx-auto flex items-center justify-center">
                            <div class="absolute inset-0 border-4 border-indigo-500/20 rounded-full"></div>
                            <div class="absolute inset-0 border-4 border-t-indigo-600 rounded-full animate-spin"></div>
                        </div>

                        <div class="space-y-2">
                            <h3 class="text-lg font-bold text-white font-outfit" id="exec-title">Running Installation
                            </h3>
                            <p class="text-slate-400 text-xs" id="exec-desc">Writing configuration files and setting up
                                database tables...</p>
                        </div>

                        <!-- Progress timeline status -->
                        <div
                            class="w-full max-w-sm mx-auto text-left space-y-3 pt-4 font-semibold text-xs text-slate-400">
                            <div class="flex items-center gap-3" id="prog-step-1">
                                <i class="fa-solid fa-spinner animate-spin text-indigo-400 text-xs"></i>
                                <span>Save configuration parameters to system environment</span>
                            </div>
                            <div class="flex items-center gap-3 opacity-30" id="prog-step-2">
                                <i class="fa-solid fa-circle text-[8px]"></i>
                                <span>Run database schema migrations and seeding</span>
                            </div>
                            <div class="flex items-center gap-3 opacity-30" id="prog-step-3">
                                <i class="fa-solid fa-circle text-[8px]"></i>
                                <span>Generate secure application crypt keys</span>
                            </div>
                            <div class="flex items-center gap-3 opacity-30" id="prog-step-4">
                                <i class="fa-solid fa-circle text-[8px]"></i>
                                <span>Write lock settings and clean caches</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 5: Success Completed -->
                <div id="step-card-5" class="step-card hidden flex-1 flex flex-col justify-between">
                    <div class="flex-1 flex flex-col justify-center py-6 text-center space-y-6">
                        <!-- Checked circle -->
                        <div
                            class="w-16 h-16 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 rounded-full flex items-center justify-center text-3xl mx-auto shadow-lg shadow-emerald-500/10">
                            <i class="fa-solid fa-check"></i>
                        </div>

                        <div class="space-y-1">
                            <h3 class="text-xl font-bold text-white tracking-wide font-outfit">Setup Completed
                                Successfully!</h3>
                            <p class="text-slate-400 text-xs max-w-sm mx-auto">Guard Helper is now completely
                                configured and ready for production.</p>
                        </div>

                        <!-- Dashboard Access Links Grid -->
                        <div class="grid grid-cols-2 gap-4 max-w-md mx-auto pt-4 w-full">
                            <!-- Administration panel -->
                            <a href="/settings" id="link-admin-panel" target="_blank"
                                class="p-4 border border-white/5 hover:border-indigo-500/30 bg-white/2 hover:bg-indigo-500/5 rounded-xl transition text-left group">
                                <div
                                    class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                                    <i class="fa-solid fa-toolbox text-sm"></i>
                                </div>
                                <span class="block text-xs font-bold text-white mb-0.5">Admin Settings</span>
                                <span class="block text-[10px] text-slate-500 font-medium">Configure SMTP, mail filters,
                                    and tokens.</span>
                            </a>

                            <!-- Public portal -->
                            <a href="/" id="link-public-portal" target="_blank"
                                class="p-4 border border-white/5 hover:border-indigo-500/30 bg-white/2 hover:bg-indigo-500/5 rounded-xl transition text-left group">
                                <div
                                    class="w-8 h-8 rounded-lg bg-indigo-500/10 text-indigo-400 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                                    <i class="fa-solid fa-right-to-bracket text-sm"></i>
                                </div>
                                <span class="block text-xs font-bold text-white mb-0.5">Public Portal</span>
                                <span class="block text-[10px] text-slate-500 font-medium">Test credentials check &
                                    secure 2FA code fetching.</span>
                            </a>
                        </div>
                    </div>

                    <div class="flex justify-center pt-5 border-t border-white/5 mt-5">
                        <a href="/"
                            class="px-6 py-2.5 bg-slate-200 hover:bg-white text-slate-900 font-extrabold text-xs tracking-wider rounded-lg transition shadow-md">
                            Go to Dashboard Welcome
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script>
        let currentStep = 1;
        let adminPayload = {};

        // Run requirements check on start
        window.addEventListener('DOMContentLoaded', () => {
            // Set public portal link based on origin
            const linkPublic = document.getElementById('link-public-portal');
            linkPublic.href = window.location.origin.replace(':8000', ':4200');

            checkRequirements();
        });

        // Trigger requirement check request
        function checkRequirements() {
            fetch('/install/check', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
                .then(res => res.json())
                .then(res => {
                    // Populate PHP check
                    const phpVal = document.getElementById('req-php-val');
                    phpVal.innerText = res.data.php.current;
                    phpVal.className = 'text-xs font-bold ' + (res.data.php.ok ? 'text-emerald-400' : 'text-red-400');

                    // Populate Extensions list
                    const extContainer = document.getElementById('req-extensions');
                    extContainer.innerHTML = '';
                    for (const [ext, ok] of Object.entries(res.data.extensions)) {
                        extContainer.innerHTML += `
                        <div class="flex justify-between items-center p-2 rounded-lg bg-white/2 border border-white/5">
                            <span class="text-slate-300 font-medium">${ext}</span>
                            <span class="font-bold font-mono text-[10px] ${ok ? 'text-emerald-400' : 'text-red-400'}">
                                ${ok ? '<i class="fa-solid fa-check mr-0.5"></i> Loaded' : '<i class="fa-solid fa-xmark mr-0.5"></i> Missing'}
                            </span>
                        </div>
                    `;
                    }

                    // Populate Permissions list
                    const permContainer = document.getElementById('req-permissions');
                    permContainer.innerHTML = '';
                    const labels = {
                        storage: 'storage/ (Storage & logs caching)',
                        bootstrap_cache: 'bootstrap/cache/ (Configuration caching)',
                        env: '.env / Root Directory (Settings storage)'
                    };
                    for (const [dir, ok] of Object.entries(res.data.permissions)) {
                        permContainer.innerHTML += `
                        <div class="flex justify-between items-center p-2 rounded-lg bg-white/2 border border-white/5">
                            <span class="text-slate-300 font-medium">${labels[dir] || dir}</span>
                            <span class="font-bold font-mono text-[10px] ${ok ? 'text-emerald-400' : 'text-red-400'}">
                                ${ok ? '<i class="fa-solid fa-circle-check mr-0.5"></i> Writable' : '<i class="fa-solid fa-circle-xmark mr-0.5"></i> Protected'}
                            </span>
                        </div>
                    `;
                    }

                    // Activate Next button if requirements pass
                    if (res.success) {
                        document.getElementById('btn-next-1').removeAttribute('disabled');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Connection to installation endpoint failed. Verify your PHP server is running.');
                });
        }

        // Toggle database form fields based on selected connection type
        function toggleDatabaseFields() {
            const select = document.querySelector('select[name="db_connection"]');
            const rowHost = document.getElementById('db-host-port-row');
            const rowCreds = document.getElementById('db-credentials-row');
            const nameLabel = document.getElementById('db-name-label');
            const dbInput = document.querySelector('input[name="db_database"]');

            if (select.value === 'sqlite') {
                rowHost.classList.add('hidden');
                rowCreds.classList.add('hidden');
                nameLabel.innerText = 'SQLite Database Path';
                dbInput.value = 'database.sqlite';
                dbInput.placeholder = 'e.g. database.sqlite';
            } else {
                rowHost.classList.remove('hidden');
                rowCreds.classList.remove('hidden');
                nameLabel.innerText = 'Database Name';
                dbInput.value = 'guard_helper';
                dbInput.placeholder = 'e.g. guard_helper';
            }
        }

        // Submit database verification request
        function submitDatabase(e) {
            e.preventDefault();

            const spinner = document.getElementById('db-spinner');
            const chevron = document.getElementById('db-chevron');
            const submitBtn = document.getElementById('btn-db-submit');

            spinner.classList.remove('hidden');
            chevron.classList.add('hidden');
            submitBtn.setAttribute('disabled', 'true');

            const form = document.getElementById('form-database');
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());

            fetch('/install/database', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            })
                .then(res => {
                    if (!res.ok) {
                        return res.json().then(err => { throw err; });
                    }
                    return res.json();
                })
                .then(res => {
                    spinner.classList.add('hidden');
                    chevron.classList.remove('hidden');
                    submitBtn.removeAttribute('disabled');

                    nextStep();
                })
                .catch(err => {
                    spinner.classList.add('hidden');
                    chevron.classList.remove('hidden');
                    submitBtn.removeAttribute('disabled');
                    alert(err.message || 'Database validation failed. Ensure credentials and connection hosts are correct.');
                });
        }

        // Submit System Branding & Administrator details
        function submitAdmin(e) {
            e.preventDefault();

            const form = document.getElementById('form-admin');
            const formData = new FormData(form);
            adminPayload = Object.fromEntries(formData.entries());

            nextStep();
            runFinalInstallation();
        }

        // Execute migrations, seed, generate app key, and lock file
        function runFinalInstallation() {
            const step1 = document.getElementById('prog-step-1');
            const step2 = document.getElementById('prog-step-2');
            const step3 = document.getElementById('prog-step-3');
            const step4 = document.getElementById('prog-step-4');

            fetch('/install/run', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(adminPayload)
            })
                .then(res => {
                    if (!res.ok) {
                        return res.json().then(err => { throw err; });
                    }
                    return res.json();
                })
                .then(res => {
                    // Step 1 Success
                    step1.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-400 text-xs"></i> <span>Configuration values saved successfully</span>';
                    step1.className = 'flex items-center gap-3 text-emerald-400 font-bold';

                    // Step 2 Progress
                    step2.className = 'flex items-center gap-3 text-indigo-400 font-bold';
                    step2.innerHTML = '<i class="fa-solid fa-spinner animate-spin text-indigo-400 text-xs"></i> <span>Running database migrations and seeds...</span>';

                    setTimeout(() => {
                        step2.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-400 text-xs"></i> <span>Database structure & tables initialized</span>';
                        step2.className = 'flex items-center gap-3 text-emerald-400 font-bold';

                        // Step 3 Progress
                        step3.className = 'flex items-center gap-3 text-indigo-400 font-bold';
                        step3.innerHTML = '<i class="fa-solid fa-spinner animate-spin text-indigo-400 text-xs"></i> <span>Generating secure application crypt keys...</span>';

                        setTimeout(() => {
                            step3.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-400 text-xs"></i> <span>Application cryptographic keys generated</span>';
                            step3.className = 'flex items-center gap-3 text-emerald-400 font-bold';

                            // Step 4 Progress
                            step4.className = 'flex items-center gap-3 text-indigo-400 font-bold';
                            step4.innerHTML = '<i class="fa-solid fa-spinner animate-spin text-indigo-400 text-xs"></i> <span>Writing lock settings & clearing cache...</span>';

                            setTimeout(() => {
                                step4.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-400 text-xs"></i> <span>Settings locked. Clear optimization caches</span>';
                                step4.className = 'flex items-center gap-3 text-emerald-400 font-bold';

                                setTimeout(() => {
                                    nextStep();
                                }, 800);

                            }, 1000);
                        }, 1000);
                    }, 1200);
                })
                .catch(err => {
                    document.getElementById('exec-title').innerText = 'Installation Failed';
                    document.getElementById('exec-desc').innerText = 'An error occurred during database migration or seeder execution.';

                    step1.className = 'flex items-center gap-3 text-red-400 font-bold';
                    step1.innerHTML = `<i class="fa-solid fa-circle-xmark text-red-400 text-xs"></i> <span>${err.message || 'Artisan command execution failed.'}</span>`;
                    alert(err.message || 'Installation execution failed. Please verify that your database permissions are set up correctly.');
                });
        }

        // Navigate between steps
        function nextStep() {
            goToStep(currentStep + 1);
        }

        function prevStep() {
            goToStep(currentStep - 1);
        }

        function goToStep(step) {
            // Validate step boundaries
            if (step < 1 || step > 5) return;

            // Hide old card
            document.getElementById(`step-card-${currentStep}`).classList.add('hidden');

            // Set indicator classes
            document.getElementById(`step-ind-${currentStep}`).className = 'flex items-center gap-3 text-xs text-slate-500 font-medium transition-all duration-300';
            const oldInd = document.getElementById(`step-ind-${currentStep}`).firstElementChild;
            oldInd.className = 'w-7 h-7 rounded-full flex items-center justify-center border border-white/10 bg-white/5 font-mono';

            // Show new card
            document.getElementById(`step-card-${step}`).classList.remove('hidden');

            // Update indicator state
            document.getElementById(`step-ind-${step}`).className = 'flex items-center gap-3 text-xs text-indigo-400 font-bold transition-all duration-300';
            const newInd = document.getElementById(`step-ind-${step}`).firstElementChild;
            newInd.className = 'w-7 h-7 rounded-full flex items-center justify-center border border-indigo-500 bg-indigo-600 text-white font-mono';

            currentStep = step;
        }
    </script>
</body>

</html>