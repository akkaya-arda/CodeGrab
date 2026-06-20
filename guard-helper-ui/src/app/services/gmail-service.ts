import { HttpClient } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable } from "@angular/core";
import { map, Observable } from "rxjs";
import { GmailAccount } from "../models/gmail-account";
import { ApiResponse } from "../models/response/api-response";

@Injectable({
    providedIn: 'root'
})
export class GmailService {
    constructor(private httpClient: HttpClient, private apiConnectionSettings: ApiConnectionSettings) { }

    public getAccounts(): Observable<ApiResponse<GmailAccount[]>> {
        return this.httpClient.get<ApiResponse<any>>(this.apiConnectionSettings.gmailServiceUrl + '/get-accounts').pipe(
            map((response: ApiResponse<any>) => {
                if (response.success && response.data != null) {
                    console.log("[GmailService]", "Successfully fetched gmail accounts & mapping.");

                    const data: GmailAccount[] = response.data.map((account: any) => {
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
        return this.httpClient.post<ApiResponse>(this.apiConnectionSettings.gmailServiceUrl + '/enable-account', { email: email });
    }

    public disableAccount(email: string) {
        return this.httpClient.post<ApiResponse>(this.apiConnectionSettings.gmailServiceUrl + '/disable-account', { email: email });
    }

    public getAuthorizationUrl(): Observable<ApiResponse<string>> {
        return this.httpClient.post<ApiResponse<string>>(this.apiConnectionSettings.oAuthServiceUrl + '/google/get-redirect-link', {});
    }

    public deleteAccount(email: string): Observable<ApiResponse> {
        return this.httpClient.post<ApiResponse>(this.apiConnectionSettings.gmailServiceUrl + '/delete-account', { email: email });
    }

}
