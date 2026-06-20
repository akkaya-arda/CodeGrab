import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule } from '@angular/router';
import { StatisticsService } from '../../services/statistics-service';
import { ToastrService } from 'ngx-toastr';
import { LayoutServices } from '../../services/layout-services';

interface DashboardStats {
  accounts: {
    gmail: { total: number; active: number };
    outlook: { total: number; active: number };
    imap: { total: number; active: number };
    all: { total: number; active: number };
  };
  fetches: {
    total: number;
    success: number;
    failed: number;
    success_rate: number;
  };
  distributions: {
    platforms: Array<{ platform: string; total: number }>;
    providers: Array<{ account_type: string; total: number }>;
    daily_trend: Array<{ date: string; total: number }>;
  };
  recent_logs: Array<{
    id: number;
    email: string;
    account_type: string;
    platform: string;
    status: string;
    code?: string;
    error_message?: string;
    created_at: string;
  }>;
}

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterModule],
  templateUrl: './dashboard.html',
  styleUrl: './dashboard.css',
})
export class Dashboard {
  private statsService = inject(StatisticsService);
  private toastr = inject(ToastrService);
  private layoutServices = inject(LayoutServices);

  protected stats = signal<DashboardStats | null>(null);
  protected isLoading = signal<boolean>(true);

  constructor() {
    this.layoutServices.setPageTitle('Dashboard');
    this.loadDashboardData();
  }

  protected loadDashboardData() {
    this.isLoading.set(true);
    this.statsService.getSummary().subscribe({
      next: response => {
        this.isLoading.set(false);
        if (response.success && response.data) {
          this.stats.set(response.data);
        }
      },
      error: error => {
        this.isLoading.set(false);
        this.toastr.error('Failed to load dashboard statistics.');
      }
    });
  }
}
