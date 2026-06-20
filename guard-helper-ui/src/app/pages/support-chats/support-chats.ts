import { Component, inject, signal, OnInit, OnDestroy, ViewChild, ElementRef, AfterViewChecked } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SupportService } from '../../services/support-service';
import { ToastrService } from 'ngx-toastr';
import { LayoutServices } from '../../services/layout-services';

interface SupportMessage {
  id: number;
  support_thread_id: number;
  sender: 'user' | 'admin';
  message: string;
  created_at: string;
}

interface SupportThread {
  id: number;
  access_grant_id: number | null;
  token: string;
  user_email: string | null;
  platform: string | null;
  status: 'open' | 'resolved';
  created_at: string;
  updated_at: string;
  messages?: SupportMessage[];
}

@Component({
  selector: 'app-support-chats',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './support-chats.html',
  styleUrl: './support-chats.css',
})
export class SupportChats implements OnInit, OnDestroy, AfterViewChecked {
  private supportService = inject(SupportService);
  private toastr = inject(ToastrService);
  private layoutServices = inject(LayoutServices);

  @ViewChild('scrollContainer') private scrollContainer!: ElementRef;

  protected threads = signal<SupportThread[]>([]);
  protected activeThread = signal<SupportThread | null>(null);
  protected newReply = signal<string>('');
  protected isReplying = signal<boolean>(false);
  protected isLoadingThreads = signal<boolean>(false);
  protected isLoadingMessages = signal<boolean>(false);

  private threadPollInterval: any = null;
  private listPollInterval: any = null;
  private shouldScrollToBottom = false;

  constructor() {
    this.layoutServices.setPageTitle('Support Chats');
  }

  ngOnInit(): void {
    this.loadThreads();
    
    // Periodically update the list of threads every 10 seconds
    this.listPollInterval = setInterval(() => {
      this.loadThreads(true);
    }, 10000);
  }

  ngOnDestroy(): void {
    this.clearThreadPolling();
    if (this.listPollInterval) {
      clearInterval(this.listPollInterval);
    }
  }

  ngAfterViewChecked(): void {
    if (this.shouldScrollToBottom) {
      this.scrollToBottomDirectly();
      this.shouldScrollToBottom = false;
    }
  }

  protected loadThreads(silent = false) {
    if (!silent) this.isLoadingThreads.set(true);
    this.supportService.getAdminThreads().subscribe({
      next: response => {
        this.isLoadingThreads.set(false);
        if (response.success && response.data) {
          // Sort threads: Open threads first, then by updated_at descending
          const sorted = response.data.sort((a, b) => {
            if (a.status === 'open' && b.status !== 'open') return -1;
            if (a.status !== 'open' && b.status === 'open') return 1;
            return new Date(b.updated_at).getTime() - new Date(a.updated_at).getTime();
          });
          this.threads.set(sorted);

          // Refresh active thread details if selected
          const active = this.activeThread();
          if (active) {
            const updatedActive = sorted.find(t => t.id === active.id);
            if (updatedActive && updatedActive.status !== active.status) {
              active.status = updatedActive.status;
              this.activeThread.set({ ...active });
            }
          }
        }
      },
      error: () => {
        this.isLoadingThreads.set(false);
        if (!silent) this.toastr.error('Failed to load support threads.');
      }
    });
  }

  protected selectThread(thread: SupportThread) {
    this.clearThreadPolling();
    this.activeThread.set(thread);
    this.isLoadingMessages.set(true);
    this.newReply.set('');

    this.loadActiveThreadMessages(false);

    // Setup polling for the selected thread messages every 4 seconds
    this.threadPollInterval = setInterval(() => {
      this.loadActiveThreadMessages(true);
    }, 4000);
  }

  private loadActiveThreadMessages(silent = false) {
    const active = this.activeThread();
    if (!active) return;

    this.supportService.getAdminThread(active.id).subscribe({
      next: response => {
        this.isLoadingMessages.set(false);
        if (response.success && response.data) {
          const fetchedMessages = response.data.messages || [];
          const currentMessageCount = active.messages?.length || 0;
          
          active.messages = fetchedMessages;
          active.status = response.data.status;
          this.activeThread.set({ ...active });

          // Scroll to bottom if we received new messages or first load
          if (!silent || fetchedMessages.length > currentMessageCount) {
            this.shouldScrollToBottom = true;
          }
        }
      },
      error: () => {
        this.isLoadingMessages.set(false);
      }
    });
  }

  protected sendReply() {
    const replyText = this.newReply().trim();
    const active = this.activeThread();
    if (!replyText || !active || this.isReplying()) return;

    this.isReplying.set(true);
    this.supportService.replyAdminThread(active.id, replyText).subscribe({
      next: response => {
        this.isReplying.set(false);
        this.newReply.set('');
        if (response.success && response.data) {
          active.messages = response.data.messages || [];
          active.status = response.data.status;
          this.activeThread.set({ ...active });
          this.shouldScrollToBottom = true;
          this.loadThreads(true); // Silent reload list to update timestamps
        }
      },
      error: () => {
        this.isReplying.set(false);
        this.toastr.error('Failed to send reply.');
      }
    });
  }

  protected resolveThread() {
    const active = this.activeThread();
    if (!active) return;

    this.supportService.closeAdminThread(active.id).subscribe({
      next: response => {
        if (response.success) {
          this.toastr.success('Support chat resolved successfully.');
          active.status = 'resolved';
          this.activeThread.set({ ...active });
          this.loadThreads(true);
        }
      },
      error: () => {
        this.toastr.error('Failed to resolve support chat.');
      }
    });
  }

  private clearThreadPolling() {
    if (this.threadPollInterval) {
      clearInterval(this.threadPollInterval);
      this.threadPollInterval = null;
    }
  }

  private scrollToBottomDirectly() {
    try {
      if (this.scrollContainer) {
        const element = this.scrollContainer.nativeElement;
        element.scrollTop = element.scrollHeight;
      }
    } catch (err) {
      console.warn('Could not scroll chat container to bottom', err);
    }
  }
}
