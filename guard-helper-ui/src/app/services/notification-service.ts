import { HttpClient } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable } from "@angular/core";
import { Observable } from "rxjs";
import { ApiResponse } from "../models/response/api-response";

@Injectable({
    providedIn: 'root'
})
export class NotificationService {
    private notificationsUrl: string;

    constructor(private httpClient: HttpClient, private apiConnectionSettings: ApiConnectionSettings) {
        this.notificationsUrl = this.apiConnectionSettings.baseUrl + '/notifications';
    }

    public getNotifications(page: number = 1): Observable<ApiResponse<any>> {
        return this.httpClient.get<ApiResponse<any>>(`${this.notificationsUrl}/list?page=${page}`);
    }

    public getUnreadCount(): Observable<ApiResponse<{ count: number }>> {
        return this.httpClient.get<ApiResponse<{ count: number }>>(`${this.notificationsUrl}/unread-count`);
    }

    public markAsRead(id?: number): Observable<ApiResponse> {
        return this.httpClient.post<ApiResponse>(`${this.notificationsUrl}/mark-as-read`, { id });
    }

    public deleteNotification(id: number): Observable<ApiResponse> {
        return this.httpClient.post<ApiResponse>(`${this.notificationsUrl}/delete`, { id });
    }
}
