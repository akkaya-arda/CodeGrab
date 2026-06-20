import { HttpClient } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable, inject } from "@angular/core";
import { Observable } from "rxjs";
import { ApiResponse } from "../models/response/api-response";

@Injectable({
    providedIn: 'root'
})
export class SettingsService {
    private httpClient = inject(HttpClient);
    private apiConnectionSettings = inject(ApiConnectionSettings);

    public getSettings(): Observable<ApiResponse<any>> {
        return this.httpClient.get<ApiResponse<any>>(this.apiConnectionSettings.baseUrl + '/admin/settings');
    }

    public updateSettings(settings: any): Observable<ApiResponse<any>> {
        return this.httpClient.post<ApiResponse<any>>(this.apiConnectionSettings.baseUrl + '/admin/settings', settings);
    }

    public testSmtp(settings: any): Observable<ApiResponse<any>> {
        return this.httpClient.post<ApiResponse<any>>(this.apiConnectionSettings.baseUrl + '/admin/settings/test-smtp', settings);
    }

    public getOAuthConfig(): Observable<ApiResponse<any>> {
        return this.httpClient.get<ApiResponse<any>>(this.apiConnectionSettings.baseUrl + '/admin/settings/oauth-config');
    }

    public updateOAuthConfig(config: any): Observable<ApiResponse<any>> {
        return this.httpClient.post<ApiResponse<any>>(this.apiConnectionSettings.baseUrl + '/admin/settings/oauth-config', config);
    }
}
