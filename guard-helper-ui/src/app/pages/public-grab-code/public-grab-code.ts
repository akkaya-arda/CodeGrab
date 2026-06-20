import { Component, inject, signal, OnInit, computed, OnDestroy, Renderer2 } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ActivatedRoute } from '@angular/router';
import { ApiConnectionSettings } from '../../settings/api-connection-settings';
import { FeedbackService } from '../../services/feedback-service';
import { AccessGrantService } from '../../services/access-grant-service';
import { ToastrService } from 'ngx-toastr';
import { SupportService } from '../../services/support-service';
import { ThemeService } from '../../services/theme-service';
import { StaticPageService, StaticPage } from '../../services/static-page-service';
import { of, throwError, Observable } from 'rxjs';
import { tap, catchError } from 'rxjs/operators';

interface Platform {
  id: number;
  name: string;
  logo: string;
  sender: string;
  subject: string;
}

@Component({
  selector: 'app-public-grab-code',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './public-grab-code.html',
  styleUrl: './public-grab-code.css',
})
export class PublicGrabCode implements OnInit, OnDestroy {
  private http = inject(HttpClient);
  private settings = inject(ApiConnectionSettings);
  private feedbackService = inject(FeedbackService);
  private grantService = inject(AccessGrantService);
  private route = inject(ActivatedRoute);
  private toastr = inject(ToastrService);
  private supportService = inject(SupportService);
  protected themeService = inject(ThemeService);
  private staticPageService = inject(StaticPageService);
  private renderer = inject(Renderer2);

  protected year: number = new Date().getFullYear();

  protected platforms = signal<Platform[]>([]);
  protected selectedPlatform = signal<string>('');
  protected email = signal<string>('');
  protected isEmailSubmitted = signal<boolean>(false);
  protected isSearchingEmail = signal<boolean>(false);

  // Token Verification States
  protected token = signal<string | null>(null);
  protected isTokenChecking = signal<boolean>(false);
  protected isTokenValid = signal<boolean>(false);
  protected tokenError = signal<string | null>(null);
  protected remainingUses = signal<number | null>(null);
  protected totalLimit = signal<number | null>(null);
  protected expiresAt = signal<string | null>(null);
  protected isPublicPortalEnabled = signal<boolean>(false);
  protected isPublicPortalChecking = signal<boolean>(true);
  protected manualToken = signal<string>('');
  protected hideEmail = signal<boolean>(false);
  protected showEmail = signal<boolean>(false);

  // Dark Mode State
  protected isDarkMode = signal<boolean>(false);

  // Account Bundle Credentials
  protected hasBundle = signal<boolean>(false);
  protected bundleEmail = signal<string>('');
  protected bundlePassword = signal<string>('');
  protected showPassword = signal<boolean>(false);
  protected copyBundleEmailSuccess = signal<boolean>(false);
  protected copyBundlePasswordSuccess = signal<boolean>(false);

  protected isExpired = computed(() => {
    const expiry = this.expiresAt();
    if (!expiry) return false;
    return new Date(expiry).getTime() < Date.now();
  });

  protected isInterceptionDisabled = computed(() => {
    const uses = this.remainingUses();
    const expired = this.isExpired();
    return expired || (uses !== null && uses !== undefined && uses <= 0);
  });

  // UI States
  protected isLoading = signal<boolean>(false);
  protected loadingStep = signal<number>(1);
  protected loadingMessage = signal<string>('Initializing secure connection...');

  protected errorCode = signal<string | null>(null);
  protected result = signal<{ code: string; date?: string } | null>(null);
  protected copySuccess = signal<boolean>(false);

  // Support Portal Widget signals
  protected supportPortalEnabled = signal<boolean>(false);
  protected supportMode = signal<string>('built_in');
  protected supportCustomScript = signal<string>('');
  protected showSupportChat = signal<boolean>(false);
  protected supportThreadToken = signal<string>('');
  protected supportMessages = signal<any[]>([]);
  protected newSupportMessage = signal<string>('');
  protected isSendingSupportMessage = signal<boolean>(false);
  private supportPollInterval: any = null;

  // User Feedback States
  protected logId = signal<number | null>(null);
  protected showFeedbackWidget = signal<boolean>(false);
  protected feedbackSubmitted = signal<boolean>(false);
  protected feedbackWorking = signal<boolean | null>(null);
  protected feedbackComment = signal<string>('');
  protected isSubmittingFeedback = signal<boolean>(false);

