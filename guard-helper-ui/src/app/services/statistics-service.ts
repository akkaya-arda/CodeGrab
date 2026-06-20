import { HttpClient, HttpParams } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable } from "@angular/core";
import { Observable } from "rxjs";
import { ApiResponse } from "../models/response/api-response";

@Injectable({
    providedIn: 'root'
})
export class StatisticsService {
    private statisticsUrl: string;

    constructor(private httpClient: HttpClient, private apiConnectionSettings: ApiConnectionSettings) {
        this.statisticsUrl = this.apiConnectionSettings.baseUrl + '/statistics';
    }

    public getSummary(): Observable<ApiResponse<any>> {
        return this.httpClient.get<ApiResponse<any>>(this.statisticsUrl + '/summary');
    }

    public getLogs(page: number = 1, search: string = '', status: string = '', platform: string = '', accountType: string = ''): Observable<ApiResponse<any>> {
        let params = new HttpParams().set('page', page.toString());
        
        if (search) {
            params = params.set('search', search);
        }
        if (status) {
            params = params.set('status', status);
        }
        if (platform) {
            params = params.set('platform', platform);
        }
        if (accountType) {
            params = params.set('account_type', accountType);
        }

        return this.httpClient.get<ApiResponse<any>>(this.statisticsUrl + '/logs', { params });
    }
}
