import { Component, OnInit, inject, signal, computed } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, FormsModule, Validators } from '@angular/forms';
import { AccountBundleService, AccountBundle } from '../../services/account-bundle-service';
import { AccessGrantService } from '../../services/access-grant-service';
import { PlatformService, Platform } from '../../services/platform-service';
import { LayoutServices } from '../../services/layout-services';
import { ToastrService } from 'ngx-toastr';

@Component({
  selector: 'app-account-bundles',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule],
  templateUrl: './account-bundles.html',
  styleUrl: './account-bundles.css'
})
export class AccountBundles implements OnInit {
  private bundleService = inject(AccountBundleService);
  private grantService = inject(AccessGrantService);
  private platformService = inject(PlatformService);
  private layoutService = inject(LayoutServices);
  private toastr = inject(ToastrService);
  private fb = inject(FormBuilder);

  protected bundles = signal<AccountBundle[]>([]);
  protected emails = signal<string[]>([]);
  protected platforms = signal<Platform[]>([]);

  // CRUD Modals
  protected showAddEditModal = signal<boolean>(false);
  protected isEditing = signal<boolean>(false);
  protected editingBundleId: number | null = null;
  protected isSubmitting = signal<boolean>(false);

  // Wizard state signals for Add/Edit Bundle modal
  protected wizardStep = signal<number>(1);
  protected platformSearch = signal<string>('');
  protected emailSearch = signal<string>('');
  protected customEmailInput = signal<string>('');
  protected useCustomEmail = signal<boolean>(false);

  // Filtered platforms for wizard step 1
  protected filteredPlatformsForWizard = computed(() => {
    const query = this.platformSearch().toLowerCase().trim();
    const all = this.platforms();
    if (!query) return all;
    return all.filter(p => p.name.toLowerCase().includes(query));
  });

  // Filtered emails for wizard step 2
  protected filteredEmailsForWizard = computed(() => {
    const query = this.emailSearch().toLowerCase().trim();
    const all = this.emails();
    if (!query) return all;
    return all.filter(e => e.toLowerCase().includes(query));
  });

  // Bulk generation modal
  protected showBulkModal = signal<boolean>(false);
  protected selectedBundle = signal<AccountBundle | null>(null);
  protected bulkQuantity = signal<number>(50);
  protected bulkLimit = signal<number>(20);
  protected bulkUnlimited = signal<boolean>(false);
  protected bulkHasExpiry = signal<boolean>(false);
  protected bulkExpiresAt = signal<string>('');
  protected bulkHideEmail = signal<boolean>(false);
  protected isGeneratingBulk = signal<boolean>(false);

  // Control Panel & Advanced Exporter GUI State
  protected activePanelTab = signal<'generate' | 'manage' | 'export'>('generate');
  protected bulkTag = signal<string>('');
  protected customPrefix = signal<string>('');
  protected bundleGrants = signal<any[]>([]);
  protected selectedGrantsForExport = signal<number[]>([]);
  protected filterTag = signal<string>('all');
  protected uniqueTags = signal<string[]>([]);
  protected exportFormat = signal<string>('custom'); // 'custom', 'csv', 'json', 'xml'
  protected customTemplate = signal<string>('{url}'); // default custom template
  protected exportRecordSeparator = signal<string>('newline');
  protected exportCsvHeaders = signal<boolean>(true);
  protected csvDelimiter = signal<string>(',');
  protected exportJsonTemplate = signal<string>('{\n  "token": "{token}",\n  "link": "{url}"\n}');
  protected exportXmlRootTag = signal<string>('tokens');
  protected exportXmlItemTag = signal<string>('token_item');

