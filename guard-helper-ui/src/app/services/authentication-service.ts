import { HttpClient } from "@angular/common/http";
import { LoginModel } from "../models/login-model";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable, signal } from "@angular/core";
import { catchError, of, tap } from "rxjs";

@Injectable({
    providedIn: 'root'
})
export class AuthenticationService {
    public currentUser = signal<any>(null);

    constructor(private httpClient: HttpClient, private apiConnectionSettings: ApiConnectionSettings) { }

    public login(loginModel: LoginModel) {
        return this.httpClient.post(this.apiConnectionSettings.authenticationUrl + '/login', loginModel).pipe(
            tap({
                next: (response: any) => {
                    console.log("[AuthService] Login succeeded.");
                    localStorage.setItem('app-token', response.token);
                    this.loadCurrentUser().subscribe();
                },
                error: (error: any) => {
                    console.log("[AuthService] Login failed.");
                }
            }),
        );
    }

    public loadCurrentUser() {
        if (!localStorage.getItem('app-token')) {
            this.currentUser.set(null);
            return of(null);
        }
        return this.httpClient.get<any>(this.apiConnectionSettings.authenticationUrl + '/me').pipe(
            tap({
                next: (response: any) => {
                    if (response.success && response.data) {
                        this.currentUser.set(response.data);
                    } else {
                        this.currentUser.set(null);
                    }
                },
                error: () => {
                    this.currentUser.set(null);
                }
            }),
            catchError(() => {
                this.currentUser.set(null);
                return of(null);
            })
        );
    }

    public updateProfile(profileData: any) {
        return this.httpClient.put<any>(this.apiConnectionSettings.authenticationUrl + '/profile', profileData).pipe(
            tap({
                next: (response: any) => {
                    if (response.success && response.data) {
                        this.currentUser.set(response.data);
                    }
                }
            })
        );
    }

    public resetPassword(email: string) {
        return this.httpClient.post<any>(this.apiConnectionSettings.authenticationUrl + '/reset-password', { email });
    }
}
