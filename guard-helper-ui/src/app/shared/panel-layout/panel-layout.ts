import { Component, signal, inject, HostListener, OnInit, OnDestroy } from '@angular/core';
import { RouterOutlet, RouterLinkWithHref, RouterLink, Router, NavigationEnd } from "@angular/router";
import { filter, Subscription } from 'rxjs';
import { LayoutServices } from '../../services/layout-services';
import { NotificationService } from '../../services/notification-service';
import { AuthenticationService } from '../../services/authentication-service';
import { SettingsService } from '../../services/settings-service';
import { ThemeService } from '../../services/theme-service';
import { CommonModule } from '@angular/common';

interface NavItem {
  label: string;
  routerLink: string;
  icon: string;
  children?: NavItem[];
  isHidden?: boolean;
  badgeCount?: () => number;
}

@Component({
  selector: 'app-panel-layout',
  imports: [RouterOutlet, RouterLinkWithHref, RouterLink, CommonModule],
  templateUrl: './panel-layout.html',
  styleUrl: './panel-layout.css',
})
export class PanelLayout implements OnInit, OnDestroy {
  private notificationService = inject(NotificationService);
  private authenticationService = inject(AuthenticationService);
  private settingsService = inject(SettingsService);
  protected themeService = inject(ThemeService);
  private router = inject(Router);
  
  protected isSidebarOpen = signal<boolean>(false);
  protected unreadErrorCount = signal<number>(0);
  protected isDropdownOpen = signal<boolean>(false);
  protected currentUser = this.authenticationService.currentUser;

  private routerSubscription: Subscription | null = null;

  protected toggleDropdown() {
    this.isDropdownOpen.update(v => !v);
  }

  protected logout() {
    localStorage.removeItem('app-token');
    this.isDropdownOpen.set(false);
    this.router.navigate(['/login']);
  }
  
  protected navItems: NavItem[] = [
    {
      label: 'Dashboard',
      routerLink: '/dashboard',
      icon: 'fa fa-dashboard'
    },
    {
      label: 'Google',
      routerLink: '#',
      icon: 'fa-brands fa-google',
      isHidden: false,
      children: [
        {
          label: 'Emails',
          routerLink: '/gmail/emails',
          icon: 'fa-solid fa-envelope',
        }
      ]
    },
    {
      label: 'Outlook',
      routerLink: '#',
      icon: 'fa-brands fa-windows',
      isHidden: false,
      children: [
        {
          label: 'Emails',
          routerLink: '/outlook/emails',
          icon: 'fa-solid fa-envelope',
        }
      ]
    },
    {
      label: 'IMAP',
      routerLink: '#',
      icon: 'fa-solid fa-server',
      isHidden: false,
      children: [
        {
          label: 'Emails',
          routerLink: '/emails/imap',
          icon: 'fa-solid fa-envelope',
        }
      ]
    },
    {
      label: 'Activity Logs',
      routerLink: '/logs',
      icon: 'fa-solid fa-file-lines'
    },
    {
      label: 'User Feedbacks',
      routerLink: '/feedbacks',
      icon: 'fa-solid fa-comments'
    },
    {
      label: 'Platforms',
      routerLink: '/platforms',
      icon: 'fa-solid fa-gamepad'
    },
    {
      label: 'System Errors',
      routerLink: '/notifications',
      icon: 'fa-solid fa-triangle-exclamation',
      badgeCount: () => this.unreadErrorCount()
    },
    {
      label: 'Access Grants',
      routerLink: '/access-grants',
      icon: 'fa-solid fa-key'
    },
    {
      label: 'Account Bundles',
      routerLink: '/account-bundles',
      icon: 'fa-solid fa-boxes-stacked'
    },
    {
      label: 'Support Chats',
      routerLink: '/support-chats',
      icon: 'fa-solid fa-comments'
    },
    {
      label: 'Settings',
      routerLink: '/settings',
      icon: 'fa-solid fa-gear'
    },
    {
      label: 'Public Access Portal',
      routerLink: '/grab-code',
      icon: 'fa-solid fa-square-arrow-up-right'
    }
  ];

  @HostListener('window:notifications-updated')
  onNotificationsUpdated() {
    this.fetchUnreadCount();
  }

  constructor(protected layoutServices: LayoutServices) {}

  private notificationInterval: any = null;

  ngOnInit() {
    this.authenticationService.loadCurrentUser().subscribe();
    this.fetchUnreadCount();
    this.loadBranding();
    this.expandActiveParent(this.router.url);

    // Fetch unread count on every route change navigation ONLY if light mode is disabled
    this.routerSubscription = this.router.events.pipe(
      filter(event => event instanceof NavigationEnd)
    ).subscribe((event: any) => {
      if (!this.themeService.lightMode()) {
        this.fetchUnreadCount();
      }
      this.expandActiveParent(event.urlAfterRedirects || event.url);
    });
  }

  private expandActiveParent(url: string) {
    this.navItems.forEach(item => {
      if (item.children) {
        const hasActiveChild = item.children.some(child => url.startsWith(child.routerLink));
        if (hasActiveChild) {
          item.isHidden = false;
        }
      }
    });
  }

  protected loadBranding() {
    this.settingsService.getSettings().subscribe({
      next: response => {
        if (response.success && response.data) {
          const data = response.data;
          this.themeService.applyBranding(
            data.system_name,
            data.system_logo,
            data.theme_primary_color,
            data.theme_accent_color,
            data.system_slogan_title,
            data.system_slogan_subtitle,
            data.logo_enabled === '1',
            data.theme_font_family,
            data.copyright_text,
            data.hide_access_restricted_info === '1',
            data.light_mode === '1',
            data.public_portal_title
          );
          this.setupNotificationPolling();
        }
      }
    });
  }

  private setupNotificationPolling() {
    if (this.notificationInterval) {
      clearInterval(this.notificationInterval);
    }
    const intervalTime = this.themeService.lightMode() ? 60000 : 20000;
    this.notificationInterval = setInterval(() => {
      this.fetchUnreadCount();
    }, intervalTime);
  }

  ngOnDestroy() {
    if (this.routerSubscription) {
      this.routerSubscription.unsubscribe();
    }
    if (this.notificationInterval) {
      clearInterval(this.notificationInterval);
    }
  }

  private fetchUnreadCount() {
    if (!localStorage.getItem('app-token')) return;
    this.notificationService.getUnreadCount().subscribe({
      next: response => {
        if (response.success && response.data) {
          this.unreadErrorCount.set(response.data.count);
        }
      },
      error: () => {
        console.warn('[PanelLayout] Failed to retrieve notification count.');
      }
    });
  }
}
