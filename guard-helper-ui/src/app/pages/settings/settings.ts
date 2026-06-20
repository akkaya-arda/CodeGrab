import { Component, inject, signal } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators, FormsModule } from '@angular/forms';
import { SettingsService } from '../../services/settings-service';
import { ToastrService } from 'ngx-toastr';
import { CommonModule } from '@angular/common';
import { LayoutServices } from '../../services/layout-services';
import { PlatformService } from '../../services/platform-service';
import { AccountBundleService } from '../../services/account-bundle-service';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { ApiConnectionSettings } from '../../settings/api-connection-settings';
import { StaticPageService } from '../../services/static-page-service';
import { ThemeService } from '../../services/theme-service';

@Component({
  selector: 'app-settings',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule],
  templateUrl: './settings.html',
  styleUrl: './settings.css',
})
export class Settings {
  private settingsService = inject(SettingsService);
  private platformService = inject(PlatformService);
  private bundleService = inject(AccountBundleService);
  private staticPageService = inject(StaticPageService);
  protected themeService = inject(ThemeService);
  private http = inject(HttpClient);
  private apiSettings = inject(ApiConnectionSettings);
  private toastr = inject(ToastrService);
  private fb = inject(FormBuilder);
  private layoutServices = inject(LayoutServices);

  protected isSaving = signal<boolean>(false);
  protected isTestingSmtp = signal<boolean>(false);
  protected activeSettingsTab = signal<string>('branding');
  protected isPageModalOpen = signal<boolean>(false);
  protected staticPages = signal<any[]>([]);
  protected editingPageId = signal<number | null>(null);

  protected pageFormTitle = signal<string>('');
  protected pageFormSlug = signal<string>('');
  protected pageFormContent = signal<string>('');
  protected pageFormPublished = signal<boolean>(true);
  protected pageFormShowInFooter = signal<boolean>(true);

  protected isUploadingLogo = signal<boolean>(false);

  // Webhook Playground signals
  protected activeTab = signal<'curl' | 'n8n' | 'code' | 'response'>('curl');
  protected activeCodeLanguage = signal<'php' | 'csharp' | 'python' | 'js'>('php');
  protected playgroundPhpCode = signal<string>('');
  protected playgroundCsharpCode = signal<string>('');
  protected playgroundPythonCode = signal<string>('');
  protected playgroundJsCode = signal<string>('');
  protected playgroundEmail = signal<string>('tester@example.com');
  protected playgroundPlatform = signal<string>('Steam');
  protected playgroundLimit = signal<number>(5);
  protected playgroundAuthType = signal<'secret' | 'signature'>('secret');
  protected playgroundEndpointMode = signal<'single' | 'bulk'>('single');
  protected playgroundBundleId = signal<number>(1);
  protected playgroundQuantity = signal<number>(50);
  protected playgroundTag = signal<string>('');
  protected playgroundHmac = signal<string>('');
  protected playgroundCurl = signal<string>('');
  protected playgroundN8n = signal<string>('');
  protected playgroundResponseSchema = signal<string>('');
  protected playgroundResponse = signal<any>(null);
  protected playgroundResponseStatus = signal<number | null>(null);
  protected playgroundIsTesting = signal<boolean>(false);
  protected useDockerHost = signal<boolean>(false);
  protected platforms = signal<any[]>([]);
  protected accountBundles = signal<any[]>([]);

  protected settingsForm: FormGroup = this.fb.group({
    telegram_enabled: [false],
    telegram_bot_token: [''],
    telegram_chat_id: [''],
    telegram_webhook_active: [false],
    smtp_enabled: [false],
    smtp_host: [''],
    smtp_port: [587, [Validators.min(1), Validators.max(65535)]],
    smtp_encryption: ['tls'],
    smtp_username: [''],
    smtp_password: [''],
    smtp_from_address: ['', [Validators.email]],
    smtp_from_name: [''],
    smtp_to_address: ['', [Validators.email]],
    public_access_portal_enabled: [false],
    webhook_secret_key: [''],
    frontend_url: ['', [Validators.required]],
    support_portal_enabled: [false],
    support_mode: ['built_in'],
    support_custom_script: [''],
    system_name: ['Raven', [Validators.required]],
    system_logo: [''],
    logo_enabled: [true],
    theme_primary_color: ['#4f46e5'],
    theme_accent_color: ['#6366f1'],
    theme_font_family: ['Pacifico', [Validators.required]],
    system_slogan_title: ['Access Portal', [Validators.required]],
    system_slogan_subtitle: ['Retrieve your 2FA codes easily.', [Validators.required]],
    copyright_text: [''],
    hide_access_restricted_info: [false],
    email_timeframe_limit: [1200, [Validators.required, Validators.min(1)]],
  });

