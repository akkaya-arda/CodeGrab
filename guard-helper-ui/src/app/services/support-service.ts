import { HttpClient } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable, inject } from "@angular/core";
import { Observable } from "rxjs";
import { ApiResponse } from "../models/response/api-response";

@Injectable({
    providedIn: 'root'
})
export class SupportService {
    private httpClient = inject(HttpClient);
    private apiConnectionSettings = inject(ApiConnectionSettings);

    // --- Public Guest API ---
    public sendMessage(payload: { thread_token: string; message: string; token?: string }): Observable<ApiResponse<any>> {
        return this.httpClient.post<ApiResponse<any>>(
            this.apiConnectionSettings.baseUrl + '/public/support/messages',
            payload
        );
    }

    public getThread(threadToken: string): Observable<ApiResponse<any>> {
        return this.httpClient.get<ApiResponse<any>>(
            this.apiConnectionSettings.baseUrl + `/public/support/threads/${threadToken}`
        );
    }

    // --- Admin Dashboard API ---
    public getAdminThreads(): Observable<ApiResponse<any[]>> {
        return this.httpClient.get<ApiResponse<any[]>>(
            this.apiConnectionSettings.baseUrl + '/admin/support/threads'
        );
    }

    public getAdminThread(id: number): Observable<ApiResponse<any>> {
        return this.httpClient.get<ApiResponse<any>>(
            this.apiConnectionSettings.baseUrl + `/admin/support/threads/${id}`
        );
    }

    public replyAdminThread(id: number, message: string): Observable<ApiResponse<any>> {
        return this.httpClient.post<ApiResponse<any>>(
            this.apiConnectionSettings.baseUrl + `/admin/support/threads/${id}/messages`,
            { message }
        );
    }

    public closeAdminThread(id: number): Observable<ApiResponse<any>> {
        return this.httpClient.post<ApiResponse<any>>(
            this.apiConnectionSettings.baseUrl + `/admin/support/threads/${id}/close`,
            {}
        );
    }
}
