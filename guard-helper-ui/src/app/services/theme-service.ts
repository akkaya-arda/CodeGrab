import { Injectable, inject, signal } from '@angular/core';
import { ApiConnectionSettings } from '../settings/api-connection-settings';

@Injectable({
  providedIn: 'root'
})
export class ThemeService {
  private apiSettings = inject(ApiConnectionSettings);

  public systemName = signal<string>('');
  public systemLogo = signal<string>('');
  public logoEnabled = signal<boolean>(true);
  public primaryColor = signal<string>('#4f46e5');
  public accentColor = signal<string>('#6366f1');
  public systemSloganTitle = signal<string>('Access Portal');
  public systemSloganSubtitle = signal<string>('Retrieve your 2FA codes easily.');
  public systemFontFamily = signal<string>('Pacifico');
  public copyrightText = signal<string>('');
  public hideAccessRestrictedInfo = signal<boolean>(false);
  public lightMode = signal<boolean>(false);
  public publicPortalTitle = signal<string>('');
  public apiCacheDuration = 30000;
  public platformsCacheDuration = 900000;

  public getLogoUrl(): string {
    const logo = this.systemLogo();
    if (!logo) return '';
    if (logo.startsWith('http://') || logo.startsWith('https://')) return logo;

    let base = this.apiSettings.baseUrl;
    if (base.endsWith('/api')) {
      base = base.substring(0, base.length - 4);
    }

    const separator = (base.endsWith('/') || logo.startsWith('/')) ? '' : '/';
    return base + separator + logo;
  }

  public applyBranding(
    name: string,
    logo: string,
    primary: string,
    accent: string,
    sloganTitle?: string,
    sloganSubtitle?: string,
    logoEnabled?: boolean,
    fontFamily?: string,
    copyrightText?: string,
    hideAccessRestrictedInfo?: boolean,
    lightMode?: boolean,
    publicPortalTitle?: string
  ) {
    this.systemName.set(name || '');
    this.systemLogo.set(logo || '');
    this.logoEnabled.set(logoEnabled !== undefined ? logoEnabled : true);
    this.primaryColor.set(primary || '#4f46e5');
    this.accentColor.set(accent || '#6366f1');
    this.systemSloganTitle.set(sloganTitle || 'Access Portal');
    this.systemSloganSubtitle.set(sloganSubtitle || 'Retrieve your 2FA codes easily.');
    this.systemFontFamily.set(fontFamily || 'Pacifico');
    this.copyrightText.set(copyrightText || '');
    this.hideAccessRestrictedInfo.set(hideAccessRestrictedInfo || false);
    this.lightMode.set(lightMode || false);
    this.publicPortalTitle.set(publicPortalTitle || '');

    this.loadGoogleFont(fontFamily || 'Pacifico');
    this.injectDynamicStyles(primary || '#4f46e5', accent || '#6366f1', fontFamily || 'Pacifico');
  }

  private injectDynamicStyles(primary: string, accent: string, fontFamily: string) {
    let styleEl = document.getElementById('dynamic-theme-styles') as HTMLStyleElement;
    if (!styleEl) {
      styleEl = document.createElement('style');
      styleEl.id = 'dynamic-theme-styles';
      document.head.appendChild(styleEl);
    }

    styleEl.innerHTML = `
      :root {
        --primary-color: ${primary};
        --accent-color: ${accent};
        --primary-hover: color-mix(in srgb, var(--primary-color) 85%, black);
        --primary-light: color-mix(in srgb, var(--primary-color) 8%, white);
        --primary-light-border: color-mix(in srgb, var(--primary-color) 18%, white);
        --system-name-font: "${fontFamily}", sans-serif;
      }

      .font-system-name {
        font-family: var(--system-name-font) !important;
      }

      /* Background Overrides */
      .bg-indigo-600 {
        background-color: var(--primary-color) !important;
      }
      .hover\\:bg-indigo-700:hover {
        background-color: var(--primary-hover) !important;
      }
      .bg-indigo-50 {
        background-color: var(--primary-light) !important;
      }
      .bg-indigo-50\\/10 {
        background-color: color-mix(in srgb, var(--primary-color) 4%, transparent) !important;
      }
      .bg-indigo-50\\/100 {
        background-color: var(--primary-light) !important;
      }
      .peer-checked\\:bg-indigo-600:checked ~ div {
        background-color: var(--primary-color) !important;
      }
      .peer-checked\\:bg-indigo-600:checked + div {
        background-color: var(--primary-color) !important;
      }

      /* Text Overrides */
      .text-indigo-600 {
        color: var(--primary-color) !important;
      }
      .text-indigo-700 {
        color: color-mix(in srgb, var(--primary-color) 80%, black) !important;
      }
      .text-indigo-750, .text-indigo-800 {
        color: color-mix(in srgb, var(--primary-color) 70%, black) !important;
      }
      .text-indigo-650 {
        color: color-mix(in srgb, var(--primary-color) 85%, black) !important;
      }
      .hover\\:text-indigo-700:hover {
        color: var(--primary-hover) !important;
      }
      .hover\\:text-indigo-800:hover {
        color: var(--primary-hover) !important;
      }

      /* Border Overrides */
      .border-indigo-600 {
        border-color: var(--primary-color) !important;
      }
      .border-indigo-500 {
        border-color: var(--primary-color) !important;
      }
      .border-indigo-100 {
        border-color: var(--primary-light-border) !important;
      }
      .border-indigo-200 {
        border-color: color-mix(in srgb, var(--primary-color) 30%, white) !important;
      }
      .border-indigo-700 {
        border-color: var(--primary-hover) !important;
      }

      /* Input Focus States */
      .focus\\:border-indigo-600:focus,
      .focus\\:border-indigo-500:focus,
      .focus\\:border-indigo-650:focus {
        border-color: var(--primary-color) !important;
      }
      .focus\\:ring-indigo-600:focus,
      .focus\\:ring-indigo-500:focus,
      .focus\\:ring-indigo-650:focus {
        --tw-ring-color: var(--primary-color) !important;
      }
      .focus-within\\:border-indigo-500:focus-within,
      .focus-within\\:border-indigo-400:focus-within {
        border-color: var(--primary-color) !important;
      }
      .focus-within\\:ring-indigo-500\\/20:focus-within {
        --tw-ring-color: color-mix(in srgb, var(--primary-color) 20%, transparent) !important;
      }
    `;
  }

  private loadedFonts = new Set<string>();

  private loadGoogleFont(fontName: string) {
    if (!fontName || this.loadedFonts.has(fontName)) return;

    // Common system fonts do not need to be loaded from Google Fonts
    const systemFonts = ['inter', 'system-ui', '-apple-system', 'sans-serif', 'serif', 'monospace', 'arial', 'helvetica'];
    if (systemFonts.includes(fontName.toLowerCase())) return;

    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = `https://fonts.googleapis.com/css2?family=${fontName.replace(/ /g, '+')}:wght@400;700;900&display=swap`;
    document.head.appendChild(link);
    this.loadedFonts.add(fontName);
  }
}
