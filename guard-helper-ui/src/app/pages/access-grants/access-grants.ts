import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AccessGrantService, AccessGrant } from '../../services/access-grant-service';
import { PlatformService, Platform } from '../../services/platform-service';
import { LayoutServices } from '../../services/layout-services';
import { ToastrService } from 'ngx-toastr';

@Component({
  selector: 'app-access-grants',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './access-grants.html',
  styleUrl: './access-grants.css'
})
export class AccessGrants implements OnInit {
  private grantService = inject(AccessGrantService);
  private platformService = inject(PlatformService);
  private layoutService = inject(LayoutServices);
  private toastr = inject(ToastrService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);

  protected grants = signal<AccessGrant[]>([]);
  protected emails = signal<string[]>([]);
  protected platforms = signal<Platform[]>([]);

  // Wizard state signals
  protected showWizard = signal<boolean>(false);
  protected wizardStep = signal<number>(1);
  protected emailSearchQuery = signal<string>('');
  protected platformSearchQuery = signal<string>('');
  protected wizardSelectedEmail = signal<string>('');
  protected wizardSelectedPlatform = signal<string>('');
  protected wizardLimit = signal<number>(20);
  protected wizardUnlimited = signal<boolean>(false);
  protected wizardHasExpiry = signal<boolean>(false);
  protected wizardExpiresAt = signal<string>('');
  protected wizardHideEmail = signal<boolean>(false);
  protected generatedTokenLink = signal<string>('');

  protected selectedEmail = signal<string>('');
  protected selectedPlatform = signal<string>('');
  protected limit = signal<number>(20);
  protected isSubmitting = signal<boolean>(false);

  // Search, Filtering, and Pagination state
  protected searchQuery = signal<string>('');
  protected filterStatus = signal<string>('all');
  protected filterPlatform = signal<string>('all');
  protected filterTag = signal<string>('all');
  protected tags = signal<string[]>([]);
  protected currentPage = signal<number>(1);
  protected pageSize = signal<number>(10);
  protected sortField = signal<string>('created_at');
  protected sortAsc = signal<boolean>(false);

  // Filtered computed lists for Wizard
  protected filteredEmails = computed(() => {
    const q = this.emailSearchQuery().toLowerCase().trim();
    const list = this.emails();
    if (!q) return list;
    return list.filter(email => email.toLowerCase().includes(q));
  });

  protected filteredPlatforms = computed(() => {
    const q = this.platformSearchQuery().toLowerCase().trim();
    const list = this.platforms();
    if (!q) return list;
    return list.filter(platform => platform.name.toLowerCase().includes(q));
  });

  // Advanced searching, filtering, and sorting computed properties
  protected filteredGrants = computed(() => {
    let list = this.grants();
    const query = this.searchQuery().trim().toLowerCase();
    const status = this.filterStatus();
    const platform = this.filterPlatform();
    const tag = this.filterTag();

    // 1. Search Query
    if (query) {
      list = list.filter(g => 
        g.email.toLowerCase().includes(query) || 
        g.token.toLowerCase().includes(query) || 
        (g.tag && g.tag.toLowerCase().includes(query))
      );
    }

    // 2. Status Filter
    if (status !== 'all') {
      list = list.filter(g => {
        const expired = this.isExpired(g);
        return status === 'active' ? !expired : expired;
      });
    }

    // 3. Platform Filter
    if (platform !== 'all') {
      list = list.filter(g => g.platform === platform);
    }

    // 4. Tag Filter
    if (tag !== 'all') {
      list = list.filter(g => g.tag === tag);
    }

    // 5. Sorting
    const field = this.sortField();
    const asc = this.sortAsc();
    list = [...list].sort((a: any, b: any) => {
      let valA: any = a[field];
      let valB: any = b[field];

      if (field === 'expires_at') {
        valA = valA ? new Date(valA).getTime() : (asc ? Infinity : -Infinity);
        valB = valB ? new Date(valB).getTime() : (asc ? Infinity : -Infinity);
      } else if (field === 'uses') {
        valA = a.uses;
        valB = b.uses;
      } else if (field === 'created_at') {
        valA = a.created_at ? new Date(a.created_at).getTime() : 0;
        valB = b.created_at ? new Date(b.created_at).getTime() : 0;
      } else {
        valA = String(valA || '').toLowerCase();
        valB = String(valB || '').toLowerCase();
      }

      if (valA < valB) return asc ? -1 : 1;
      if (valA > valB) return asc ? 1 : -1;
      return 0;
    });

    return list;
  });

