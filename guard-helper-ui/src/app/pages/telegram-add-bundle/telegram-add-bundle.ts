import { Component, inject, OnInit, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { HttpClient } from '@angular/common/http';
import { ApiConnectionSettings } from '../../settings/api-connection-settings';

interface Platform {
  id: number;
  name: string;
}

@Component({
  selector: 'app-telegram-add-bundle',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  templateUrl: './telegram-add-bundle.html',
  styleUrl: './telegram-add-bundle.css'
})
export class TelegramAddBundle implements OnInit {
  private fb = inject(FormBuilder);
  private http = inject(HttpClient);
  private settings = inject(ApiConnectionSettings);

  protected platforms = signal<Platform[]>([]);
  protected isSubmitting = signal<boolean>(false);
  protected isSuccess = signal<boolean>(false);
  protected errorMessage = signal<string | null>(null);
  protected initData = signal<string>('');

  protected bundleForm: FormGroup = this.fb.nonNullable.group({
    name: ['', [Validators.required]],
    email: ['', [Validators.required, Validators.email]],
    platform: ['', [Validators.required]],
    password: ['', [Validators.required]],
    login_username: ['', []]
  });

  ngOnInit(): void {
    const tg = (window as any).Telegram?.WebApp;
    if (tg) {
      tg.ready();
      tg.expand();
      this.initData.set(tg.initData || '');
    }

    this.loadPlatforms();
  }

  private loadPlatforms(): void {
    const url = `${this.settings.baseUrl}/telegram-api/platforms`;
    const payload = { init_data: this.initData() };

    this.http.post<any>(url, payload).subscribe({
      next: (res) => {
        if (res.success) {
          this.platforms.set(res.platforms || []);
        } else {
          this.errorMessage.set(res.message || 'Failed to load platforms.');
        }
      },
      error: (err) => {
        this.errorMessage.set(err.error?.message || 'Unauthorized or server error occurred.');
      }
    });
  }

  protected onSubmit(): void {
    if (this.bundleForm.invalid) {
      return;
    }

    this.isSubmitting.set(true);
    this.errorMessage.set(null);

    const url = `${this.settings.baseUrl}/telegram-api/add-bundle`;
    const payload = {
      ...this.bundleForm.value,
      init_data: this.initData()
    };

    this.http.post<any>(url, payload).subscribe({
      next: (res) => {
        this.isSubmitting.set(false);
        if (res.success) {
          this.isSuccess.set(true);
          setTimeout(() => {
            const tg = (window as any).Telegram?.WebApp;
            if (tg) {
              tg.close();
            }
          }, 2000);
        } else {
          this.errorMessage.set(res.message || 'Failed to save account bundle.');
        }
      },
      error: (err) => {
        this.isSubmitting.set(false);
        this.errorMessage.set(err.error?.message || 'An error occurred during submission.');
      }
    });
  }
}
