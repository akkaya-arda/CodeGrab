import { Routes } from '@angular/router';
import { Login } from './pages/login/login';
import { PanelLayout } from './shared/panel-layout/panel-layout';
import { Dashboard } from './pages/dashboard/dashboard';
import { OutlookEmails } from './pages/outlook/outlook-emails/outlook-emails';
import { GmailEmails } from './pages/gmail/gmail-emails/gmail-emails';
import { ImapEmails } from './pages/imap/imap-emails/imap-emails';
import { Logs } from './pages/logs/logs';
import { PublicGrabCode } from './pages/public-grab-code/public-grab-code';
import { Notifications } from './pages/notifications/notifications';
import { Feedbacks } from './pages/feedbacks/feedbacks';
import { Platforms } from './pages/platforms/platforms';
import { Settings } from './pages/settings/settings';
import { AccessGrants } from './pages/access-grants/access-grants';
import { Account } from './pages/account/account';
import { AccountBundles } from './pages/account-bundles/account-bundles';
import { SupportChats } from './pages/support-chats/support-chats';
import { TelegramAddBundle } from './pages/telegram-add-bundle/telegram-add-bundle';

import { authGuard } from './guards/auth.guard';

export const routes: Routes = [
    {
        path: 'login',
        component: Login
    },
    {
        path: 'grab-code',
        component: PublicGrabCode
    },
    {
        path: 'telegram/add-bundle',
        component: TelegramAddBundle
    },
    {
        path: '',
        redirectTo: 'grab-code',
        pathMatch: 'full'
    },
    {
        path: '',
        component: PanelLayout,
        canActivate: [authGuard],
        children: [
            {
                path: 'dashboard',
                component: Dashboard
            },
            {
                path: 'access-grants',
                component: AccessGrants
            },
            {
                path: 'account-bundles',
                component: AccountBundles
            },
            {
                path: 'support-chats',
                component: SupportChats
            },
            {
                path: 'outlook',
                children: [
                    {
                        path: 'emails',
                        component: OutlookEmails
                    }
                ]
            },
            {
                path: 'gmail',
                children: [
                    {
                        path: 'emails',
                        component: GmailEmails
                    }
                ]
            },
            {
                path: 'emails/imap',
                component: ImapEmails
            },
            {
                path: 'logs',
                component: Logs
            },
            {
                path: 'notifications',
                component: Notifications
            },
            {
                path: 'feedbacks',
                component: Feedbacks
            },
            {
                path: 'platforms',
                component: Platforms
            },
            {
                path: 'settings',
                component: Settings
            },
            {
                path: 'settings/account',
                component: Account
            },
            {
                path: '**',
                redirectTo: 'dashboard'
            }
        ]
    }
];