  protected paginatedGrants = computed(() => {
    const list = this.filteredGrants();
    const page = this.currentPage();
    const size = this.pageSize();
    const start = (page - 1) * size;
    return list.slice(start, start + size);
  });

  protected totalPages = computed(() => {
    return Math.ceil(this.filteredGrants().length / this.pageSize()) || 1;
  });

  protected rangeStart = computed(() => {
    if (this.filteredGrants().length === 0) return 0;
    return (this.currentPage() - 1) * this.pageSize() + 1;
  });

  protected rangeEnd = computed(() => {
    const end = this.currentPage() * this.pageSize();
    return Math.min(end, this.filteredGrants().length);
  });

  constructor() {
    this.layoutService.setPageTitle('Access Tokens');
  }

  ngOnInit(): void {
    this.loadGrants();
    this.loadDropdowns();
    this.loadTags();

    // Check if redirected with preset email
    this.route.queryParams.subscribe(params => {
      const emailParam = params['email'];
      if (emailParam) {
        this.wizardSelectedEmail.set(emailParam);
        this.wizardStep.set(2); // Jump direct to select platform
        this.showWizard.set(true);

        // Remove the query param from URL bar without reloading page
        this.router.navigate([], {
          relativeTo: this.route,
          queryParams: { email: null },
          queryParamsHandling: 'merge'
        });
      }
    });
  }

