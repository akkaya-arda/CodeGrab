import { Component, inject, signal, OnInit } from '@angular/core';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators, AbstractControl } from '@angular/forms';
import { AuthenticationService } from '../../services/authentication-service';
import { LayoutServices } from '../../services/layout-services';
import { ToastrService } from 'ngx-toastr';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-account',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './account.html',
  styleUrl: './account.css'
})
export class Account implements OnInit {
  private fb = inject(FormBuilder);
  private authService = inject(AuthenticationService);
  private layoutServices = inject(LayoutServices);
  private toastr = inject(ToastrService);

  protected isSaving = signal<boolean>(false);
  protected currentUser = this.authService.currentUser;

  protected accountForm: FormGroup = this.fb.group({
    name: ['', [Validators.required, Validators.minLength(2)]],
    email: ['', [Validators.required, Validators.email]],
    current_password: ['', [Validators.required]],
    new_password: ['', [Validators.minLength(8)]],
    new_password_confirmation: ['']
  }, { validators: this.passwordMatchValidator });

  constructor() {
    this.layoutServices.setPageTitle('Account Settings');
  }

  ngOnInit() {
    const user = this.currentUser();
    if (user) {
      this.accountForm.patchValue({
        name: user.name,
        email: user.email
      });
    }

    // Ensure we fetch freshest data
    this.authService.loadCurrentUser().subscribe({
      next: (response: any) => {
        const u = response?.data || response;
        if (u) {
          this.accountForm.patchValue({
            name: u.name,
            email: u.email
          });
        }
      }
    });
  }

  private passwordMatchValidator(control: AbstractControl): { [key: string]: boolean } | null {
    const newPass = control.get('new_password')?.value;
    const confirmPass = control.get('new_password_confirmation')?.value;
    
    if (newPass && newPass !== confirmPass) {
      control.get('new_password_confirmation')?.setErrors({ mismatch: true });
      return { mismatch: true };
    }
    return null;
  }

  onSubmit() {
    if (this.accountForm.invalid) {
      this.toastr.error('Please fix validation errors in the form.');
      return;
    }

    this.isSaving.set(true);
    const payload = { ...this.accountForm.value };
    
    // Clean payload: don't submit confirmation
    delete payload.new_password_confirmation;
    if (!payload.new_password) {
      delete payload.new_password;
    }

    this.authService.updateProfile(this.accountForm.value).subscribe({
      next: (response) => {
        this.isSaving.set(false);
        if (response.success) {
          this.toastr.success(response.message || 'Profile updated successfully.');
          this.accountForm.patchValue({
            current_password: '',
            new_password: '',
            new_password_confirmation: ''
          });
          this.accountForm.markAsPristine();
          this.accountForm.markAsUntouched();
        } else {
          this.toastr.error(response.message || 'Failed to update profile.');
        }
      },
      error: (err) => {
        this.isSaving.set(false);
        const errMsg = err.error?.message || 'Failed to update account settings.';
        this.toastr.error(errMsg, 'Error');
      }
    });
  }
}