  constructor() {
    this.layoutServices.setPageTitle('System Configuration');
    this.loadSettings();
    this.loadPlatforms();
    this.loadBundles();
    this.loadPages();
    this.settingsForm.valueChanges.subscribe(() => {
      this.updatePlayground();
    });
  }

  private loadSettings() {
    this.settingsService.getSettings().subscribe({
      next: response => {
        if (response.success && response.data) {
          const data = response.data;
          this.settingsForm.patchValue({
            telegram_enabled: data.telegram_enabled === '1',
            telegram_bot_token: data.telegram_bot_token,
            telegram_chat_id: data.telegram_chat_id,
            telegram_webhook_active: data.telegram_webhook_active === '1',
            smtp_enabled: data.smtp_enabled === '1',
            smtp_host: data.smtp_host,
            smtp_port: data.smtp_port ? parseInt(data.smtp_port) : 587,
            smtp_encryption: data.smtp_encryption || 'tls',
            smtp_username: data.smtp_username,
            smtp_password: data.smtp_password,
            smtp_from_address: data.smtp_from_address,
            smtp_from_name: data.smtp_from_name,
            smtp_to_address: data.smtp_to_address,
            public_access_portal_enabled: data.public_access_portal_enabled === '1',
            webhook_secret_key: data.webhook_secret_key,
            frontend_url: data.frontend_url || 'http://localhost:4200',
            support_portal_enabled: data.support_portal_enabled === '1',
            support_mode: data.support_mode || 'built_in',
            support_custom_script: data.support_custom_script || '',
            system_name: data.system_name || 'Raven',
            system_logo: data.system_logo || '',
            logo_enabled: data.logo_enabled === '1',
            theme_primary_color: data.theme_primary_color || '#4f46e5',
            theme_accent_color: data.theme_accent_color || '#6366f1',
            theme_font_family: data.theme_font_family || 'Pacifico',
            system_slogan_title: data.system_slogan_title || 'Access Portal',
            system_slogan_subtitle: data.system_slogan_subtitle || 'Retrieve your 2FA codes easily.',
            copyright_text: data.copyright_text || '',
            hide_access_restricted_info: data.hide_access_restricted_info === '1',
            email_timeframe_limit: data.email_timeframe_limit ? parseInt(data.email_timeframe_limit) : 1200,
          });
          this.updatePlayground();
        }
      },
      error: () => {
        this.toastr.error('Failed to load system settings.');
      }
    });
  }

  protected onSubmit() {
    if (this.settingsForm.invalid) {
      this.toastr.error('Please fix validation errors before saving.');
      return;
    }

    this.isSaving.set(true);
    const formValues = { ...this.settingsForm.value };

    // Normalize boolean checkboxes to strings '1' or '0' for the backend
    formValues.telegram_enabled = formValues.telegram_enabled ? '1' : '0';
    formValues.telegram_webhook_active = formValues.telegram_webhook_active ? '1' : '0';
    formValues.smtp_enabled = formValues.smtp_enabled ? '1' : '0';
    formValues.public_access_portal_enabled = formValues.public_access_portal_enabled ? '1' : '0';
    formValues.support_portal_enabled = formValues.support_portal_enabled ? '1' : '0';
    formValues.logo_enabled = formValues.logo_enabled ? '1' : '0';
    formValues.hide_access_restricted_info = formValues.hide_access_restricted_info ? '1' : '0';

    this.settingsService.updateSettings(formValues).subscribe({
      next: response => {
        this.isSaving.set(false);
        this.toastr.success(response.message || 'System settings saved successfully.');
        this.loadSettings();

        localStorage.removeItem('public_platforms_cache');
        localStorage.removeItem('public_platforms_cache_time');
        this.themeService.applyBranding(
          formValues.system_name,
          formValues.system_logo,
          formValues.theme_primary_color,
          formValues.theme_accent_color,
          formValues.system_slogan_title,
          formValues.system_slogan_subtitle,
          formValues.logo_enabled === '1',
          formValues.theme_font_family,
          formValues.copyright_text,
          formValues.hide_access_restricted_info === '1'
        );
      },
      error: err => {
        this.isSaving.set(false);
        this.toastr.error(err.error?.message || err.message || 'Failed to save system settings.');
      }
    });
  }

