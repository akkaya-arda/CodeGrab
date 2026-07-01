import { HttpInterceptorFn, HttpResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { ThemeService } from '../services/theme-service';
import { of, tap } from 'rxjs';

const cache = new Map<string, { response: HttpResponse<any>; timestamp: number }>();

export const cacheInterceptor: HttpInterceptorFn = (req, next) => {
  const themeService = inject(ThemeService);
  const isLightMode = themeService.lightMode();
  const cacheDuration = themeService.apiCacheDuration;

  if (req.method !== 'GET') {
    cache.clear();
    return next(req);
  }

  if (!isLightMode) {
    return next(req);
  }

  const bypassUrls = [
    '/auth/me',
    '/auth/login',
    '/auth/logout',
    '/admin/settings/test-smtp',
    '/admin/settings/oauth-config',
    '/email/gmail/status',
    '/email/outlook/status',
    '/email/imap/status'
  ];

  if (bypassUrls.some(url => req.url.includes(url))) {
    return next(req);
  }

  const now = Date.now();
  for (const [key, val] of cache.entries()) {
    if (now - val.timestamp >= cacheDuration) {
      cache.delete(key);
    }
  }

  const cacheKey = req.urlWithParams;
  const cached = cache.get(cacheKey);

  if (cached && (now - cached.timestamp < cacheDuration)) {
    return of(cached.response);
  }

  return next(req).pipe(
    tap(event => {
      if (event instanceof HttpResponse) {
        cache.set(cacheKey, {
          response: event,
          timestamp: Date.now()
        });
      }
    })
  );
};
