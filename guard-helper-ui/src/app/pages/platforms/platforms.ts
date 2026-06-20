import { Component, inject, signal } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { PlatformService, Platform } from '../../services/platform-service';
import { GmailService } from '../../services/gmail-service';
import { OutlookService } from '../../services/outlook-service';
import { ImapService } from '../../services/imap-service';
import { ToastrService } from 'ngx-toastr';
import { CommonModule } from '@angular/common';
import { LayoutServices } from '../../services/layout-services';
import { forkJoin } from 'rxjs';

interface EmailAccountItem {
  email: string;
  type: 'gmail' | 'outlook' | 'imap';
}

@Component({
  selector: 'app-platforms',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './platforms.html',
  styleUrl: './platforms.css',
})
export class Platforms {
  private platformService = inject(PlatformService);
  private gmailService = inject(GmailService);
  private outlookService = inject(OutlookService);
  private imapService = inject(ImapService);
  private toastr = inject(ToastrService);
  private fb = inject(FormBuilder);
  private layoutServices = inject(LayoutServices);

  protected platforms = signal<Platform[]>([]);
  protected emailAccounts = signal<EmailAccountItem[]>([]);
  
  // Modals visibility
  protected showAddEditModal = signal<boolean>(false);
  protected showAssignmentsModal = signal<boolean>(false);
  protected isEditing = signal<boolean>(false);
  protected editingPlatformId: number | null = null;
  protected isUploadingLogo = signal<boolean>(false);

  // Regex helper states
  protected isTestingRegex = signal<boolean>(false);
  protected testMatched = signal<boolean>(false);
  protected testResultCode = signal<string | null>(null);
  protected testError = signal<string | null>(null);

  // Assignment states
  protected selectedEmail = signal<string | null>(null);
  protected tempAssignedPlatformIds = signal<number[]>([]);

