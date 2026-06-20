import { Component, inject, signal, computed } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators, FormsModule } from '@angular/forms';
import { ImapService } from '../../../services/imap-service';
import { ImapAccount } from '../../../models/imap-account';
import { ToastrService } from 'ngx-toastr';
import { CommonModule } from '@angular/common';
import { LayoutServices } from '../../../services/layout-services';
import { Router } from '@angular/router';

@Component({
  selector: 'app-imap-emails',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, FormsModule],
  templateUrl: './imap-emails.html',
  styleUrl: './imap-emails.css',
})
export class ImapEmails {
  private imapService = inject(ImapService);
  private toastr = inject(ToastrService);
  private fb = inject(FormBuilder);
  private layoutServices = inject(LayoutServices);
  private router = inject(Router);

  protected accounts = signal<ImapAccount[]>([]);
  protected searchQuery = signal<string>('');
  protected filteredAccounts = computed(() => {
    const query = this.searchQuery().toLowerCase().trim();
    const list = this.accounts();
    if (!query) return list;
    return list.filter(account => account.email.toLowerCase().includes(query));
  });

  protected onCreateToken(email: string) {
    this.router.navigate(['/access-grants'], { queryParams: { email } });
  }
  protected showAddModal = signal<boolean>(false);
  protected isTestingConnection = signal<boolean>(false);
  protected testResult = signal<{ success: boolean; message: string } | null>(null);

  protected addForm: FormGroup = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    host: ['', [Validators.required]],
    port: [993, [Validators.required, Validators.min(1), Validators.max(65535)]],
    encryption: ['ssl', [Validators.required]],
    password: ['', [Validators.required, Validators.minLength(4)]]
  });

  constructor() {
    this.layoutServices.setPageTitle('IMAP Configuration');
    this.loadAccounts();
    
    // Watch encryption changes to update ports automatically
    this.addForm.get('encryption')?.valueChanges.subscribe(val => {
      const portCtrl = this.addForm.get('port');
      if (val === 'ssl') {
        portCtrl?.setValue(993);
      } else if (val === 'tls') {
        portCtrl?.setValue(143);
      } else {
        portCtrl?.setValue(143);
      }
    });
  }

  private loadAccounts() {
    this.imapService.getAccounts().subscribe({
      next: response => {
        this.accounts.set(response.data ?? []);
      },
      error: error => {
        this.toastr.error('Failed to load IMAP accounts.');
      }
    });
  }

  protected onClickOpenModal() {
    this.addForm.reset({
      email: '',
      host: '',
      port: 993,
      encryption: 'ssl',
      password: ''
    });
    this.testResult.set(null);
    this.showAddModal.set(true);
  }

  protected onClickCloseModal() {
    this.showAddModal.set(false);
  }

  protected testConnection() {
    if (this.addForm.invalid) {
      this.toastr.warning('Please fill in all details before testing connection.');
      return;
    }

    this.isTestingConnection.set(true);
    this.testResult.set(null);

    const accountData = this.addForm.value;
    
    // We call standard addAccount connection test or build testConnection on service
    this.imapService.addAccount(accountData).subscribe({
      next: response => {
        this.isTestingConnection.set(false);
        this.testResult.set({ success: true, message: 'Connection successful!' });
        this.toastr.success('Verification successful!');
      },
      error: err => {
        this.isTestingConnection.set(false);
        const errMsg = err.error?.message || err.message || 'Connection failed';
        this.testResult.set({ success: false, message: errMsg });
        this.toastr.error(errMsg, 'Connection Failed');
      }
    });
  }

  protected onSubmit() {
    if (this.addForm.invalid) {
      this.toastr.error('Please fix validation errors.');
      return;
    }

    const accountData = this.addForm.value;

    this.imapService.addAccount(accountData).subscribe({
      next: response => {
        this.toastr.success(response.message || 'IMAP Account added successfully.');
        this.showAddModal.set(false);
        this.loadAccounts();
      },
      error: err => {
        const errMsg = err.error?.message || err.message || 'Failed to save account';
        this.toastr.error(errMsg, 'Error saving account');
      }
    });
  }

  protected onClickEnable(account: ImapAccount) {
    this.imapService.enableAccount(account.email).subscribe({
      next: response => {
        this.accounts.update(accounts => accounts.map(a => a.id === account.id ? { ...a, isActive: true } : a));
        this.toastr.success(response.message);
      },
      error: error => {
        this.toastr.error(error.message || 'Failed to enable account');
      }
    });
  }

  protected onClickDisable(account: ImapAccount) {
    this.imapService.disableAccount(account.email).subscribe({
      next: response => {
        this.accounts.update(accounts => accounts.map(a => a.id === account.id ? { ...a, isActive: false } : a));
        this.toastr.success(response.message);
      },
      error: error => {
        this.toastr.error(error.message || 'Failed to disable account');
      }
    });
  }

  protected onClickDelete(account: ImapAccount) {
    if (confirm(`Are you sure you want to delete the IMAP account for ${account.email}?`)) {
      this.imapService.deleteAccount(account.email).subscribe({
        next: response => {
          this.accounts.update(accounts => accounts.filter(a => a.id !== account.id));
          this.toastr.success(response.message);
        },
        error: error => {
          this.toastr.error(error.message || 'Failed to delete account');
        }
      });
    }
  }

  protected onClickTestConnection(account: ImapAccount) {
    this.toastr.info(`Testing connection to ${account.email}...`);
    this.imapService.testConnection(account.email).subscribe({
      next: response => {
        this.toastr.success(response.message || 'Connection successful!');
      },
      error: err => {
        const errMsg = err.error?.message || err.message || 'Connection failed';
        this.toastr.error(errMsg, 'Connection Error');
      }
    });
  }

  protected onClickLogs(email: string) {
    this.router.navigate(['/logs'], { queryParams: { email } });
  }
}