  protected bundleForm: FormGroup = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(2)]],
    email: ['', [Validators.required, Validators.email]],
    login_username: [''],
    platform: ['', [Validators.required]],
    password: ['', [Validators.required]],
    is_active: [true],
    hide_email: [false]
  });

  // Filtered grants based on selected Tag filter
  protected filteredGrants = computed(() => {
    const grants = this.bundleGrants();
    const tag = this.filterTag();
    if (tag === 'all') return grants;
    if (tag === 'untagged') return grants.filter(g => !g.tag);
    return grants.filter(g => g.tag === tag);
  });

  // Computed live export preview text
  protected exportPreview = computed(() => {
    const selectedIds = this.selectedGrantsForExport();
    const allGrants = this.bundleGrants();
    const grants = allGrants.filter(g => selectedIds.includes(g.id));
    const format = this.exportFormat();
    const template = this.customTemplate();

    if (grants.length === 0) {
      return 'No tokens selected. Go to the "Token List & Batches" tab to select which tokens to export.';
    }

    if (format === 'json') {
      const evaluated = grants.map(g => {
        const url = `${window.location.origin}/grab-code?token=${g.token}`;
        let str = this.exportJsonTemplate();
        str = str.replace(/{token}/g, g.token || '');
        str = str.replace(/{url}/g, url);
        str = str.replace(/{email}/g, g.email || '');
        str = str.replace(/{platform}/g, g.platform || '');
        str = str.replace(/{limit}/g, g.limit !== null && g.limit !== undefined ? String(g.limit) : 'unlimited');
        str = str.replace(/{expires_at}/g, g.expires_at ? g.expires_at : 'never');
        return str;
      });
      try {
        const joined = evaluated.join(',\n');
        return `[\n${joined}\n]`;
      } catch (e) {
        return 'Invalid JSON Template: ' + (e as Error).message;
      }
    }

    if (format === 'csv') {
      const delimiter = this.csvDelimiter();
      const includeHeaders = this.exportCsvHeaders();
      const headers = includeHeaders ? `Token${delimiter}URL${delimiter}Email${delimiter}Platform${delimiter}Limit${delimiter}ExpiresAt\n` : '';
      const rows = grants.map(g => {
        const url = `${window.location.origin}/grab-code?token=${g.token}`;
        return `"${g.token}"${delimiter}"${url}"${delimiter}"${g.email}"${delimiter}"${g.platform}"${delimiter}"${g.limit ?? 'unlimited'}"${delimiter}"${g.expires_at ?? 'never'}"`;
      }).join('\n');
      return headers + rows;
    }

    if (format === 'xml') {
      const root = this.exportXmlRootTag() || 'tokens';
      const item = this.exportXmlItemTag() || 'token_item';
      let xml = `<?xml version="1.0" encoding="UTF-8"?>\n<${root}>\n`;
      grants.forEach(g => {
        const url = `${window.location.origin}/grab-code?token=${g.token}`;
        xml += `  <${item}>\n`;
        xml += `    <token>${g.token}</token>\n`;
        xml += `    <url>${url}</url>\n`;
        xml += `    <email>${g.email}</email>\n`;
        xml += `    <platform>${g.platform}</platform>\n`;
        xml += `    <limit>${g.limit ?? 'unlimited'}</limit>\n`;
        xml += `    <expires_at>${g.expires_at ?? 'never'}</expires_at>\n`;
        xml += `  </${item}>\n`;
      });
      xml += `</${root}>`;
      return xml;
    }

    if (format === 'custom') {
      const separator = this.exportRecordSeparator();
      let sepStr = '\n';
      if (separator === 'double-newline') sepStr = '\n\n';
      else if (separator === 'comma') sepStr = ', ';
      else if (separator === 'semicolon') sepStr = '; ';

      return grants.map(g => {
        const url = `${window.location.origin}/grab-code?token=${g.token}`;
        let res = template;
        res = res.replace(/{token}/g, g.token || '');
        res = res.replace(/{url}/g, url);
        res = res.replace(/{email}/g, g.email || '');
        res = res.replace(/{platform}/g, g.platform || '');
        res = res.replace(/{limit}/g, g.limit !== null && g.limit !== undefined ? String(g.limit) : 'unlimited');
        res = res.replace(/{expires_at}/g, g.expires_at ? g.expires_at : 'never');
        return res;
      }).join(sepStr);
    }

    return '';
  });

  constructor() {
    this.layoutService.setPageTitle('Account Bundles');
  }

  ngOnInit(): void {
    this.loadBundles();
    this.loadDropdowns();
  }

  protected loadBundles() {
    this.bundleService.getBundles().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.bundles.set(response.data);
        }
      },
      error: () => {
        this.toastr.error('Failed to load account bundles.');
      }
    });
  }

  protected loadDropdowns() {
    // Load registered emails
    this.grantService.getEmails().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.emails.set(response.data);
          if (response.data.length > 0) {
            this.bundleForm.patchValue({ email: response.data[0] });
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
            this.bundleForm.patchValue({ platform: response.data[0].name });
          }
        }
      }
    });
  }

  // --- CRUD Modals ---
  protected onClickAddBundle() {
    this.isEditing.set(false);
    this.editingBundleId = null;
    this.wizardStep.set(1);
    this.platformSearch.set('');
    this.emailSearch.set('');
    this.customEmailInput.set('');
    this.useCustomEmail.set(false);
    this.bundleForm.reset({
      name: '',
      email: this.emails().length > 0 ? this.emails()[0] : '',
      login_username: '',
      platform: this.platforms().length > 0 ? this.platforms()[0].name : '',
      password: '',
      is_active: true,
      hide_email: false
    });
    // Password is required on create
    this.bundleForm.get('password')?.setValidators([Validators.required]);
    this.bundleForm.get('password')?.updateValueAndValidity();
    this.showAddEditModal.set(true);
  }

  protected onClickEditBundle(bundle: AccountBundle) {
    this.isEditing.set(true);
    this.editingBundleId = bundle.id ?? null;
    this.wizardStep.set(1);
    this.platformSearch.set('');
    this.emailSearch.set('');

    const isRegistered = this.emails().includes(bundle.email);
    if (isRegistered) {
      this.useCustomEmail.set(false);
      this.customEmailInput.set('');
    } else {
      this.useCustomEmail.set(true);
      this.customEmailInput.set(bundle.email);
    }

    this.bundleForm.patchValue({
      name: bundle.name,
      email: bundle.email,
      login_username: bundle.login_username || bundle.email,
      platform: bundle.platform,
      password: '', // Empty password field for updates
      is_active: bundle.is_active,
      hide_email: bundle.hide_email === true
    });
    // Password is NOT required on update
    this.bundleForm.get('password')?.clearValidators();
    this.bundleForm.get('password')?.updateValueAndValidity();
    this.showAddEditModal.set(true);
  }

  protected nextWizardStep() {
    const step = this.wizardStep();
    if (step === 1) {
      if (this.bundleForm.get('platform')?.invalid) {
        this.toastr.warning('Please select a platform first.');
        return;
      }
      this.wizardStep.set(2);
    } else if (step === 2) {
      if (this.bundleForm.get('email')?.invalid) {
        this.toastr.warning('Please enter or select a valid email.');
        return;
      }
      
      // Default the login_username control to the email if it is currently blank
      const loginUser = this.bundleForm.get('login_username')?.value;
      if (!loginUser || loginUser.trim() === '') {
        this.bundleForm.patchValue({
          login_username: this.bundleForm.get('email')?.value
        });
      }
      
      this.wizardStep.set(3);
    }
  }

  protected prevWizardStep() {
    const step = this.wizardStep();
    if (step > 1) {
      this.wizardStep.set(step - 1);
    }
  }

  protected selectPlatformInWizard(platformName: string) {
    this.bundleForm.patchValue({ platform: platformName });
    this.nextWizardStep(); // Auto-advance
  }

  protected selectEmailInWizard(email: string) {
    this.bundleForm.patchValue({ email: email });
    this.useCustomEmail.set(false);
    this.nextWizardStep(); // Auto-advance
  }

  protected toggleCustomEmailInWizard(useCustom: boolean) {
    this.useCustomEmail.set(useCustom);
    if (useCustom) {
      this.bundleForm.patchValue({ email: this.customEmailInput() });
    } else {
      const defaultEmail = this.emails().length > 0 ? this.emails()[0] : '';
      this.bundleForm.patchValue({ email: defaultEmail });
    }
  }

  protected onCustomEmailChange(event: any) {
    const val = event.target.value;
    this.customEmailInput.set(val);
    if (this.useCustomEmail()) {
      this.bundleForm.patchValue({ email: val });
    }
  }

  protected onClickCloseModal() {
    this.showAddEditModal.set(false);
  }

  protected onSubmitBundle() {
    if (this.bundleForm.invalid) {
      this.toastr.error('Please fix validation errors in the form.');
      return;
    }

    this.isSubmitting.set(true);
    const payload = { ...this.bundleForm.value };

    if (this.isEditing() && this.editingBundleId !== null) {
      this.bundleService.updateBundle(this.editingBundleId, payload).subscribe({
        next: response => {
          this.isSubmitting.set(false);
          this.toastr.success(response.message || 'Account bundle updated successfully.');
          this.showAddEditModal.set(false);
          this.loadBundles();
        },
        error: err => {
          this.isSubmitting.set(false);
          this.toastr.error(err.error?.message || 'Failed to update account bundle.');
        }
      });
    } else {
      this.bundleService.createBundle(payload).subscribe({
        next: response => {
          this.isSubmitting.set(false);
          this.toastr.success(response.message || 'Account bundle created successfully.');
          this.showAddEditModal.set(false);
          this.loadBundles();
        },
        error: err => {
          this.isSubmitting.set(false);
          this.toastr.error(err.error?.message || 'Failed to create account bundle.');
        }
      });
    }
  }

  protected onClickDeleteBundle(bundle: AccountBundle) {
    if (bundle.id === undefined) return;
    if (confirm(`Are you sure you want to delete the account bundle "${bundle.name}"? This will revoke and delete ALL generated access grants associated with this bundle.`)) {
      this.bundleService.deleteBundle(bundle.id).subscribe({
        next: response => {
          this.toastr.success(response.message || 'Account bundle deleted.');
          this.loadBundles();
        },
        error: err => {
          this.toastr.error(err.error?.message || 'Failed to delete account bundle.');
        }
      });
    }
  }

  // --- Bulk Token Generation ---
  protected onClickBulkGenerate(bundle: AccountBundle) {
    this.selectedBundle.set(bundle);
    this.bulkQuantity.set(50);
    this.bulkLimit.set(20);
    this.bulkUnlimited.set(false);
    this.bulkHasExpiry.set(false);
    this.bulkExpiresAt.set('');
    this.bulkHideEmail.set(bundle.hide_email === true);
    this.bulkTag.set('');
    this.customPrefix.set('');
    this.activePanelTab.set('generate');
    this.filterTag.set('all');
    if (bundle.id !== undefined) {
      this.loadBundleGrants(bundle.id);
    }
    this.showBulkModal.set(true);
  }

  protected loadBundleGrants(bundleId: number) {
    this.grantService.getGrants().subscribe({
      next: response => {
        if (response.success && response.data) {
          const grants = response.data.filter((g: any) => g.account_bundle_id === bundleId);
          this.bundleGrants.set(grants);
          // Pre-select all matching visible tokens
          this.selectedGrantsForExport.set(grants.map((g: any) => g.id));
          this.loadUniqueTags();
        }
      }
    });
  }

  protected loadUniqueTags() {
    this.grantService.getTags().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.uniqueTags.set(response.data);
        }
      }
    });
  }

  protected setQuickLimit(lim: number) {
    this.bulkLimit.set(lim);
    this.bulkUnlimited.set(false);
  }

  protected onGenerateBulk() {
    const bundle = this.selectedBundle();
    if (!bundle || bundle.id === undefined) return;

    this.isGeneratingBulk.set(true);

    const payload: any = {
      account_bundle_id: bundle.id,
      quantity: this.bulkQuantity(),
      limit: this.bulkUnlimited() ? null : this.bulkLimit(),
      tag: this.bulkTag().trim() || null,
      prefix: this.customPrefix().trim() || null,
      hide_email: this.bulkHideEmail()
    };

    if (this.bulkHasExpiry() && this.bulkExpiresAt()) {
      payload.expires_at = this.bulkExpiresAt();
    }

    this.bundleService.generateBulk(payload).subscribe({
      next: response => {
        this.isGeneratingBulk.set(false);
        if (response.success && response.data) {
          this.toastr.success(`Successfully generated ${response.data.length} tokens.`);
          this.loadBundleGrants(bundle.id!);
          this.activePanelTab.set('manage');
        }
      },
      error: err => {
        this.isGeneratingBulk.set(false);
        this.toastr.error(err.error?.message || 'Failed to generate tokens in bulk.');
      }
    });
  }

  protected toggleGrantSelection(id: number) {
    const selected = [...this.selectedGrantsForExport()];
    const index = selected.indexOf(id);
    if (index > -1) {
      selected.splice(index, 1);
    } else {
      selected.push(id);
    }
    this.selectedGrantsForExport.set(selected);
  }

  protected isGrantSelected(id: number): boolean {
    return this.selectedGrantsForExport().includes(id);
  }

  protected toggleAllVisible(event: any) {
    const checked = event.target.checked;
    const visibleIds = this.filteredGrants().map(g => g.id);
    let selected = [...this.selectedGrantsForExport()];
    
    if (checked) {
      visibleIds.forEach(id => {
        if (!selected.includes(id)) {
          selected.push(id);
        }
      });
    } else {
      selected = selected.filter(id => !visibleIds.includes(id));
    }
    this.selectedGrantsForExport.set(selected);
  }

  protected isAllVisibleSelected(): boolean {
    const visibleGrants = this.filteredGrants();
    if (visibleGrants.length === 0) return false;
    const selected = this.selectedGrantsForExport();
    return visibleGrants.every(g => selected.includes(g.id));
  }

  protected onRevokeSelected() {
    const selectedIds = this.selectedGrantsForExport();
    if (selectedIds.length === 0) {
      this.toastr.warning('No tokens selected.');
      return;
    }
    if (confirm(`Are you sure you want to revoke and delete ${selectedIds.length} selected tokens?`)) {
      this.grantService.revokeBulk(selectedIds).subscribe({
        next: response => {
          this.toastr.success(response.message || 'Selected tokens revoked.');
          const bundle = this.selectedBundle();
          if (bundle && bundle.id !== undefined) {
            this.loadBundleGrants(bundle.id!);
          }
        },
        error: err => {
          this.toastr.error(err.error?.message || 'Failed to revoke tokens.');
        }
      });
    }
  }

  protected onRevokeTagGroup(tag: string) {
    if (!tag) return;
    if (confirm(`Are you sure you want to revoke and delete ALL tokens matching the tag "${tag}"?`)) {
      this.grantService.revokeTag(tag).subscribe({
        next: response => {
          this.toastr.success(response.message || `Tokens matching tag "${tag}" revoked.`);
          const bundle = this.selectedBundle();
          if (bundle && bundle.id !== undefined) {
            this.loadBundleGrants(bundle.id!);
          }
        },
        error: err => {
          this.toastr.error(err.error?.message || 'Failed to revoke tokens.');
        }
      });
    }
  }

  protected copyExportToClipboard() {
    const text = this.exportPreview();
    if (text) {
      navigator.clipboard.writeText(text).then(() => {
        this.toastr.success('Export data copied to clipboard!');
      }).catch(() => {
        this.toastr.error('Failed to copy data.');
      });
    }
  }

  protected downloadExportFile() {
    const text = this.exportPreview();
    if (!text || text.startsWith('No tokens selected')) return;

    let extension = 'txt';
    let mimeType = 'text/plain';

    if (this.exportFormat() === 'json') {
      extension = 'json';
      mimeType = 'application/json';
    } else if (this.exportFormat() === 'csv') {
      extension = 'csv';
      mimeType = 'text/csv';
    } else if (this.exportFormat() === 'xml') {
      extension = 'xml';
      mimeType = 'application/xml';
    }

    const blob = new Blob([text], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `bundle_${this.selectedBundle()?.id || 'tokens'}_export.${extension}`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    this.toastr.success('File download started!');
  }

  protected onCloseBulkModal() {
    this.showBulkModal.set(false);
  }
}
