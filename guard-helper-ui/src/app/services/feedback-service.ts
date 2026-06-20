import { HttpClient } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable } from "@angular/core";
import { Observable } from "rxjs";
import { ApiResponse } from "../models/response/api-response";

@Injectable({
    providedIn: 'root'
})
export class FeedbackService {
    constructor(private httpClient: HttpClient, private apiConnectionSettings: ApiConnectionSettings) { }

    public submitFeedback(feedback: { email: string; platform: string; is_working: boolean; comment?: string; log_id?: number }): Observable<ApiResponse> {
        return this.httpClient.post<ApiResponse>(this.apiConnectionSettings.baseUrl + '/public/feedback', feedback);
    }

    public getFeedbacks(page: number = 1): Observable<ApiResponse<any>> {
        return this.httpClient.get<ApiResponse<any>>(this.apiConnectionSettings.baseUrl + '/admin/feedbacks?page=' + page);
    }
}