  protected onClickTestSmtp() {
    const host = this.settingsForm.get('smtp_host')?.value;
    const port = this.settingsForm.get('smtp_port')?.value;
    const fromAddress = this.settingsForm.get('smtp_from_address')?.value;
    const toAddress = this.settingsForm.get('smtp_to_address')?.value;

    if (!host || !port || !fromAddress || !toAddress) {
      this.toastr.warning('Please fill in SMTP Host, Port, From Address, and Recipient Email before testing.');
      return;
    }

    if (this.settingsForm.get('smtp_from_address')?.invalid ||
      this.settingsForm.get('smtp_to_address')?.invalid ||
      this.settingsForm.get('smtp_port')?.invalid) {
      this.toastr.warning('Please check that SMTP Port, From Address, and Recipient Email are in valid format.');
      return;
    }

    this.isTestingSmtp.set(true);
    const formValues = { ...this.settingsForm.value };

    this.settingsService.testSmtp(formValues).subscribe({
      next: response => {
        this.isTestingSmtp.set(false);
        this.toastr.success(response.message || 'Test email sent successfully!');
      },
      error: err => {
        this.isTestingSmtp.set(false);
        this.toastr.error(err.error?.message || err.message || 'SMTP Connection failed.');
      }
    });
  }

  protected regenerateWebhookSecret() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let secret = '';
    for (let i = 0; i < 32; i++) {
      secret += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    this.settingsForm.get('webhook_secret_key')?.setValue(secret);
    this.toastr.info('Secret key updated in form. Save Configuration to apply.');
  }

