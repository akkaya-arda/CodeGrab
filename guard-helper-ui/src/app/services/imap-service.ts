import { HttpClient } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable } from "@angular/core";
import { map, Observable } from "rxjs";
import { ImapAccount } from "../models/imap-account";
import { ApiResponse } from "../models/response/api-response";

@Injectable({
    providedIn: 'root'
})
export class ImapService {
    constructor(private httpClient: HttpClient, private apiConnectionSettings: ApiConnectionSettings) { }

    public getAccounts(): Observable<ApiResponse<ImapAccount[]>> {
        return this.httpClient.get<ApiResponse<any>>(this.apiConnectionSettings.imapServiceUrl + '/get-accounts').pipe(
            map((response: ApiResponse<any>) => {
                if (response.success && response.data != null) {
                    console.log("[ImapService]", "Successfully fetched imap accounts & mapping.");

                    const data: ImapAccount[] = response.data.map((account: any) => {
                        return {
                            id: account.id,
                            email: account.email,
                            host: account.host,
                            port: account.port,
                            encryption: account.encryption,
                            isActive: account.is_active,
                            lastUsedAt: account.last_used_at ? new Date(account.last_used_at) : undefined,
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

    public addAccount(account: any): Observable<ApiResponse<ImapAccount>> {
        return this.httpClient.post<ApiResponse<ImapAccount>>(this.apiConnectionSettings.imapServiceUrl + '/add-account', account);
    }

    public enableAccount(email: string): Observable<ApiResponse> {
        return this.httpClient.post<ApiResponse>(this.apiConnectionSettings.imapServiceUrl + '/enable-account', { email: email });
    }

    public disableAccount(email: string): Observable<ApiResponse> {
        return this.httpClient.post<ApiResponse>(this.apiConnectionSettings.imapServiceUrl + '/disable-account', { email: email });
    }

    public deleteAccount(email: string): Observable<ApiResponse> {
        return this.httpClient.post<ApiResponse>(this.apiConnectionSettings.imapServiceUrl + '/delete-account', { email: email });
    }

    public testConnection(email: string): Observable<ApiResponse> {
        return this.httpClient.post<ApiResponse>(this.apiConnectionSettings.imapServiceUrl + '/test-connection', { email: email });
    }
}
