import { Component, inject, signal } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NotificationService } from '../../services/notification-service';
import { LayoutServices } from '../../services/layout-services';
import { ToastrService } from 'ngx-toastr';

interface SystemNotification {
  id: number;
  type: string;
  title: string;
  message: string;
  is_read: boolean;
  created_at: string;
}

@Component({
  selector: 'app-notifications',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './notifications.html',
  styleUrl: './notifications.css',
})
export class Notifications {
  private notificationService = inject(NotificationService);
  private layoutServices = inject(LayoutServices);
  private toastr = inject(ToastrService);

  protected notifications = signal<SystemNotification[]>([]);
  protected currentPage = signal<number>(1);
  protected totalPages = signal<number>(1);
  protected totalItems = signal<number>(0);

  constructor() {
    this.layoutServices.setPageTitle('System Notifications');
    this.loadNotifications();
  }

  protected loadNotifications() {
    this.notificationService.getNotifications(this.currentPage()).subscribe({
      next: response => {
        if (response.success && response.data) {
          this.notifications.set(response.data.data ?? []);
          this.totalPages.set(response.data.last_page ?? 1);
          this.totalItems.set(response.data.total ?? 0);
        }
      },
      error: () => {
        this.toastr.error('Failed to load system notifications.');
      }
    });
  }

  protected markAsRead(id?: number) {
    this.notificationService.markAsRead(id).subscribe({
      next: response => {
        if (id) {
          this.notifications.update(list => list.map(n => n.id === id ? { ...n, is_read: true } : n));
        } else {
          this.notifications.update(list => list.map(n => ({ ...n, is_read: true })));
          this.toastr.success('All notifications marked as read.');
        }
        
        // Notify sidebar or other systems about count updates if needed
        window.dispatchEvent(new Event('notifications-updated'));
      },
      error: () => {
        this.toastr.error('Failed to update notification status.');
      }
    });
  }

  protected deleteNotification(id: number) {
    this.notificationService.deleteNotification(id).subscribe({
      next: response => {
        this.notifications.update(list => list.filter(n => n.id !== id));
        this.totalItems.update(c => c - 1);
        this.toastr.success('Notification removed.');
        window.dispatchEvent(new Event('notifications-updated'));
      },
      error: () => {
        this.toastr.error('Failed to delete notification.');
      }
    });
  }

  protected nextPage() {
    if (this.currentPage() < this.totalPages()) {
      this.currentPage.update(p => p + 1);
      this.loadNotifications();
    }
  }

  protected prevPage() {
    if (this.currentPage() > 1) {
      this.currentPage.update(p => p - 1);
      this.loadNotifications();
    }
  }
}