  protected loadBundles() {
    this.bundleService.getBundles().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.accountBundles.set(response.data);
          if (response.data.length > 0) {
            this.playgroundBundleId.set(response.data[0].id || 1);
          }
        }
      }
    });
  }

  protected loadPlatforms() {
    this.platformService.getPlatforms().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.platforms.set(response.data);
          if (response.data.length > 0) {
            this.playgroundPlatform.set(response.data[0].name);
          }
        }
      }
    });
  }

  protected onPlaygroundEmailInput(event: any) {
    this.playgroundEmail.set(event.target.value);
    this.updatePlayground();
  }

  protected onPlaygroundPlatformInput(event: any) {
    this.playgroundPlatform.set(event.target.value);
    this.updatePlayground();
  }

  protected onPlaygroundLimitInput(event: any) {
    this.playgroundLimit.set(parseInt(event.target.value) || 1);
    this.updatePlayground();
  }

  protected onPlaygroundEndpointModeInput(event: any) {
    this.playgroundEndpointMode.set(event.target.value as 'single' | 'bulk');
    this.updatePlayground();
  }

  protected onPlaygroundBundleInput(event: any) {
    this.playgroundBundleId.set(parseInt(event.target.value) || 1);
    this.updatePlayground();
  }

  protected onPlaygroundQuantityInput(event: any) {
    this.playgroundQuantity.set(parseInt(event.target.value) || 1);
    this.updatePlayground();
  }

  protected onPlaygroundTagInput(event: any) {
    this.playgroundTag.set(event.target.value || '');
    this.updatePlayground();
  }

  protected setPlaygroundAuthType(type: 'secret' | 'signature') {
    this.playgroundAuthType.set(type);
    this.updatePlayground();
  }

  protected async updatePlayground() {
    const mode = this.playgroundEndpointMode();
    const limit = this.playgroundLimit();
    const authType = this.playgroundAuthType();
    const secret = this.settingsForm.get('webhook_secret_key')?.value || 'YOUR_SECRET_KEY';

    let payload: any = {};
    let apiUrl = '';

    if (mode === 'single') {
      payload = {
        email: this.playgroundEmail(),
        platform: this.playgroundPlatform(),
        limit: limit
      };
      if (this.playgroundTag()) {
        payload.tag = this.playgroundTag();
      }
      apiUrl = `${this.apiSettings.baseUrl}/webhook/generate-access`;
    } else {
      payload = {
        account_bundle_id: this.playgroundBundleId(),
        quantity: this.playgroundQuantity(),
        limit: limit
      };
      if (this.playgroundTag()) {
        payload.tag = this.playgroundTag();
      }
      apiUrl = `${this.apiSettings.baseUrl}/webhook/generate-access-bulk`;
    }

    const payloadStr = JSON.stringify(payload);

    let hmacVal = '';
    if (authType === 'signature') {
      try {
        hmacVal = await this.signHmacSha256(secret, payloadStr);
      } catch (e) {
        hmacVal = 'failed_to_calculate_hmac';
      }
    }
    this.playgroundHmac.set(hmacVal);

    // Adjust endpoint if local Docker host is enabled
    let targetUrl = apiUrl;
    if (this.useDockerHost()) {
      targetUrl = targetUrl.replace('localhost', 'host.docker.internal').replace('127.0.0.1', 'host.docker.internal');
    }

    const headerStr = authType === 'secret'
      ? `-H "X-Webhook-Secret: ${secret}"`
      : `-H "X-Webhook-Signature: ${hmacVal}"`;

    const curl = `curl -X POST ${targetUrl} \\\n  -H "Content-Type: application/json" \\\n  ${headerStr} \\\n  -d '${payloadStr}'`;

    this.playgroundCurl.set(curl);

    // Generate N8N Node JSON
    const headersList = [
      {
        name: 'Content-Type',
        value: 'application/json'
      }
    ];

    if (authType === 'secret') {
      headersList.push({
        name: 'X-Webhook-Secret',
        value: secret
      });
    } else {
      headersList.push({
        name: 'X-Webhook-Signature',
        value: hmacVal
      });
    }

    let n8nBody = '';
    if (mode === 'single') {
      n8nBody = "={\n  \"email\": \"{{ $json.email }}\",\n  \"platform\": \"{{ $json.platform }}\",\n  \"limit\": \"{{ $json.limit || 5 }}\"" + (this.playgroundTag() ? ",\n  \"tag\": \"" + this.playgroundTag() + "\"" : "") + "\n}";
    } else {
      n8nBody = "={\n  \"account_bundle_id\": \"{{ $json.account_bundle_id || 1 }}\",\n  \"quantity\": \"{{ $json.quantity || 50 }}\",\n  \"limit\": \"{{ $json.limit || 5 }}\"" + (this.playgroundTag() ? ",\n  \"tag\": \"" + this.playgroundTag() + "\"" : "") + "\n}";
    }

    const n8nNode = {
      nodes: [
        {
          parameters: {
            method: 'POST',
            url: targetUrl,
            sendHeaders: true,
            headerParameters: {
              parameters: headersList
            },
            sendBody: true,
            specifyBody: 'json',
            jsonBody: n8nBody,
            options: {}
          },
          id: 'guard-helper-webhook-node',
          name: mode === 'single' ? 'Generate Guard Helper Access' : 'Generate Bulk Guard Helper Access',
          type: 'n8n-nodes-base.httpRequest',
          typeVersion: 4.1,
          position: [250, 250]
        }
      ],
      connections: {}
    };

    this.playgroundN8n.set(JSON.stringify(n8nNode, null, 2));

    // PHP snippet
    let phpAuth = '';
    if (authType === 'secret') {
      phpAuth = `$headers[] = 'X-Webhook-Secret: ' . $secret;`;
    } else {
      phpAuth = `$signature = hash_hmac('sha256', $payload_json, $secret);\n$headers[] = 'X-Webhook-Signature: ' . $signature;`;
    }

    let phpPayloadFields = '';
    if (mode === 'single') {
      phpPayloadFields = `    'email' => '${payload.email}',
    'platform' => '${payload.platform}',
    'limit' => ${limit}` + (payload.tag ? `,\n    'tag' => '${payload.tag}'` : '');
    } else {
      phpPayloadFields = `    'account_bundle_id' => ${payload.account_bundle_id},
    'quantity' => ${payload.quantity},
    'limit' => ${limit}` + (payload.tag ? `,\n    'tag' => '${payload.tag}'` : '');
    }

    const phpCode = `<?php
$url = '${targetUrl}';
$secret = '${secret}';

$payload = [
${phpPayloadFields}
];

$payload_json = json_encode($payload);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_json);

$headers = [
    'Content-Type: application/json'
];

${phpAuth}

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: " . $status_code . "\\n";
echo "Response: " . $response . "\\n";
?>`;
    this.playgroundPhpCode.set(phpCode);

    // C# snippet
    let csharpAuth = '';
    if (authType === 'secret') {
      csharpAuth = `request.Headers.Add("X-Webhook-Secret", secret);`;
    } else {
      csharpAuth = `using var hmac = new HMACSHA256(Encoding.UTF8.GetBytes(secret));
        var hash = hmac.ComputeHash(Encoding.UTF8.GetBytes(payloadJson));
        var signature = BitConverter.ToString(hash).Replace("-", "").ToLower();
        request.Headers.Add("X-Webhook-Signature", signature);`;
    }

    let csharpPayloadFields = '';
    if (mode === 'single') {
      csharpPayloadFields = `            email = "${payload.email}",
            platform = "${payload.platform}",
            limit = ${limit}` + (payload.tag ? `,\n            tag = "${payload.tag}"` : '');
    } else {
      csharpPayloadFields = `            account_bundle_id = ${payload.account_bundle_id},
            quantity = ${payload.quantity},
            limit = ${limit}` + (payload.tag ? `,\n            tag = "${payload.tag}"` : '');
    }

    const csharpCode = `using System;
using System.Net.Http;
using System.Text;
using System.Text.Json;
using System.Security.Cryptography;
using System.Threading.Tasks;

class Program
{
    static async Task Main()
    {
        var url = "${targetUrl}";
        var secret = "${secret}";
        
        var payload = new
        {
${csharpPayloadFields}
        };
        
        var payloadJson = JsonSerializer.Serialize(payload);
        
        using var client = new HttpClient();
        var request = new HttpRequestMessage(HttpMethod.Post, url);
        request.Content = new StringContent(payloadJson, Encoding.UTF8, "application/json");
        
        ${csharpAuth}

        var response = await client.SendAsync(request);
        var responseString = await response.Content.ReadAsStringAsync();
        
        Console.WriteLine($"Status: {response.StatusCode}");
        Console.WriteLine($"Response: {responseString}");
    }
}`;
    this.playgroundCsharpCode.set(csharpCode);

    // Python snippet
    let pythonAuth = '';
    if (authType === 'secret') {
      pythonAuth = `headers["X-Webhook-Secret"] = secret`;
    } else {
      pythonAuth = `signature = hmac.new(secret.encode(), payload_json.encode(), hashlib.sha256).hexdigest()
headers["X-Webhook-Signature"] = signature`;
    }

    let pythonPayloadFields = '';
    if (mode === 'single') {
      pythonPayloadFields = `    "email": "${payload.email}",
    "platform": "${payload.platform}",
    "limit": ${limit}` + (payload.tag ? `,\n    "tag": "${payload.tag}"` : '');
    } else {
      pythonPayloadFields = `    "account_bundle_id": ${payload.account_bundle_id},
    "quantity": ${payload.quantity},
    "limit": ${limit}` + (payload.tag ? `,\n    "tag": "${payload.tag}"` : '');
    }

    const pythonCode = `import requests
import json
import hmac
import hashlib

url = "${targetUrl}"
secret = "${secret}"

payload = {
${pythonPayloadFields}
}

payload_json = json.dumps(payload, separators=(',', ':'))

headers = {
    "Content-Type": "application/json"
}

${pythonAuth}

response = requests.post(url, data=payload_json, headers=headers)

print(f"Status Code: {response.status_code}")
print(f"Response: {response.text}")`;
    this.playgroundPythonCode.set(pythonCode);

    // JS snippet
    let jsAuth = '';
    if (authType === 'secret') {
      jsAuth = `headers['X-Webhook-Secret'] = secret;`;
    } else {
      jsAuth = `const signature = crypto
  .createHmac('sha256', secret)
  .update(payloadStr)
  .digest('hex');
headers['X-Webhook-Signature'] = signature;`;
    }

    let jsPayloadFields = '';
    if (mode === 'single') {
      jsPayloadFields = `  email: '${payload.email}',
  platform: '${payload.platform}',
  limit: ${limit}` + (payload.tag ? `,\n  tag: '${payload.tag}'` : '');
    } else {
      jsPayloadFields = `  account_bundle_id: ${payload.account_bundle_id},
  quantity: ${payload.quantity},
  limit: ${limit}` + (payload.tag ? `,\n  tag: '${payload.tag}'` : '');
    }

    const jsCode = `const crypto = require('crypto');

const url = '${targetUrl}';
const secret = '${secret}';

const payload = {
${jsPayloadFields}
};

const payloadStr = JSON.stringify(payload);

const headers = {
  'Content-Type': 'application/json'
};

${jsAuth}

fetch(url, {
  method: 'POST',
  headers: headers,
  body: payloadStr
})
.then(res => res.json().then(data => ({ status: res.status, data })))
.then(result => {
  console.log('Status:', result.status);
  console.log('Response:', result.data);
})
.catch(err => console.error('Error:', err));`;
    this.playgroundJsCode.set(jsCode);

    // Generate Response Schema
    let responseSchema = {};
    if (mode === 'single') {
      responseSchema = {
        success: true,
        message: 'Access token generated successfully.',
        token: 'tok_abcdef123456789...',
        access_url: `${this.settingsForm.get('frontend_url')?.value || 'http://localhost:4200'}/grab-code?token=tok_abcdef123456789...`,
        data: {
          id: 12,
          email: payload.email,
          platform: payload.platform,
          tag: payload.tag || null,
          limit: limit,
          uses: 0,
          is_active: true,
          created_at: new Date().toISOString().replace(/\.\d+Z$/, '.000000Z')
        }
      };
    } else {
      responseSchema = {
        success: true,
        message: 'Successfully generated 50 tokens.',
        tokens: [
          {
            token: 'tok_abcdef123456789...',
            access_url: `${this.settingsForm.get('frontend_url')?.value || 'http://localhost:4200'}/grab-code?token=tok_abcdef123456789...`
          },
          {
            token: 'tok_xyz789012345678...',
            access_url: `${this.settingsForm.get('frontend_url')?.value || 'http://localhost:4200'}/grab-code?token=tok_xyz789012345678...`
          }
        ]
      };
    }
    this.playgroundResponseSchema.set(JSON.stringify(responseSchema, null, 2));
  }

  private async signHmacSha256(key: string, data: string): Promise<string> {
    const encoder = new TextEncoder();
    const cryptoKey = await window.crypto.subtle.importKey(
      'raw',
      encoder.encode(key),
      { name: 'HMAC', hash: 'SHA-256' },
      false,
      ['sign']
    );
    const signature = await window.crypto.subtle.sign(
      'HMAC',
      cryptoKey,
      encoder.encode(data)
    );
    const hashArray = Array.from(new Uint8Array(signature));
    return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
  }

  protected async sendPlaygroundRequest() {
    this.playgroundIsTesting.set(true);
    this.playgroundResponse.set(null);
    this.playgroundResponseStatus.set(null);

    const mode = this.playgroundEndpointMode();
    const limit = this.playgroundLimit();
    const authType = this.playgroundAuthType();
    const secret = this.settingsForm.get('webhook_secret_key')?.value;

    let payload: any = {};
    let apiUrl = '';

    if (mode === 'single') {
      payload = {
        email: this.playgroundEmail(),
        platform: this.playgroundPlatform(),
        limit: limit
      };
      if (this.playgroundTag()) {
        payload.tag = this.playgroundTag();
      }
      apiUrl = `${this.apiSettings.baseUrl}/webhook/generate-access`;
    } else {
      payload = {
        account_bundle_id: this.playgroundBundleId(),
        quantity: this.playgroundQuantity(),
        limit: limit
      };
      if (this.playgroundTag()) {
        payload.tag = this.playgroundTag();
      }
      apiUrl = `${this.apiSettings.baseUrl}/webhook/generate-access-bulk`;
    }

    const payloadStr = JSON.stringify(payload);

    let headers = new HttpHeaders({
      'Content-Type': 'application/json'
    });

    if (authType === 'secret') {
      headers = headers.set('X-Webhook-Secret', secret);
    } else {
      const hmac = await this.signHmacSha256(secret, payloadStr);
      headers = headers.set('X-Webhook-Signature', hmac);
    }

    this.http.post<any>(apiUrl, payload, { headers, observe: 'response' }).subscribe({
      next: response => {
        this.playgroundIsTesting.set(false);
        this.playgroundResponseStatus.set(response.status);
        this.playgroundResponse.set(response.body);
        if (response.body?.success) {
          this.toastr.success('Webhook dispatch successful!');
        } else {
          this.toastr.warning(response.body?.message || 'Webhook rejected.');
        }
      },
      error: err => {
        this.playgroundIsTesting.set(false);
        this.playgroundResponseStatus.set(err.status || 500);
        this.playgroundResponse.set(err.error || { message: 'Network or configuration error.' });
        this.toastr.error(err.error?.message || 'Webhook request failed.', 'Dispatch Error');
      }
    });
  }

  protected copyCurlToClipboard() {
    navigator.clipboard.writeText(this.playgroundCurl()).then(() => {
      this.toastr.success('cURL command copied to clipboard!');
    });
  }

  protected copyN8nToClipboard() {
    navigator.clipboard.writeText(this.playgroundN8n()).then(() => {
      this.toastr.success('N8N Node JSON copied to clipboard!');
    });
  }

  protected toggleDockerHost(checked: boolean) {
    this.useDockerHost.set(checked);
    this.updatePlayground();
  }

  protected downloadN8nJson() {
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(this.playgroundN8n());
    const downloadAnchor = document.createElement('a');
    downloadAnchor.setAttribute("href", dataStr);
    downloadAnchor.setAttribute("download", "guard-helper-n8n-node.json");
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
    this.toastr.success('N8N Workflow JSON downloaded successfully!');
  }

  protected copyCodeToClipboard() {
    let code = '';
    const lang = this.activeCodeLanguage();
    if (lang === 'php') code = this.playgroundPhpCode();
    else if (lang === 'csharp') code = this.playgroundCsharpCode();
    else if (lang === 'python') code = this.playgroundPythonCode();
    else if (lang === 'js') code = this.playgroundJsCode();

    navigator.clipboard.writeText(code).then(() => {
      this.toastr.success(`${lang.toUpperCase()} snippet copied to clipboard!`);
    });
  }

  protected isTogglingWebhook = signal<boolean>(false);

  protected onToggleTelegramWebhook(event: any) {
    const checked = event.target.checked;

    // Localhost check on frontend
    if (checked && this.isLocalhost()) {
      this.toastr.warning('Telegram Webhook cannot be activated on localhost since Telegram requires a public HTTPS URL.', 'Localhost Blocked');
      setTimeout(() => {
        this.settingsForm.patchValue({ telegram_webhook_active: false });
      });
      return;
    }

    const botToken = this.settingsForm.get('telegram_bot_token')?.value;
    if (!botToken) {
      this.toastr.warning('Please enter and save your Telegram Bot Token before activating the webhook.');
      setTimeout(() => {
        this.settingsForm.patchValue({ telegram_webhook_active: false });
      });
      return;
    }

    this.isTogglingWebhook.set(true);

    this.http.post<any>(`${this.apiSettings.baseUrl}/admin/settings/telegram/webhook/toggle`, {
      activate: checked
    }).subscribe({
      next: response => {
        this.isTogglingWebhook.set(false);
        if (response.success) {
          this.toastr.success(response.message || 'Telegram webhook status updated successfully.');
          this.settingsForm.patchValue({ telegram_webhook_active: checked });
        } else {
          this.toastr.error(response.message || 'Failed to update Telegram Webhook.');
          setTimeout(() => {
            this.settingsForm.patchValue({ telegram_webhook_active: !checked });
          });
        }
      },
      error: err => {
        this.isTogglingWebhook.set(false);
        this.toastr.error(err.error?.message || err.message || 'An error occurred during webhook configuration.');
        setTimeout(() => {
          this.settingsForm.patchValue({ telegram_webhook_active: !checked });
        });
      }
    });
  }

  protected isLocalhost(): boolean {
    const host = window.location.hostname;
    return host === 'localhost' || host === '127.0.0.1' || host.includes('localhost') || host.includes('127.0.0.1');
  }

  protected onLogoSelected(event: any) {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('logo', file);

    this.isUploadingLogo.set(true);

    const headers = new HttpHeaders({
      'Authorization': 'Bearer ' + localStorage.getItem('app-token')
    });

    this.http.post<any>(`${this.apiSettings.baseUrl}/admin/settings/logo`, formData, { headers }).subscribe({
      next: response => {
        this.isUploadingLogo.set(false);
        if (response.success && response.logo_url) {
          this.settingsForm.get('system_logo')?.setValue(response.logo_url);
          this.toastr.success('Logo uploaded successfully.');
          this.themeService.systemLogo.set(response.logo_url);
        }
      },
      error: err => {
        this.isUploadingLogo.set(false);
        this.toastr.error(err.error?.message || 'Logo upload failed.');
      }
    });
  }

  protected loadPages() {
    this.staticPageService.getPages().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.staticPages.set(response.data);
        }
      }
    });
  }

  protected openAddPageModal() {
    this.editingPageId.set(null);
    this.pageFormTitle.set('');
    this.pageFormSlug.set('');
    this.pageFormContent.set('');
    this.pageFormPublished.set(true);
    this.pageFormShowInFooter.set(true);
    this.isPageModalOpen.set(true);
  }

  protected openEditPageModal(page: any) {
    this.editingPageId.set(page.id);
    this.pageFormTitle.set(page.title);
    this.pageFormSlug.set(page.slug || '');
    this.pageFormContent.set(page.content || '');
    this.pageFormPublished.set(page.is_published);
    this.pageFormShowInFooter.set(page.show_in_footer);
    this.isPageModalOpen.set(true);
  }

  protected savePage() {
    if (!this.pageFormTitle().trim()) {
      this.toastr.warning('Please enter a page title.');
      return;
    }

    const payload = {
      title: this.pageFormTitle().trim(),
      slug: this.pageFormSlug().trim() || undefined,
      content: this.pageFormContent(),
      is_published: this.pageFormPublished(),
      show_in_footer: this.pageFormShowInFooter()
    };

    const id = this.editingPageId();
    if (id !== null) {
      this.staticPageService.updatePage(id, payload).subscribe({
        next: response => {
          if (response.success) {
            this.toastr.success('Page updated successfully.');
            this.isPageModalOpen.set(false);
            this.loadPages();
          }
        },
        error: err => {
          this.toastr.error(err.error?.message || 'Failed to update page.');
        }
      });
    } else {
      this.staticPageService.createPage(payload).subscribe({
        next: response => {
          if (response.success) {
            this.toastr.success('Page created successfully.');
            this.isPageModalOpen.set(false);
            this.loadPages();
          }
        },
        error: err => {
          this.toastr.error(err.error?.message || 'Failed to create page.');
        }
      });
    }
  }

  protected deletePage(id: number) {
    if (!confirm('Are you sure you want to delete this custom page?')) return;
    this.staticPageService.deletePage(id).subscribe({
      next: response => {
        if (response.success) {
          this.toastr.success('Page deleted successfully.');
          this.loadPages();
        }
      },
      error: () => {
        this.toastr.error('Failed to delete page.');
      }
    });
  }

  protected restoreDefaultTheme() {
    this.settingsForm.get('theme_primary_color')?.setValue('#4f46e5');
    this.settingsForm.get('theme_accent_color')?.setValue('#6366f1');
    this.toastr.info('Colors reset to default. Save settings to apply.');
  }
}
