import { HttpClient } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { ApiResponse } from "../models/response/api-response";
import { map, Observable } from "rxjs";
import { OutlookAccount } from "../models/outlook-account";
import { Inject, Injectable } from "@angular/core";

@Injectable({
    providedIn: 'root'
})
export class OutlookService {
    constructor(private httpClient: HttpClient, private apiConnectionSettings: ApiConnectionSettings) {

    }

    public getAccounts(): Observable<ApiResponse<OutlookAccount[]>> {
        return this.httpClient.get<ApiResponse<any>>(this.apiConnectionSettings.outlookServiceUrl + '/get-accounts').pipe(
            map((response: ApiResponse<any>) => {
                if (response.success && response.data != null) {
                    console.log("[OutlookService]", "Successfully fetched outlook accounts & mapping.");

                    const data: OutlookAccount[] = response.data.map((account: any) => {
                        return {
                            id: account.id,
                            email: account.email,
                            accessTokenExpiresAt: new Date(account.access_token_expires_at),
                            refreshTokenExpiresAt: new Date(account.refresh_token_expires_at),
                            isActive: account.is_active,
                            lastUsedAt: new Date(account.last_used_at),
                            fetchCount: account.fetch_count
                        }
                    });
                    return {
                        success: response.success,
                        data: data,
                        message: response.message
                    }
                }
                return response;
            })
        );
    }

    public enableAccount(email: string) {
        return this.httpClient.post<ApiResponse>(this.apiConnectionSettings.outlookServiceUrl + '/enable-account', { email: email });
    }

    public disableAccount(email: string) {
        return this.httpClient.post<ApiResponse>(this.apiConnectionSettings.outlookServiceUrl + '/disable-account', { email: email });
    }

    public getAuthorizationUrl(): Observable<ApiResponse<string>> {
        return this.httpClient.post<ApiResponse<string>>(this.apiConnectionSettings.oAuthServiceUrl + '/outlook/get-redirect-link', {});
    }

    public deleteAccount(email: string): Observable<ApiResponse> {
        return this.httpClient.post<ApiResponse>(this.apiConnectionSettings.outlookServiceUrl + '/delete-account', { email: email });
    }

}
