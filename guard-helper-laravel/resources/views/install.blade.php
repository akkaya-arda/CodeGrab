<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guard Helper - Installation Wizard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f4f6f9;
            color: #333;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        .installer-container {
            margin-top: 50px;
            margin-bottom: 50px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #d1d4d7;
            border-radius: 4px;
            background-color: #fff;
            overflow: hidden;
        }

        .installer-sidebar {
            background-color: #2f353a;
            color: #fff;
            padding: 30px 20px;
            min-height: 550px;
        }

        .installer-sidebar h4 {
            border-bottom: 1px solid #4f5d73;
            padding-bottom: 15px;
            margin-bottom: 25px;
            font-size: 18px;
            font-weight: 600;
        }

        .step-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
            opacity: 0.6;
            transition: all 0.3s;
        }

        .step-item.active {
            opacity: 1;
            font-weight: bold;
        }

        .step-item.completed {
            opacity: 0.8;
            color: #20a8d8;
        }

        .step-number {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            border: 2px solid #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .step-item.completed .step-number {
            background-color: #20a8d8;
            border-color: #20a8d8;
            color: #fff;
        }

        .step-item.active .step-number {
            border-color: #20a8d8;
            background-color: transparent;
            color: #20a8d8;
        }

        .installer-content {
            padding: 40px 30px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 550px;
        }

        .installer-content h3 {
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2f353a;
        }

        .installer-content p.subtitle {
            color: #73818f;
            font-size: 13px;
            margin-bottom: 30px;
        }

        .form-group label {
            font-weight: 600;
            font-size: 12px;
            color: #5c6873;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            border: 1px solid #e4e7ea;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 13px;
            height: auto;
        }

        .form-control:focus {
            border-color: #8ad4ee;
            box-shadow: 0 0 0 0.2rem rgba(32, 168, 216, .25);
        }

        .btn {
            font-size: 13px;
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 4px;
        }

        .btn-primary {
            background-color: #20a8d8;
            border-color: #20a8d8;
        }

        .btn-primary:hover,
        .btn-primary:focus {
            background-color: #1b8eb7;
            border-color: #1b8eb7;
        }

        .btn-secondary {
            background-color: #c8ced3;
            border-color: #c8ced3;
            color: #2f353a;
        }

        .btn-secondary:hover {
            background-color: #b8bec2;
            border-color: #b8bec2;
            color: #2f353a;
        }

        .requirement-row {
            padding: 10px 15px;
            border: 1px solid #e4e7ea;
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .badge-success {
            background-color: #4dbd74;
        }

        .badge-danger {
            background-color: #f86c6b;
        }

        .option-box {
            border: 1px solid #e4e7ea;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .option-box:hover {
            border-color: #20a8d8;
            background-color: #f8f9fa;
        }

        .option-box.active {
            border-color: #20a8d8;
            background-color: #ebf5fa;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-md-12">
                <div class="row installer-container">
                    <div class="col-md-4 installer-sidebar">
                        <h4>Setup Wizard</h4>
                        <div class="step-list">
                            <div id="step-ind-1" class="step-item active">
                                <div class="step-number">1</div>
                                <span>Application Key</span>
                            </div>
                            <div id="step-ind-2" class="step-item">
                                <div class="step-number">2</div>
                                <span>Requirements Check</span>
                            </div>
                            <div id="step-ind-3" class="step-item">
                                <div class="step-number">3</div>
                                <span>Database Setup</span>
                            </div>
                            <div id="step-ind-4" class="step-item">
                                <div class="step-number">4</div>
                                <span>System Settings</span>
                            </div>
                            <div id="step-ind-5" class="step-item">
                                <div class="step-number">5</div>
                                <span>Installation Progress</span>
                            </div>
                            <div id="step-ind-6" class="step-item">
                                <div class="step-number">6</div>
                                <span>Completed</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 installer-content">
                        <div id="step-card-1" class="step-card">
                            <div>
                                <h3>Application Key Configuration</h3>
                                <p class="subtitle">Specify the encryption key for securing sessions and values in your
                                    app.</p>
                                <div class="option-box active" id="opt-generate" onclick="selectKeyOption('generate')">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="key-opt-generate" name="key_option"
                                            class="custom-control-input" checked>
                                        <label class="custom-control-label font-weight-bold text-dark"
                                            for="key-opt-generate">Generate APP_KEY automatically (Recommended)</label>
                                    </div>
                                    <small class="text-muted d-block mt-2">Creates a unique cryptographic key for your
                                        installation.</small>
                                </div>
                                <div class="option-box" id="opt-provide" onclick="selectKeyOption('provide')">
                                    <div class="custom-control custom-radio">
                                        <input type="radio" id="key-opt-provide" name="key_option"
                                            class="custom-control-input">
                                        <label class="custom-control-label font-weight-bold text-dark"
                                            for="key-opt-provide">I have an existing APP_KEY</label>
                                    </div>
                                    <small class="text-muted d-block mt-2 font-italic">Use this option if you are
                                        migrating or redeploying an existing setup.</small>
                                </div>
                                <div id="key-input-container" class="form-group mt-3 d-none">
                                    <label for="custom-app-key">Enter APP_KEY</label>
                                    <input type="text" id="custom-app-key" class="form-control"
                                        placeholder="e.g. base64:abc...">
                                    <div id="key-validation-error" class="text-danger mt-1 font-weight-bold d-none"
                                        style="font-size: 11px;">APP_KEY must start with base64: and be of proper
                                        format.</div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end border-t pt-3">
                                <button type="button" class="btn btn-primary" onclick="validateKeyStep()">
                                    <span>Continue</span> <i class="fa-solid fa-chevron-right ml-1"></i>
                                </button>
                            </div>
                        </div>

                        <div id="step-card-2" class="step-card d-none">
                            <div>
                                <h3>System Check</h3>
                                <p class="subtitle">Ensure PHP environment requirements and write permissions are met.
                                </p>
                                <div id="req-list">
                                    <div class="requirement-row d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>PHP Version</strong>
                                            <div class="text-muted" style="font-size:11px;">Required: >= 8.3.0</div>
                                        </div>
                                        <span id="req-php-val" class="badge badge-secondary">Checking...</span>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold text-secondary mb-2" style="font-size:11px;">PHP
                                            EXTENSIONS</div>
                                        <div id="req-extensions" class="row">
                                            <div class="col-6">Checking...</div>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <div class="font-weight-bold text-secondary mb-2" style="font-size:11px;">WRITE
                                            PERMISSIONS</div>
                                        <div id="req-permissions">
                                            Checking...
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between border-t pt-3 mt-3">
                                <button type="button" class="btn btn-secondary" onclick="prevStep()">Back</button>
                                <button type="button" id="btn-next-2" disabled class="btn btn-primary"
                                    onclick="nextStep()">
                                    <span>Continue</span> <i class="fa-solid fa-chevron-right ml-1"></i>
                                </button>
                            </div>
                        </div>

                        <div id="step-card-3" class="step-card d-none">
                            <form id="form-database" onsubmit="submitDatabase(event)">
                                <div>
                                    <h3>Database Connection</h3>
                                    <p class="subtitle">Configure your database driver and setup parameters.</p>
                                    <div class="form-group">
                                        <label>Database Connection</label>
                                        <select name="db_connection" onchange="toggleDatabaseFields()"
                                            class="form-control">
                                            <option value="mysql">MySQL / MariaDB</option>
                                            <option value="pgsql">PostgreSQL</option>
                                            <option value="sqlite">SQLite (File-based)</option>
                                        </select>
                                    </div>
                                    <div id="db-host-port-row" class="row">
                                        <div class="col-8 form-group">
                                            <label>Host / IP</label>
                                            <input type="text" name="db_host" value="127.0.0.1" class="form-control">
                                        </div>
                                        <div class="col-4 form-group">
                                            <label>Port</label>
                                            <input type="number" name="db_port" value="3306" class="form-control">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label id="db-name-label">Database Name</label>
                                        <input type="text" name="db_database" value="guard_helper" required
                                            class="form-control">
                                    </div>
                                    <div id="db-credentials-row" class="row">
                                        <div class="col-6 form-group">
                                            <label>Username</label>
                                            <input type="text" name="db_username" value="root" class="form-control">
                                        </div>
                                        <div class="col-6 form-group">
                                            <label>Password</label>
                                            <input type="password" name="db_password" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between border-t pt-3 mt-3">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep()">Back</button>
                                    <button type="submit" id="btn-db-submit" class="btn btn-primary">
                                        <i class="fa-solid fa-spinner fa-spin d-none" id="db-spinner"></i>
                                        <span id="db-btn-text">Verify Connection</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div id="step-card-4" class="step-card d-none">
                            <form id="form-admin" onsubmit="submitAdmin(event)">
                                <div>
                                    <h3>System Configurations</h3>
                                    <p class="subtitle">Enter application name and setup administrator credentials.</p>
                                    <div class="form-group">
                                        <label>System / Site Name</label>
                                        <input type="text" name="system_name" value="Guard Helper" required
                                            class="form-control">
                                    </div>
                                    <div class="font-weight-bold text-secondary mb-2" style="font-size:11px;">
                                        ADMINISTRATOR ACCOUNT</div>
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" name="admin_name" value="Administrator" required
                                            class="form-control">
                                    </div>
                                    <div class="row">
                                        <div class="col-6 form-group">
                                            <label>Email Address</label>
                                            <input type="email" name="admin_email" value="admin@example.com" required
                                                class="form-control">
                                        </div>
                                        <div class="col-6 form-group">
                                            <label>Password</label>
                                            <input type="password" name="admin_password" value="admin12345" required
                                                class="form-control">
                                        </div>
                                    </div>
                                    <div class="font-weight-bold text-secondary mb-2" style="font-size:11px;">TELEGRAM
                                        BOT (OPTIONAL)</div>
                                    <div class="row">
                                        <div class="col-6 form-group">
                                            <label>Bot Token</label>
                                            <input type="text" name="telegram_bot_token" class="form-control">
                                        </div>
                                        <div class="col-6 form-group">
                                            <label>Chat ID</label>
                                            <input type="text" name="telegram_chat_id" class="form-control">
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between border-t pt-3 mt-3">
                                    <button type="button" class="btn btn-secondary" onclick="prevStep()">Back</button>
                                    <button type="submit" class="btn btn-primary">Save & Run Setup</button>
                                </div>
                            </form>
                        </div>

                        <div id="step-card-5" class="step-card d-none">
                            <div class="text-center py-5">
                                <i class="fa-solid fa-gear fa-spin text-primary mb-4" style="font-size: 50px;"></i>
                                <h3 id="exec-title">Installation is running</h3>
                                <p class="subtitle" id="exec-desc">Please wait while database tables and keys are
                                    initialized.</p>
                                <div class="text-left mx-auto" style="max-width: 450px;">
                                    <div id="prog-step-1" class="text-muted font-weight-bold mb-2">
                                        <i class="fa-regular fa-circle mr-2"></i> Saving configurations...
                                    </div>
                                    <div id="prog-step-2" class="text-muted font-weight-bold mb-2">
                                        <i class="fa-regular fa-circle mr-2"></i> Migrating database tables...
                                    </div>
                                    <div id="prog-step-3" class="text-muted font-weight-bold mb-2">
                                        <i class="fa-regular fa-circle mr-2"></i> Writing application keys...
                                    </div>
                                    <div id="prog-step-4" class="text-muted font-weight-bold">
                                        <i class="fa-regular fa-circle mr-2"></i> Finalizing application locks...
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="step-card-6" class="step-card d-none">
                            <div class="text-center py-5">
                                <i class="fa-solid fa-circle-check text-success mb-4" style="font-size: 60px;"></i>
                                <h3>Setup Finished!</h3>
                                <p class="subtitle">Guard Helper has been installed successfully.</p>
                                <div class="alert alert-info py-2" style="font-size: 13px;">
                                    <strong>Default Admin:</strong> admin@example.com / admin12345
                                </div>
                            </div>
                            <div class="d-flex justify-content-center border-t pt-3 mt-3">
                                <a href="/login" class="btn btn-primary btn-lg">Go to Login</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentStep = 1;
        let adminPayload = {};
        let appKey = '';

        document.addEventListener('DOMContentLoaded', function () {
            checkRequirements();
        });

        function selectKeyOption(option) {
            document.querySelectorAll('.option-box').forEach(el => el.classList.remove('active'));
            document.getElementById('key-opt-generate').checked = false;
            document.getElementById('key-opt-provide').checked = false;

            if (option === 'generate') {
                document.getElementById('opt-generate').classList.add('active');
                document.getElementById('key-opt-generate').checked = true;
                document.getElementById('key-input-container').classList.add('d-none');
            } else {
                document.getElementById('opt-provide').classList.add('active');
                document.getElementById('key-opt-provide').checked = true;
                document.getElementById('key-input-container').classList.remove('d-none');
            }
        }

        function validateKeyStep() {
            const isProvide = document.getElementById('key-opt-provide').checked;
            if (isProvide) {
                const keyInput = document.getElementById('custom-app-key').value.trim();
                if (!keyInput.startsWith('base64:') || keyInput.length < 10) {
                    document.getElementById('key-validation-error').classList.remove('d-none');
                    return;
                }
                appKey = keyInput;
            } else {
                appKey = '';
            }
            document.getElementById('key-validation-error').classList.add('d-none');
            nextStep();
        }

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
                    const phpVal = document.getElementById('req-php-val');
                    phpVal.innerText = res.data.php.current;
                    phpVal.className = 'badge ' + (res.data.php.ok ? 'badge-success' : 'badge-danger');

                    const extContainer = document.getElementById('req-extensions');
                    extContainer.innerHTML = '';
                    for (const [ext, ok] of Object.entries(res.data.extensions)) {
                        extContainer.innerHTML += `
                        <div class="col-md-6 mb-2">
                            <div class="p-2 border rounded d-flex justify-content-between align-items-center bg-light" style="font-size: 11px;">
                                <span>${ext}</span>
                                <span class="badge ${ok ? 'badge-success' : 'badge-danger'}">
                                    ${ok ? 'OK' : 'Missing'}
                                </span>
                            </div>
                        </div>
                    `;
                    }

                    const permContainer = document.getElementById('req-permissions');
                    permContainer.innerHTML = '';
                    const labels = {
                        storage: 'storage/ directory',
                        bootstrap_cache: 'bootstrap/cache/ directory',
                        env: '.env file / root directory'
                    };
                    for (const [dir, ok] of Object.entries(res.data.permissions)) {
                        permContainer.innerHTML += `
                        <div class="p-2 border rounded d-flex justify-content-between align-items-center bg-light mb-2" style="font-size: 11px;">
                            <span>${labels[dir] || dir}</span>
                            <span class="badge ${ok ? 'badge-success' : 'badge-danger'}">
                                ${ok ? 'Writable' : 'Protected'}
                            </span>
                        </div>
                    `;
                    }

                    if (res.success) {
                        document.getElementById('btn-next-2').removeAttribute('disabled');
                    }
                })
                .catch(err => {
                    alert('Connection to installation server failed.');
                });
        }

        function toggleDatabaseFields() {
            const select = document.querySelector('select[name="db_connection"]');
            const rowHost = document.getElementById('db-host-port-row');
            const rowCreds = document.getElementById('db-credentials-row');
            const nameLabel = document.getElementById('db-name-label');
            const dbInput = document.querySelector('input[name="db_database"]');

            if (select.value === 'sqlite') {
                rowHost.classList.add('d-none');
                rowCreds.classList.add('d-none');
                nameLabel.innerText = 'SQLite Database Path';
                dbInput.value = 'database.sqlite';
            } else {
                rowHost.classList.remove('d-none');
                rowCreds.classList.remove('d-none');
                nameLabel.innerText = 'Database Name';
                dbInput.value = 'guard_helper';
            }
        }

        function submitDatabase(e) {
            e.preventDefault();

            const spinner = document.getElementById('db-spinner');
            const submitBtn = document.getElementById('btn-db-submit');
            const btnText = document.getElementById('db-btn-text');

            spinner.classList.remove('d-none');
            submitBtn.setAttribute('disabled', 'true');
            btnText.innerText = 'Verifying...';

            const form = document.getElementById('form-database');
            const formData = new FormData(form);
            const payload = Object.fromEntries(formData.entries());
            payload.app_key = appKey;

            performDatabaseFetch(payload, spinner, submitBtn, btnText);
        }

        function performDatabaseFetch(payload, spinner, submitBtn, btnText) {
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
                    spinner.classList.add('d-none');
                    submitBtn.removeAttribute('disabled');
                    btnText.innerText = 'Verify Connection';
                    nextStep();
                })
                .catch(err => {
                    if (err.error_type === 'mac_mismatch') {
                        if (confirm(err.message)) {
                            payload.wipe_database = true;
                            performDatabaseFetch(payload, spinner, submitBtn, btnText);
                        } else {
                            spinner.classList.add('d-none');
                            submitBtn.removeAttribute('disabled');
                            btnText.innerText = 'Verify Connection';
                        }
                    } else {
                        spinner.classList.add('d-none');
                        submitBtn.removeAttribute('disabled');
                        btnText.innerText = 'Verify Connection';
                        alert(err.message || 'Database connection validation failed.');
                    }
                });
        }

        function submitAdmin(e) {
            e.preventDefault();

            const form = document.getElementById('form-admin');
            const formData = new FormData(form);
            adminPayload = Object.fromEntries(formData.entries());
            adminPayload.app_key = appKey;

            nextStep();
            runFinalInstallation();
        }

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
                    step1.innerHTML = '<i class="fa-solid fa-circle-check text-success mr-2"></i> <span>Configuration values saved successfully</span>';
                    step1.className = 'text-success font-weight-bold mb-2';

                    step2.className = 'text-primary font-weight-bold mb-2';
                    step2.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> <span>Running database migrations and seeds...</span>';

                    setTimeout(() => {
                        step2.innerHTML = '<i class="fa-solid fa-circle-check text-success mr-2"></i> <span>Database structure & tables initialized</span>';
                        step2.className = 'text-success font-weight-bold mb-2';

                        step3.className = 'text-primary font-weight-bold mb-2';
                        step3.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> <span>Generating secure application crypt keys...</span>';

                        setTimeout(() => {
                            step3.innerHTML = '<i class="fa-solid fa-circle-check text-success mr-2"></i> <span>Application cryptographic keys generated</span>';
                            step3.className = 'text-success font-weight-bold mb-2';

                            step4.className = 'text-primary font-weight-bold';
                            step4.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> <span>Writing lock settings & clearing cache...</span>';

                            setTimeout(() => {
                                step4.innerHTML = '<i class="fa-solid fa-circle-check text-success mr-2"></i> <span>Settings locked. Clear optimization caches</span>';
                                step4.className = 'text-success font-weight-bold';

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

                    step1.className = 'text-danger font-weight-bold mb-2';
                    step1.innerHTML = `<i class="fa-solid fa-circle-xmark text-danger mr-2"></i> <span>${err.message || 'Artisan command execution failed.'}</span>`;
                    alert(err.message || 'Installation execution failed.');
                });
        }

        function nextStep() {
            goToStep(currentStep + 1);
        }

        function prevStep() {
            goToStep(currentStep - 1);
        }

        function goToStep(step) {
            if (step < 1 || step > 6) return;

            document.getElementById(`step-card-${currentStep}`).classList.add('d-none');
            document.getElementById(`step-ind-${currentStep}`).className = 'step-item';

            document.getElementById(`step-card-${step}`).classList.remove('d-none');
            document.getElementById(`step-ind-${step}`).className = 'step-item active';

            for (let i = 1; i < step; i++) {
                document.getElementById(`step-ind-${i}`).className = 'step-item completed';
            }

            currentStep = step;
        }
    </script>
</body>

</html>