  protected platformForm: FormGroup = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(2)]],
    logo: ['', [Validators.required, Validators.pattern(/https?:\/\/.+/)]],
    sender: ['', [Validators.required]],
    subject: [''],
    regex: [''],
    enable_heuristic: [false],
    grabbing_strategy: ['heuristic_first']
  });

  protected regexForm: FormGroup = this.fb.group({
    regex: ['', [Validators.required]],
    body: ['', [Validators.required]]
  });

  constructor() {
    this.layoutServices.setPageTitle('Platforms Configuration');
    this.loadPlatforms();
    this.loadAllEmails();
    this.initFormSync();
  }

  private initFormSync() {
    // Automatically sync main form's regex to tester form's regex
    this.platformForm.get('regex')?.valueChanges.subscribe(val => {
      if (this.regexForm.get('regex')?.value !== val) {
        this.regexForm.patchValue({ regex: val || '' }, { emitEvent: false });
      }
    });

    // Automatically sync tester form's regex to main form's regex
    this.regexForm.get('regex')?.valueChanges.subscribe(val => {
      if (this.platformForm.get('regex')?.value !== val) {
        this.platformForm.patchValue({ regex: val || '' }, { emitEvent: false });
      }
    });
  }

  private loadPlatforms() {
    this.platformService.getPlatforms().subscribe({
      next: response => {
        this.platforms.set(response.data ?? []);
      },
      error: () => {
        this.toastr.error('Failed to load platforms.');
      }
    });
  }

  private loadAllEmails() {
    forkJoin({
      gmail: this.gmailService.getAccounts(),
      outlook: this.outlookService.getAccounts(),
      imap: this.imapService.getAccounts()
    }).subscribe({
      next: result => {
        const items: EmailAccountItem[] = [];
        
        if (result.gmail.success && result.gmail.data) {
          result.gmail.data.forEach(acc => items.push({ email: acc.email, type: 'gmail' }));
        }
        if (result.outlook.success && result.outlook.data) {
          result.outlook.data.forEach(acc => items.push({ email: acc.email, type: 'outlook' }));
        }
        if (result.imap.success && result.imap.data) {
          result.imap.data.forEach(acc => items.push({ email: acc.email, type: 'imap' }));
        }

        this.emailAccounts.set(items);
      },
      error: () => {
        this.toastr.error('Failed to load email accounts for configuration.');
      }
    });
  }

  // --- CRUD Modals ---
  protected onClickAddPlatform() {
    this.isEditing.set(false);
    this.editingPlatformId = null;
    this.platformForm.reset({
      name: '',
      logo: '',
      sender: '',
      subject: '',
      regex: '',
      enable_heuristic: false,
      grabbing_strategy: 'heuristic_first'
    });
    this.resetRegexTester();
    this.showAddEditModal.set(true);
  }

  protected onClickEditPlatform(platform: Platform) {
    this.isEditing.set(true);
    this.editingPlatformId = platform.id ?? null;
    this.platformForm.patchValue({
      name: platform.name,
      logo: platform.logo,
      sender: platform.sender,
      subject: platform.subject,
      regex: platform.regex,
      enable_heuristic: platform.enable_heuristic ?? false,
      grabbing_strategy: platform.grabbing_strategy ?? 'heuristic_first'
    });
    this.resetRegexTester();
    // Pre-fill regex pattern into helper
    this.regexForm.patchValue({ regex: platform.regex });
    this.showAddEditModal.set(true);
  }

  protected onClickCloseModal() {
    this.showAddEditModal.set(false);
  }

  protected onLogoFileSelected(event: any) {
    const file = event.target.files[0];
    if (!file) return;

    this.isUploadingLogo.set(true);
    this.platformService.uploadLogo(file).subscribe({
      next: response => {
        this.isUploadingLogo.set(false);
        if (response.success && response.data?.logo_url) {
          this.platformForm.patchValue({ logo: response.data.logo_url });
          this.toastr.success('Platform logo uploaded successfully.');
        }
      },
      error: err => {
        this.isUploadingLogo.set(false);
        const errMsg = err.error?.message || 'Failed to upload platform logo.';
        this.toastr.error(errMsg);
      }
    });
  }

  protected onSubmitPlatform() {
    if (this.platformForm.invalid) {
      this.toastr.error('Please fix validation errors in the form.');
      return;
    }

    const platformData: Platform = this.platformForm.value;

    if (this.isEditing() && this.editingPlatformId != null) {
      this.platformService.updatePlatform(this.editingPlatformId, platformData).subscribe({
        next: response => {
          this.toastr.success(response.message || 'Platform updated successfully.');
          this.showAddEditModal.set(false);
          this.loadPlatforms();
        },
        error: err => {
          const errMsg = err.error?.message || 'Failed to update platform.';
          this.toastr.error(errMsg, 'Update Error');
        }
      });
    } else {
      this.platformService.createPlatform(platformData).subscribe({
        next: response => {
          this.toastr.success(response.message || 'Platform created successfully.');
          this.showAddEditModal.set(false);
          this.loadPlatforms();
        },
        error: err => {
          const errMsg = err.error?.message || 'Failed to create platform.';
          this.toastr.error(errMsg, 'Create Error');
        }
      });
    }
  }

  protected onClickDeletePlatform(platform: Platform) {
    if (platform.id == null) return;
    if (confirm(`Are you sure you want to delete the platform "${platform.name}"? This will delete all its mappings.`)) {
      this.platformService.deletePlatform(platform.id).subscribe({
        next: response => {
          this.toastr.success(response.message || 'Platform deleted.');
          this.loadPlatforms();
        },
        error: err => {
          this.toastr.error(err.error?.message || 'Failed to delete platform.');
        }
      });
    }
  }

  // --- Regex Tester Helper ---
  protected resetRegexTester() {
    this.regexForm.patchValue({ regex: '', body: '' }, { emitEvent: false });
    this.testMatched.set(false);
    this.testResultCode.set(null);
    this.testError.set(null);
  }

  protected syncFormRegex() {
    // Sync whatever is written in the main form's regex field into the tester regex field
    const mainRegex = this.platformForm.get('regex')?.value;
    this.regexForm.patchValue({ regex: mainRegex });
  }

  protected testRegex() {
    const regex = this.regexForm.get('regex')?.value;
    const body = this.regexForm.get('body')?.value;

    if (!regex || !body) {
      this.toastr.warning('Please input both regex and mock email body to test.');
      return;
    }

    this.isTestingRegex.set(true);
    this.testError.set(null);
    this.testMatched.set(false);
    this.testResultCode.set(null);

    this.platformService.testRegex(regex, body).subscribe({
      next: response => {
        this.isTestingRegex.set(false);
        if (response.success && response.data) {
          this.testMatched.set(response.data.matched);
          this.testResultCode.set(response.data.code);
          if (response.data.matched) {
            this.toastr.success(`Match found! Code: ${response.data.code}`);
          } else {
            this.toastr.warning('Regex did not match anything in the body.');
          }
        }
      },
      error: err => {
        this.isTestingRegex.set(false);
        const errMsg = err.error?.message || 'Failed to evaluate regex.';
        this.testError.set(errMsg);
        this.toastr.error(errMsg, 'Regex Evaluation Error');
      }
    });
  }

  protected applyTesterRegexToMain() {
    const helperRegex = this.regexForm.get('regex')?.value;
    this.platformForm.patchValue({ regex: helperRegex });
    this.toastr.info('Regex pattern applied to the main form.');
  }

  // --- Assignments Manager ---
  protected onClickManageAssignments(email: string) {
    this.selectedEmail.set(email);
    this.tempAssignedPlatformIds.set([]);
    
    // Fetch assignments for this email from backend
    this.platformService.getAssignments(email).subscribe({
      next: response => {
        this.tempAssignedPlatformIds.set(response.data ?? []);
        this.showAssignmentsModal.set(true);
      },
      error: () => {
        this.toastr.error('Failed to load assignments for ' + email);
      }
    });
  }

  protected isPlatformAssigned(platformId: number): boolean {
    return this.tempAssignedPlatformIds().includes(platformId);
  }

  protected togglePlatformAssignment(platformId: number) {
    const current = this.tempAssignedPlatformIds();
    if (current.includes(platformId)) {
      this.tempAssignedPlatformIds.set(current.filter(id => id !== platformId));
    } else {
      this.tempAssignedPlatformIds.set([...current, platformId]);
    }
  }

  protected onSaveAssignments() {
    const email = this.selectedEmail();
    if (!email) return;

    this.platformService.saveAssignments(email, this.tempAssignedPlatformIds()).subscribe({
      next: response => {
        this.toastr.success(response.message || 'Assignments updated.');
        this.showAssignmentsModal.set(false);
      },
      error: err => {
        this.toastr.error(err.error?.message || 'Failed to update assignments.');
      }
    });
  }

  protected onCloseAssignmentsModal() {
    this.showAssignmentsModal.set(false);
  }
}