  // Custom Static Pages States
  protected staticPages = signal<StaticPage[]>([]);
  protected pageModalTitle = signal<string>('');
  protected pageModalContent = signal<string>('');
  protected showPageModal = signal<boolean>(false);
  protected isLoadingPageContent = signal<boolean>(false);

  constructor() { }

  ngOnInit(): void {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      this.isDarkMode.set(true);
    }
    this.updateBodyBackground();

    this.loadStaticPages();
    this.route.queryParams.subscribe(params => {
      const tok = params['token'];
      if (tok) {
        this.token.set(tok);
        this.validateToken(tok);
        this.loadPlatforms();
      } else {
        this.checkPublicAccess();
      }
    });
  }

  private loadPlatforms() {
    this.getPublicPlatformsData().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.platforms.set(response.data);
        }
      }
    });
  }

  private getPublicPlatformsData(email?: string): Observable<any> {
    const url = this.settings.baseUrl + '/public/platforms';
    const params: any = {};
    if (email) {
      params.email = email;
    }

    if (!email) {
      const cached = localStorage.getItem('public_platforms_cache');
      const cachedTime = localStorage.getItem('public_platforms_cache_time');

      if (cached && cachedTime) {
        const age = Date.now() - parseInt(cachedTime, 10);
        const fifteenMinutes = 15 * 60 * 1000;
        if (age < fifteenMinutes) {
          try {
            const data = JSON.parse(cached);
            return of(data);
          } catch (e) {
            // fallback
          }
        }
      }
    }

    return this.http.get<any>(url, { params }).pipe(
      tap(response => {
        if (!email && response && response.success) {
          try {
            localStorage.setItem('public_platforms_cache', JSON.stringify(response));
            localStorage.setItem('public_platforms_cache_time', Date.now().toString());
          } catch (e) {
            // handle storage disable/quota
          }
        }
      }),
      catchError(err => {
        if (!email) {
          const cached = localStorage.getItem('public_platforms_cache');
          if (cached) {
            try {
              const data = JSON.parse(cached);
              return of(data);
            } catch (e) {
              // ignore
            }
          }
        }
        return throwError(() => err);
      })
    );
  }

  protected getSelectedPlatformLogo(): string {
    const matched = this.platforms().find(p => p.name === this.selectedPlatform());
    return matched ? matched.logo : '';
  }

  private validateToken(tok: string) {
    this.isTokenChecking.set(true);
    this.tokenError.set(null);

    this.grantService.verifyToken(tok).subscribe({
      next: response => {
        this.isTokenChecking.set(false);
        if (response.success && response.data) {
          this.isTokenValid.set(true);
          this.email.set(response.data.email);
          this.selectedPlatform.set(response.data.platform);
          this.remainingUses.set(response.data.remaining);
          this.totalLimit.set(response.data.limit);
          this.expiresAt.set(response.data.expires_at || null);
          this.hideEmail.set(response.data.hide_email === true);
          
          const bundle = response.data.account_bundle;
          if (bundle) {
            this.hasBundle.set(true);
            this.bundleEmail.set(bundle.login_username || bundle.email);
            this.bundlePassword.set(bundle.password);
          } else {
            this.hasBundle.set(false);
            this.bundleEmail.set('');
            this.bundlePassword.set('');
          }

          this.supportPortalEnabled.set(response.data.support_portal_enabled === true);
          this.supportMode.set(response.data.support_mode || 'built_in');
          this.supportCustomScript.set(response.data.support_custom_script || '');
          this.initializeSupport();

          // Apply dynamic branding
          this.themeService.applyBranding(
            response.data.system_name,
            response.data.system_logo,
            response.data.theme_primary_color,
            response.data.theme_accent_color,
            response.data.system_slogan_title,
            response.data.system_slogan_subtitle,
            response.data.logo_enabled,
            response.data.theme_font_family,
            response.data.copyright_text,
            response.data.hide_access_restricted_info
          );
        } else {
          this.isTokenValid.set(false);
          this.tokenError.set(response.message || 'Access token is invalid or has expired.');
          this.fetchBrandingFallback();
        }
      },
      error: err => {
        this.isTokenChecking.set(false);
        this.isTokenValid.set(false);
        this.tokenError.set(err.error?.message || 'Failed to authenticate invitation token.');
        this.fetchBrandingFallback();
      }
    });
  }

  private checkPublicAccess() {
    this.isPublicPortalChecking.set(true);
    this.getPublicPlatformsData().subscribe({
      next: response => {
        this.isPublicPortalChecking.set(false);
        this.isPublicPortalEnabled.set(response.public_access_enabled === true);
        this.supportPortalEnabled.set(response.support_portal_enabled === true);
        this.supportMode.set(response.support_mode || 'built_in');
        this.supportCustomScript.set(response.support_custom_script || '');
        this.initializeSupport();

        // Apply dynamic branding
        this.themeService.applyBranding(
          response.system_name,
          response.system_logo,
          response.theme_primary_color,
          response.theme_accent_color,
          response.system_slogan_title,
          response.system_slogan_subtitle,
          response.logo_enabled,
          response.theme_font_family,
          response.copyright_text,
          response.hide_access_restricted_info
        );
      },
      error: () => {
        this.isPublicPortalChecking.set(false);
        this.isPublicPortalEnabled.set(false);
        this.themeService.applyBranding('Raven', '', '#4f46e5', '#6366f1');
      }
    });
  }

  protected onSearchEmail() {
    const emailVal = this.email().trim();
    if (!emailVal) {
      this.toastr.warning('Please enter a valid email address.');
      return;
    }

    this.isSearchingEmail.set(true);
    this.platforms.set([]);
    this.selectedPlatform.set('');

    this.getPublicPlatformsData(emailVal).subscribe({
      next: response => {
        this.isSearchingEmail.set(false);
        if (response.success && response.data) {
          this.platforms.set(response.data);
          this.hideEmail.set(response.hide_email === true);
          this.isEmailSubmitted.set(true);
          if (response.data.length > 0) {
            this.selectedPlatform.set(response.data[0].name);
            this.toastr.success('Found registered email. Please select your platform.');
          } else {
            this.toastr.warning('This email has no configured platforms.');
          }
        }
      },
      error: err => {
        this.isSearchingEmail.set(false);
        const errMsg = err.error?.message || 'This email address is not registered in our system.';
        this.toastr.error(errMsg, 'Search Error');
        this.isEmailSubmitted.set(false);
      }
    });
  }

  protected changeEmail() {
    this.isEmailSubmitted.set(false);
    this.platforms.set([]);
    this.selectedPlatform.set('');
    this.resetPortal();
  }

  protected submitManualToken() {
    const tok = this.manualToken().trim();
    if (!tok) {
      this.toastr.warning('Please enter a valid access token.');
      return;
    }
    this.token.set(tok);
    this.validateToken(tok);
    this.loadPlatforms();
  }

  protected clearToken() {
    this.token.set(null);
    this.tokenError.set(null);
    this.manualToken.set('');
    this.hideEmail.set(false);
    this.showEmail.set(false);
    this.checkPublicAccess();
  }

  protected selectPlatform(name: string) {
    if (this.isLoading()) return;
    this.selectedPlatform.set(name);
  }

  protected onSubmit() {
    if (!this.email() || !this.selectedPlatform()) {
      this.toastr.warning('Please enter your email and select a platform.');
      return;
    }

    // Reset states
    this.isLoading.set(true);
    this.loadingStep.set(1);
    this.loadingMessage.set('Connecting to mail server...');
    this.result.set(null);
    this.errorCode.set(null);
    this.logId.set(null);

    // Reset feedback
    this.showFeedbackWidget.set(false);
    this.feedbackSubmitted.set(false);
    this.feedbackWorking.set(null);
    this.feedbackComment.set('');

    this.runTimelineAnimation();
  }

  private runTimelineAnimation() {
    setTimeout(() => {
      if (!this.isLoading()) return;
      this.loadingStep.set(2);
      this.loadingMessage.set('Accessing inbox folders...');

      setTimeout(() => {
        if (!this.isLoading()) return;
        this.loadingStep.set(3);
        this.loadingMessage.set('Searching for latest Guard security email...');

        this.fetchCodeFromApi();
      }, 900);
    }, 800);
  }

  private fetchCodeFromApi() {
    const payload: any = {
      email: this.email().trim(),
      platform: this.selectedPlatform()
    };

    if (this.token()) {
      payload.token = this.token();
    }

    this.http.post<any>(this.settings.baseUrl + '/public/fetch-code', payload).subscribe({
      next: response => {
        this.isLoading.set(false);
        if (response.success && response.code) {
          this.result.set({
            code: response.code,
            date: response.date
          });
          this.logId.set(response.log_id ?? null);
          this.showFeedbackWidget.set(true);

          if (response.remaining !== undefined && response.remaining !== null) {
            this.remainingUses.set(response.remaining);
          }

          this.toastr.success('Guard code retrieved successfully!');
        } else {
          this.errorCode.set('No security code was found. Please request a new code on the game client.');
          this.toastr.error(response.message || 'No security code was found. Please request a new code on the game client.');
          this.logId.set(response.log_id ?? null);
        }
      },
      error: err => {
        this.isLoading.set(false);
        const errMsg = err.error?.message || err.message || 'An error occurred while connecting to the email server.';
        this.errorCode.set(errMsg);
        this.toastr.error(errMsg, 'Code Fetch Failed');
        this.logId.set(err.error?.log_id ?? null);
      }
    });
  }

  protected copyToClipboard() {
    const code = this.result()?.code;
    if (code) {
      navigator.clipboard.writeText(code).then(() => {
        this.copySuccess.set(true);
        this.toastr.success('Copied to clipboard!');
        setTimeout(() => this.copySuccess.set(false), 2000);
      });
    }
  }

  protected selectFeedback(working: boolean) {
    this.feedbackWorking.set(working);
    // If they choose positive feedback, we can submit immediately or let them add comment.
    // Let's let them add a comment for both, but display the text area.
  }

  protected submitFeedback() {
    if (this.feedbackWorking() === null) {
      this.toastr.warning('Please select whether the code worked or not.');
      return;
    }

    this.isSubmittingFeedback.set(true);

    const payload = {
      email: this.email().trim(),
      platform: this.selectedPlatform(),
      is_working: this.feedbackWorking() as boolean,
      comment: this.feedbackComment().trim() || undefined,
      log_id: this.logId() ?? undefined
    };

    this.feedbackService.submitFeedback(payload).subscribe({
      next: response => {
        this.isSubmittingFeedback.set(false);
        this.feedbackSubmitted.set(true);
        this.toastr.success('Thank you for your feedback!');
      },
      error: err => {
        this.isSubmittingFeedback.set(false);
        this.toastr.error('Failed to submit feedback.');
      }
    });
  }

  protected resetPortal() {
    this.result.set(null);
    this.errorCode.set(null);
    this.logId.set(null);
    this.showFeedbackWidget.set(false);
    this.feedbackSubmitted.set(false);
    this.feedbackWorking.set(null);
    this.feedbackComment.set('');
  }

  protected copyBundleEmail() {
    const emailVal = this.bundleEmail();
    if (emailVal) {
      navigator.clipboard.writeText(emailVal).then(() => {
        this.copyBundleEmailSuccess.set(true);
        this.toastr.success('Username/Email copied!');
        setTimeout(() => this.copyBundleEmailSuccess.set(false), 2000);
      });
    }
  }

  protected copyBundlePassword() {
    const pwdVal = this.bundlePassword();
    if (pwdVal) {
      navigator.clipboard.writeText(pwdVal).then(() => {
        this.copyBundlePasswordSuccess.set(true);
        this.toastr.success('Password copied!');
        setTimeout(() => this.copyBundlePasswordSuccess.set(false), 2000);
      });
    }
  }

  protected togglePasswordVisibility() {
    this.showPassword.update(v => !v);
  }

  protected toggleEmailVisibility() {
    this.showEmail.update(v => !v);
  }

  protected getMaskedText(val: string): string {
    if (!val) return '';
    if (val.includes('@')) {
      const parts = val.split('@');
      const local = parts[0];
      const domain = parts.slice(1).join('@');
      if (local.length <= 1) {
        return '*@' + domain;
      } else if (local.length === 2) {
        return local[0] + '*@' + domain;
      } else {
        return local[0] + '***' + local[local.length - 1] + '@' + domain;
      }
    } else {
      if (val.length <= 1) {
        return '*';
      } else if (val.length === 2) {
        return val[0] + '*';
      } else {
        return val[0] + '***' + val[val.length - 1];
      }
    }
  }

  ngOnDestroy(): void {
    if (this.supportPollInterval) {
      clearInterval(this.supportPollInterval);
    }
    this.cleanupCustomScripts();
    this.renderer.removeClass(document.body, 'dark');
    this.renderer.removeStyle(document.body, 'background-color');
  }

  protected initializeSupport() {
    if (!this.supportPortalEnabled()) {
      this.cleanupCustomScripts();
      if (this.supportPollInterval) {
        clearInterval(this.supportPollInterval);
        this.supportPollInterval = null;
      }
      return;
    }

    if (this.supportMode() === 'custom_script') {
      if (this.supportPollInterval) {
        clearInterval(this.supportPollInterval);
        this.supportPollInterval = null;
      }
      this.injectCustomScript();
    } else {
      this.cleanupCustomScripts();
      // Initialize built-in support
      let threadToken = localStorage.getItem('support_thread_token');
      if (!threadToken) {
        threadToken = 'supp_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
        localStorage.setItem('support_thread_token', threadToken);
      }
      this.supportThreadToken.set(threadToken);

      // Load existing thread messages
      this.loadSupportMessages();

      // Start polling every 5 seconds
      if (!this.supportPollInterval) {
        this.supportPollInterval = setInterval(() => {
          this.loadSupportMessages();
        }, 5000);
      }
    }
  }

  private injectCustomScript() {
    this.cleanupCustomScripts();

    const container = document.createElement('div');
    container.id = 'custom-support-script-container';
    container.innerHTML = this.supportCustomScript();
    document.body.appendChild(container);

    // Execute scripts inside the injected container by appending to the head
    const scripts = container.querySelectorAll('script');
    scripts.forEach(oldScript => {
      const newScript = document.createElement('script');
      Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
      newScript.textContent = oldScript.textContent || oldScript.innerHTML;
      newScript.setAttribute('data-custom-support-script', 'true');
      document.head.appendChild(newScript);
      oldScript.remove();
    });
  }

  private cleanupCustomScripts() {
    const container = document.getElementById('custom-support-script-container');
    if (container) {
      container.remove();
    }
    document.querySelectorAll('script[data-custom-support-script="true"]').forEach(el => el.remove());
  }

  protected loadSupportMessages() {
    const threadTok = this.supportThreadToken();
    if (!threadTok) return;

    this.supportService.getThread(threadTok).subscribe({
      next: response => {
        if (response.success && response.data && response.data.messages) {
          this.supportMessages.set(response.data.messages);
        }
      }
    });
  }

  protected sendSupportMessage() {
    const msg = this.newSupportMessage().trim();
    const threadTok = this.supportThreadToken();
    if (!msg || !threadTok || this.isSendingSupportMessage()) return;

    this.isSendingSupportMessage.set(true);
    const payload = {
      thread_token: threadTok,
      message: msg,
      token: this.token() || undefined
    };

    this.supportService.sendMessage(payload).subscribe({
      next: response => {
        this.isSendingSupportMessage.set(false);
        this.newSupportMessage.set('');
        if (response.success && response.data) {
          this.supportMessages.set(response.data.messages || []);
        }
      },
      error: () => {
        this.isSendingSupportMessage.set(false);
        this.toastr.error('Failed to send support message.');
      }
    });
  }

  private loadStaticPages() {
    this.staticPageService.getPublicPages().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.staticPages.set(response.data.filter(p => p.show_in_footer));
        }
      }
    });
  }

  protected openPageModal(slug: string | undefined) {
    if (!slug) return;
    this.isLoadingPageContent.set(true);
    this.showPageModal.set(true);
    this.pageModalTitle.set('Loading...');
    this.pageModalContent.set('');

    this.staticPageService.getPublicPage(slug).subscribe({
      next: response => {
        this.isLoadingPageContent.set(false);
        if (response.success && response.data) {
          this.pageModalTitle.set(response.data.title);
          this.pageModalContent.set(response.data.content || '');
        } else {
          this.toastr.error('Failed to load page content.');
          this.showPageModal.set(false);
        }
      },
      error: () => {
        this.isLoadingPageContent.set(false);
        this.toastr.error('Failed to load page content.');
        this.showPageModal.set(false);
      }
    });
  }

  protected closePageModal() {
    this.showPageModal.set(false);
  }

  protected toggleDarkMode() {
    this.isDarkMode.update(v => !v);
    localStorage.setItem('theme', this.isDarkMode() ? 'dark' : 'light');
    this.updateBodyBackground();
  }

  private updateBodyBackground() {
    if (this.isDarkMode()) {
      this.renderer.addClass(document.body, 'dark');
      this.renderer.setStyle(document.body, 'background-color', '#0f172a'); // tailwind slate-900
    } else {
      this.renderer.removeClass(document.body, 'dark');
      this.renderer.setStyle(document.body, 'background-color', '#f8fafc'); // tailwind slate-100
    }
  }

  private fetchBrandingFallback() {
    this.getPublicPlatformsData().subscribe({
      next: response => {
        this.themeService.applyBranding(
          response.system_name,
          response.system_logo,
          response.theme_primary_color,
          response.theme_accent_color,
          response.system_slogan_title,
          response.system_slogan_subtitle,
          response.logo_enabled,
          response.theme_font_family,
          response.copyright_text,
          response.hide_access_restricted_info
        );
      }
    });
  }
}
