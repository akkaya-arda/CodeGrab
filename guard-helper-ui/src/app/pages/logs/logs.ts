import { Component, inject, signal, effect } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { StatisticsService } from '../../services/statistics-service';
import { ToastrService } from 'ngx-toastr';
import { LayoutServices } from '../../services/layout-services';

interface FetchLog {
  id: number;
  email: string;
  account_type: string;
  platform: string;
  status: string;
  code?: string;
  error_message?: string;
  created_at: string;
  grab_pattern?: string;
}

@Component({
  selector: 'app-logs',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './logs.html',
  styleUrl: './logs.css',
})
export class Logs {
  private statsService = inject(StatisticsService);
  private toastr = inject(ToastrService);
  private layoutServices = inject(LayoutServices);
  private route = inject(ActivatedRoute);

  protected logs = signal<FetchLog[]>([]);
  protected totalItems = signal<number>(0);
  protected currentPage = signal<number>(1);
  protected totalPages = signal<number>(1);
  protected lastPage = signal<number>(1);

  // Filters
  protected searchQuery = signal<string>('');
  protected filterStatus = signal<string>('');
  protected filterPlatform = signal<string>('');
  protected filterAccountType = signal<string>('');

  constructor() {
    this.layoutServices.setPageTitle('Activity Logs');
    
    const emailParam = this.route.snapshot.queryParamMap.get('email');
    if (emailParam) {
      this.searchQuery.set(emailParam);
    }

    // Run loading whenever page or filters change
    effect(() => {
      this.loadLogs(
        this.currentPage(),
        this.searchQuery(),
        this.filterStatus(),
        this.filterPlatform(),
        this.filterAccountType()
      );
    });
  }

  protected loadLogs(page: number, search: string, status: string, platform: string, accountType: string) {
    this.statsService.getLogs(page, search, status, platform, accountType).subscribe({
      next: response => {
        if (response.success && response.data) {
          this.logs.set(response.data.data ?? []);
          this.totalItems.set(response.data.total ?? 0);
          this.totalPages.set(response.data.last_page ?? 1);
          this.lastPage.set(response.data.last_page ?? 1);
        }
      },
      error: error => {
        this.toastr.error('Failed to load activity logs.');
      }
    });
  }

  protected onSearchChange() {
    this.currentPage.set(1); // Reset page on filter change
  }

  protected onFilterChange() {
    this.currentPage.set(1);
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
    if (page >= 1 && page <= this.totalPages()) {
      this.currentPage.set(page);
    }
  }

  protected getPageNumbers(): number[] {
    const pages: number[] = [];
    const maxVisible = 5;
    let start = Math.max(1, this.currentPage() - 2);
    let end = Math.min(this.totalPages(), start + maxVisible - 1);
    
    if (end - start + 1 < maxVisible) {
      start = Math.max(1, end - maxVisible + 1);
    }

    for (let i = start; i <= end; i++) {
      pages.push(i);
    }
    return pages;
  }
}
