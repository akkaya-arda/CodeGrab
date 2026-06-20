import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FeedbackService } from '../../services/feedback-service';
import { LayoutServices } from '../../services/layout-services';
import { ToastrService } from 'ngx-toastr';

interface UserFeedback {
  id: number;
  email: string;
  platform: string;
  is_working: boolean;
  comment?: string;
  log_id?: number;
  created_at: string;
  fetch_log?: {
    id: number;
    email: string;
    account_type: string;
    platform: string;
    status: string;
    code?: string;
    error_message?: string;
    created_at: string;
  };
}

@Component({
  selector: 'app-feedbacks',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './feedbacks.html',
  styleUrl: './feedbacks.css',
})
export class Feedbacks {
  private feedbackService = inject(FeedbackService);
  private layoutServices = inject(LayoutServices);
  private toastr = inject(ToastrService);

  protected feedbacks = signal<UserFeedback[]>([]);
  protected currentPage = signal<number>(1);
  protected totalPages = signal<number>(1);
  protected totalItems = signal<number>(0);

  constructor() {
    this.layoutServices.setPageTitle('User Feedbacks');
    this.loadFeedbacks();
  }

  protected loadFeedbacks() {
    this.feedbackService.getFeedbacks(this.currentPage()).subscribe({
      next: response => {
        if (response.success && response.data) {
          this.feedbacks.set(response.data.data ?? []);
          this.totalPages.set(response.data.last_page ?? 1);
          this.totalItems.set(response.data.total ?? 0);
        }
      },
      error: () => {
        this.toastr.error('Failed to load user feedbacks.');
      }
    });
  }

  protected nextPage() {
    if (this.currentPage() < this.totalPages()) {
      this.currentPage.update(p => p + 1);
      this.loadFeedbacks();
    }
  }

  protected prevPage() {
    if (this.currentPage() > 1) {
      this.currentPage.update(p => p - 1);
      this.loadFeedbacks();
    }
  }
}
