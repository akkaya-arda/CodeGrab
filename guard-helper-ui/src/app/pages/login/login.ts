import { Component, inject, Inject, signal, Signal } from '@angular/core';
import { FontAwesomeModule } from '@fortawesome/angular-fontawesome';
import { LoginModel } from '../../models/login-model';
import { AuthenticationService } from '../../services/authentication-service';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { ToastrService } from 'ngx-toastr';
import { CommonModule } from '@angular/common';

@Component({
  selector: 'app-login',
  imports: [FontAwesomeModule, ReactiveFormsModule, RouterModule, CommonModule],
  templateUrl: './login.html',
  styleUrl: './login.css',
})
export class Login {
  protected loginModel: LoginModel;
  protected fb: FormBuilder = inject(FormBuilder);
  private router: Router = inject(Router);
  private toastr: ToastrService = inject(ToastrService);
  protected loginForm: FormGroup = this.fb.nonNullable.group({
    email: ['', [Validators.email, Validators.required]],
    password: ['', [Validators.minLength(8), Validators.required]],
    rememberMe: [false, []]
  });
  protected loginFailed = signal<boolean>(false);
  protected isResetMode = signal<boolean>(false);
  protected isResetting = signal<boolean>(false);
  protected resetEmailControl = this.fb.control('', [Validators.required, Validators.email]);

  constructor(private authenticationService: AuthenticationService) { this.loginModel = { email: '', password: '', rememberMe: false } as LoginModel; }

  protected toggleResetMode(mode: boolean) {
    this.isResetMode.set(mode);
    this.loginFailed.set(false);
    this.resetEmailControl.reset();
  }

  protected resetPassword() {
    if (this.resetEmailControl.invalid) {
      this.toastr.warning('Please enter a valid administrator email address.');
      return;
    }

    this.isResetting.set(true);
    const emailVal = this.resetEmailControl.value as string;

    this.authenticationService.resetPassword(emailVal).subscribe({
      next: (response) => {
        this.isResetting.set(false);
        if (response.success) {
          this.toastr.success(response.message || 'Password reset successful!');
          this.toggleResetMode(false);
        } else {
          this.toastr.error(response.message || 'Verification failed.');
        }
      },
      error: (err) => {
        this.isResetting.set(false);
        const errMsg = err.error?.message || 'Password reset request failed.';
        this.toastr.error(errMsg, 'Reset Failed');
      }
    });
  }

  public login() {
    this.loginModel.email = this.loginForm.get('email')?.value as string;
    this.loginModel.password = this.loginForm.get('password')?.value as string;
    this.loginModel.rememberMe = this.loginForm.get('rememberMe')?.value as boolean;
    this.authenticationService.login(this.loginModel).subscribe({
      next: response => {
        if (response.success) {
          this.router.navigate(['/dashboard']);
        }
      },
      error: error => {
        this.toastr.error('Login failed. Please try again.', 'Error');
        this.loginFailed.set(true);
      }
    });
  }
}
