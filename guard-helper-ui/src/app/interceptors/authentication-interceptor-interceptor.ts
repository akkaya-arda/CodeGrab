import { HttpInterceptorFn, HttpErrorResponse } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';

export const authenticationInterceptorInterceptor: HttpInterceptorFn = (req, next) => {
  console.log("[AuthInterceptor] Request:", req.method, req.url);
  const router = inject(Router);

  // Always include credentials for cookies
  let modifiedReq = req.clone({
    withCredentials: true,
    headers: req.headers.set('Authorization', 'Bearer ' + (localStorage.getItem('app-token') || ''))
  });

  console.log("[AuthInterceptor] Modified Request:", modifiedReq.method, modifiedReq.url, "With Credentials:", modifiedReq.withCredentials);

  return next(modifiedReq).pipe(
    catchError((error) => {
      if (error instanceof HttpErrorResponse && error.status === 401) {
        console.warn("[AuthInterceptor] 401 Unauthorized, clearing token and redirecting...");
        localStorage.removeItem('app-token');
        router.navigate(['/login']);
      }
      return throwError(() => error);
    })
  );
};
