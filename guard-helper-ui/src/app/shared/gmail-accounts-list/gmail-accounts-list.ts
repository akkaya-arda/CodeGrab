import { Component, HostListener, inject, signal, computed } from '@angular/core';
import { GmailService } from '../../services/gmail-service';
import { GmailAccount } from '../../models/gmail-account';
import { ToastrModule, ToastrService } from 'ngx-toastr';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { SettingsService } from '../../services/settings-service';

@Component({
  selector: 'app-gmail-accounts-list',
  imports: [ToastrModule, FormsModule],
  templateUrl: './gmail-accounts-list.html',
  styleUrl: './gmail-accounts-list.css',
})
export class GmailAccountsList {
  private gmailService = inject(GmailService);
  private settingsService = inject(SettingsService);
  private router = inject(Router);
  protected accounts = signal<GmailAccount[]>([]);
  protected searchQuery = signal<string>('');
  protected filteredAccounts = computed(() => {
    const query = this.searchQuery().toLowerCase().trim();
    const list = this.accounts();
    if (!query) return list;
    return list.filter(account => account.email.toLowerCase().includes(query));
  });
  private toastr: ToastrService = inject(ToastrService);

  // Google OAuth Config state variables
  protected showOAuthConfig = signal<boolean>(false);
  protected googleIsConfigured = signal<boolean>(false);
  protected googleClientId = signal<string>('');
  protected googleClientSecret = signal<string>('');
  protected googleClientSecretExists = signal<boolean>(false);
  protected googleRedirectUri = signal<string>('');
  protected suggestedGoogleRedirectUri = signal<string>('');
  protected isSavingOAuthConfig = signal<boolean>(false);

  protected onCreateToken(email: string) {
    this.router.navigate(['/access-grants'], { queryParams: { email } });
  }

  @HostListener('window:message', ['$event'])
  onMessage(event: MessageEvent) {
    if (event.data.success) {
      this.toastr.success(event.data.message);
      this.gmailService.getAccounts().subscribe({
        next: response => {
          this.accounts.set(response.data ?? []);
          console.log("[GmailAccountsList]", "Accounts loaded: ", this.accounts);
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
    this.gmailService.getAccounts().subscribe({
      next: response => {
        this.accounts.set(response.data ?? []);
        console.log("[GmailAccountsList]", "Accounts loaded: ", this.accounts);
      },
      error: error => {
        this.toastr.error(error.message);
      }
    });
  }

  protected isAccessTokenExpired(account: GmailAccount): boolean {
    return account.accessTokenExpiresAt < new Date();
  }

  protected isRefreshTokenExpired(account: GmailAccount): boolean {
    return account.refreshTokenExpiresAt < new Date();
  }

  protected onClickDelete(account: GmailAccount) {
    if (confirm(`Are you sure you want to delete the Gmail account for ${account.email}?`)) {
      this.gmailService.deleteAccount(account.email).subscribe({
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

  protected onClickEnable(account: GmailAccount) {
    this.gmailService.enableAccount(account.email).subscribe({
      next: response => {
        this.accounts.update(accounts => accounts.map(a => a.id === account.id ? { ...a, isActive: true } : a));
        this.toastr.success(response.message);
      },
      error: error => {
        this.toastr.error(error.message);
      }
    });
  }

  protected onClickDisable(account: GmailAccount) {
    this.gmailService.disableAccount(account.email).subscribe({
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
    if (!this.googleIsConfigured()) {
      this.toastr.warning('Please configure Google OAuth credentials first.');
      this.showOAuthConfig.set(true);
      return;
    }
    this.gmailService.getAuthorizationUrl().subscribe({
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
          this.googleIsConfigured.set(config.google_is_configured);
          this.googleClientId.set(config.google_client_id);
          this.googleClientSecretExists.set(config.google_client_secret_exists);
          this.googleRedirectUri.set(config.google_redirect_uri);
          this.suggestedGoogleRedirectUri.set(config.google_redirect_uri);
          if (!config.google_is_configured) {
            this.showOAuthConfig.set(true);
          }
        }
      },
      error: error => {
        this.toastr.error('Failed to load Google OAuth configuration.');
      }
    });
  }

  protected onSaveOAuthConfig() {
    this.isSavingOAuthConfig.set(true);
    const payload = {
      google_client_id: this.googleClientId(),
      google_client_secret: this.googleClientSecret(),
      google_redirect_uri: this.googleRedirectUri()
    };
    
    this.settingsService.updateOAuthConfig(payload).subscribe({
      next: response => {
        this.toastr.success(response.message || 'Google OAuth settings saved successfully.');
        this.googleClientSecret.set(''); // Clear form input
        this.loadOAuthConfig();
        this.isSavingOAuthConfig.set(false);
      },
      error: error => {
        this.toastr.error(error.error?.message || error.message || 'Failed to save Google OAuth settings.');
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