  protected loadGrants() {
    this.grantService.getGrants().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.grants.set(response.data);
        }
      },
      error: () => {
        this.toastr.error('Failed to load access tokens.');
      }
    });
  }

  protected loadDropdowns() {
    // Load emails
    this.grantService.getEmails().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.emails.set(response.data);
          if (response.data.length > 0) {
            this.selectedEmail.set(response.data[0]);
            if (!this.wizardSelectedEmail()) {
              this.wizardSelectedEmail.set(response.data[0]);
            }
          }
        }
      }
    });

    // Load platforms
    this.platformService.getPlatforms().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.platforms.set(response.data);
          if (response.data.length > 0) {
            this.selectedPlatform.set(response.data[0].name);
            if (!this.wizardSelectedPlatform()) {
              this.wizardSelectedPlatform.set(response.data[0].name);
            }
          }
        }
      }
    });
  }

  protected loadTags() {
    this.grantService.getTags().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.tags.set(response.data);
        }
      }
    });
  }

  // Wizard action handlers
  protected openWizard() {
    this.wizardStep.set(1);
    this.emailSearchQuery.set('');
    this.platformSearchQuery.set('');
    if (this.emails().length > 0) {
      this.wizardSelectedEmail.set(this.emails()[0]);
    }
    if (this.platforms().length > 0) {
      this.wizardSelectedPlatform.set(this.platforms()[0].name);
    }
    this.wizardLimit.set(20);
    this.wizardUnlimited.set(false);
    this.wizardHasExpiry.set(false);
    this.wizardExpiresAt.set('');
    this.wizardHideEmail.set(false);
    this.generatedTokenLink.set('');
    this.showWizard.set(true);
  }

  protected closeWizard() {
    this.showWizard.set(false);
  }

  protected setStep(step: number) {
    if (step === 2 && !this.wizardSelectedEmail()) {
      this.toastr.warning('Please select an email account.');
      return;
    }
    if (step === 3 && !this.wizardSelectedPlatform()) {
      this.toastr.warning('Please select a platform.');
      return;
    }
    this.wizardStep.set(step);
  }

  protected nextStep() {
    this.setStep(this.wizardStep() + 1);
  }

  protected prevStep() {
    if (this.wizardStep() > 1) {
      this.wizardStep.set(this.wizardStep() - 1);
    }
  }

  protected selectWizardEmail(email: string) {
    this.wizardSelectedEmail.set(email);
    this.nextStep();
  }

  protected selectWizardPlatform(platformName: string) {
    this.wizardSelectedPlatform.set(platformName);
    this.nextStep();
  }

  protected setQuickLimit(lim: number) {
    this.wizardLimit.set(lim);
    this.wizardUnlimited.set(false);
  }

  protected generateWizardToken() {
    if (!this.wizardSelectedEmail() || !this.wizardSelectedPlatform()) {
      this.toastr.warning('Please complete all wizard steps first.');
      return;
    }

    this.isSubmitting.set(true);
    const payload: any = {
      email: this.wizardSelectedEmail(),
      platform: this.wizardSelectedPlatform(),
      limit: this.wizardUnlimited() ? null : this.wizardLimit(),
      hide_email: this.wizardHideEmail()
    };

    if (this.wizardHasExpiry() && this.wizardExpiresAt()) {
      payload.expires_at = this.wizardExpiresAt();
    }

    this.grantService.createGrant(payload).subscribe({
      next: response => {
        this.isSubmitting.set(false);
        if (response.success && response.data) {
          this.generatedTokenLink.set(this.getAccessLink(response.data.token));
          this.toastr.success('Access token generated successfully!');
          this.loadGrants();
          this.loadTags();
          this.wizardStep.set(4);
        }
      },
      error: err => {
        this.isSubmitting.set(false);
        this.toastr.error(err.error?.message || 'Failed to generate access token.');
      }
    });
  }

  protected copyWizardLink() {
    const link = this.generatedTokenLink();
    if (link) {
      navigator.clipboard.writeText(link).then(() => {
        this.toastr.success('Link copied to clipboard!');
      }).catch(() => {
        this.toastr.error('Failed to copy link.');
      });
    }
  }

  protected createToken() {
    if (!this.selectedEmail() || !this.selectedPlatform()) {
      this.toastr.warning('Please select an email and a platform.');
      return;
    }

    this.isSubmitting.set(true);
    const payload = {
      email: this.selectedEmail(),
      platform: this.selectedPlatform(),
      limit: this.limit()
    };

    this.grantService.createGrant(payload).subscribe({
      next: response => {
        this.isSubmitting.set(false);
        if (response.success) {
          this.toastr.success('Access token generated successfully!');
          this.loadGrants();
        }
      },
      error: err => {
        this.isSubmitting.set(false);
        this.toastr.error(err.error?.message || 'Failed to generate access token.');
      }
    });
  }

  protected deleteToken(id: number) {
    if (confirm('Are you sure you want to revoke and delete this access token?')) {
      this.grantService.deleteGrant(id).subscribe({
        next: response => {
          if (response.success) {
            this.toastr.success('Access token revoked.');
            this.loadGrants();
            this.loadTags();
          }
        },
        error: () => {
          this.toastr.error('Failed to revoke access token.');
        }
      });
    }
  }

  protected getAccessLink(token: string): string {
    return `${window.location.origin}/grab-code?token=${token}`;
  }

  protected copyLink(token: string) {
    const link = this.getAccessLink(token);
    navigator.clipboard.writeText(link).then(() => {
      this.toastr.success('Access link copied to clipboard!');
    }).catch(() => {
      this.toastr.error('Failed to copy link.');
    });
  }

  protected isExpired(grant: AccessGrant): boolean {
    if (!grant.is_active) return true;
    if (grant.limit !== null && grant.uses >= grant.limit) return true;
    if (grant.expires_at) {
      return new Date(grant.expires_at).getTime() < Date.now();
    }
    return false;
  }

  protected nextPage() {
    if (this.currentPage() < this.totalPages()) {
      this.currentPage.update(p => p + 1);
    }
  }

  protected prevPage() {
    if (this.currentPage() > 1) {
      this.currentPage.update(p => p - 1);
    }
  }

  protected setPage(page: number) {
    this.currentPage.set(page);
  }

  protected changePageSize(size: number) {
    this.pageSize.set(size);
    this.currentPage.set(1);
  }

  protected toggleSort(field: string) {
    if (this.sortField() === field) {
      this.sortAsc.update(a => !a);
    } else {
      this.sortField.set(field);
      this.sortAsc.set(true);
    }
    this.currentPage.set(1);
  }

  protected onFilterChange() {
    this.currentPage.set(1);
  }

  protected resetFilters() {
    this.searchQuery.set('');
    this.filterStatus.set('all');
    this.filterPlatform.set('all');
    this.filterTag.set('all');
    this.currentPage.set(1);
  }
}
