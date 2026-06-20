import { Component, HostListener, inject, signal, computed } from '@angular/core';
import { ToastrModule, ToastrService } from 'ngx-toastr';
import { GmailService } from '../../services/gmail-service';
import { OutlookAccount } from '../../models/outlook-account';
import { OutlookService } from '../../services/outlook-service';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { SettingsService } from '../../services/settings-service';

@Component({
  selector: 'app-outlook-accounts-list',
  imports: [ToastrModule, FormsModule],
  templateUrl: './outlook-accounts-list.html',
  styleUrl: './outlook-accounts-list.css',
})
export class OutlookAccountsList {
  private toastr: ToastrService = inject(ToastrService);
  private outlookService: OutlookService = inject(OutlookService);
  private settingsService = inject(SettingsService);
  private router = inject(Router);
  protected accounts = signal<OutlookAccount[]>([]);
  protected searchQuery = signal<string>('');
  protected filteredAccounts = computed(() => {
    const query = this.searchQuery().toLowerCase().trim();
    const list = this.accounts();
    if (!query) return list;
    return list.filter(account => account.email.toLowerCase().includes(query));
  });

  // Outlook OAuth Config state variables
  protected showOAuthConfig = signal<boolean>(false);
  protected outlookIsConfigured = signal<boolean>(false);
  protected outlookTenant = signal<string>('consumers');
  protected outlookClientId = signal<string>('');
  protected outlookClientSecret = signal<string>('');
  protected outlookClientSecretExists = signal<boolean>(false);
  protected outlookRedirectUri = signal<string>('');
  protected suggestedOutlookRedirectUri = signal<string>('');
  protected isSavingOAuthConfig = signal<boolean>(false);

  protected onCreateToken(email: string) {
    this.router.navigate(['/access-grants'], { queryParams: { email } });
  }

  @HostListener('window:message', ['$event'])
  onMessage(event: MessageEvent) {
    if (event.data.success) {
      this.toastr.success(event.data.message);
      this.outlookService.getAccounts().subscribe({
        next: response => {
          this.accounts.set(response.data ?? []);
          console.log("[OutlookAccountsList]", "Accounts loaded: ", this.accounts);
        },
        error: error => {
          this.toastr.error(error.message);
        }
      });
    } else {

    }
  }

  constructor() {
    this.loadOAuthConfig();
    this.outlookService.getAccounts().subscribe({
      next: (response) => {
        this.accounts.set(response.data ?? []);
        console.log("[OutlookAccountsList]", "Accounts loaded: ", this.accounts);
      },
      error: (error) => {
        this.toastr.error(error.message)
      }
    });
  }

  protected isAccessTokenExpired(account: OutlookAccount): boolean {
    return account.accessTokenExpiresAt < new Date();
  }

  protected isRefreshTokenExpired(account: OutlookAccount): boolean {
    return account.refreshTokenExpiresAt < new Date();
  }

  protected onClickDelete(account: OutlookAccount) {
    if (confirm(`Are you sure you want to delete the Outlook account for ${account.email}?`)) {
      this.outlookService.deleteAccount(account.email).subscribe({
        next: response => {
          this.accounts.update(accounts => accounts.filter(a => a.id !== account.id));
          this.toastr.success(response.message);
        },
        error: error => {
          this.toastr.error(error.message);
        }
      });
    }
  }

  protected onClickEnable(account: OutlookAccount) {
    this.outlookService.enableAccount(account.email).subscribe({
      next: response => {
        this.accounts.update(accounts => accounts.map(a => a.id === account.id ? { ...a, isActive: true } : a));
        this.toastr.success(response.message);
      },
      error: error => {
        this.toastr.error(error.message);
      }
    });
  }

  protected onClickDisable(account: OutlookAccount) {
    this.outlookService.disableAccount(account.email).subscribe({
      next: response => {
        this.accounts.update(accounts => accounts.map(a => a.id === account.id ? { ...a, isActive: false } : a));
        this.toastr.success(response.message);
      },
      error: error => {
        this.toastr.error(error.message);
      }
    });
  }

  protected onClickReauthorize() {
    if (!this.outlookIsConfigured()) {
      this.toastr.warning('Please configure Outlook OAuth credentials first.');
      this.showOAuthConfig.set(true);
      return;
    }
    this.outlookService.getAuthorizationUrl().subscribe({
      next: response => {
        window.open(response.data, '_blank');
      },
      error: error => {
        this.toastr.error(error.message);
      }
    });
  }

  protected onClickLogs(email: string) {
    this.router.navigate(['/logs'], { queryParams: { email } });
  }

  protected loadOAuthConfig() {
    this.settingsService.getOAuthConfig().subscribe({
      next: response => {
        if (response.success && response.data) {
          const config = response.data;
          this.outlookIsConfigured.set(config.outlook_is_configured);
          this.outlookClientId.set(config.outlook_client_id);
          this.outlookClientSecretExists.set(config.outlook_client_secret_exists);
          this.outlookTenant.set(config.outlook_tenant);
          this.outlookRedirectUri.set(config.outlook_redirect_uri);
          this.suggestedOutlookRedirectUri.set(config.outlook_redirect_uri);
          if (!config.outlook_is_configured) {
            this.showOAuthConfig.set(true);
          }
        }
      },
      error: error => {
        this.toastr.error('Failed to load Outlook OAuth configuration.');
      }
    });
  }

  protected onSaveOAuthConfig() {
    this.isSavingOAuthConfig.set(true);
    const payload = {
      outlook_client_id: this.outlookClientId(),
      outlook_client_secret: this.outlookClientSecret(),
      outlook_redirect_uri: this.outlookRedirectUri(),
      outlook_tenant: this.outlookTenant()
    };
    
    this.settingsService.updateOAuthConfig(payload).subscribe({
      next: response => {
        this.toastr.success(response.message || 'Outlook OAuth settings saved successfully.');
        this.outlookClientSecret.set(''); // Clear form input
        this.loadOAuthConfig();
        this.isSavingOAuthConfig.set(false);
      },
      error: error => {
        this.toastr.error(error.error?.message || error.message || 'Failed to save Outlook OAuth settings.');
        this.isSavingOAuthConfig.set(false);
      }
    });
  }

  protected copyToClipboard(text: string) {
    navigator.clipboard.writeText(text).then(() => {
      this.toastr.success('Redirect URI copied to clipboard!');
    }).catch(() => {
      this.toastr.error('Failed to copy text.');
    });
  }
}
