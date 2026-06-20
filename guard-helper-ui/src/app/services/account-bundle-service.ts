import { HttpClient } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable, inject } from "@angular/core";
import { Observable } from "rxjs";
import { ApiResponse } from "../models/response/api-response";

export interface AccountBundle {
  id?: number;
  name: string;
  email: string;
  login_username?: string;
  platform: string;
  password?: string;
  is_active: boolean;
  hide_email?: boolean;
  created_at?: string;
  updated_at?: string;
}

@Injectable({
  providedIn: 'root'
})
export class AccountBundleService {
  private httpClient = inject(HttpClient);
  private apiConnectionSettings = inject(ApiConnectionSettings);

  public getBundles(): Observable<ApiResponse<AccountBundle[]>> {
    return this.httpClient.get<ApiResponse<AccountBundle[]>>(
      this.apiConnectionSettings.baseUrl + '/admin/account-bundles'
    );
  }

  public createBundle(payload: any): Observable<ApiResponse<AccountBundle>> {
    return this.httpClient.post<ApiResponse<AccountBundle>>(
      this.apiConnectionSettings.baseUrl + '/admin/account-bundles',
      payload
    );
  }

  public updateBundle(id: number, payload: any): Observable<ApiResponse<AccountBundle>> {
    return this.httpClient.put<ApiResponse<AccountBundle>>(
      this.apiConnectionSettings.baseUrl + `/admin/account-bundles/${id}`,
      payload
    );
  }

  public deleteBundle(id: number): Observable<ApiResponse<any>> {
    return this.httpClient.delete<ApiResponse<any>>(
      this.apiConnectionSettings.baseUrl + `/admin/account-bundles/${id}`
    );
  }

  public generateBulk(payload: {
    account_bundle_id: number;
    quantity: number;
    limit: number | null;
    expires_at?: string | null;
    tag?: string | null;
    prefix?: string | null;
    hide_email?: boolean | null;
  }): Observable<ApiResponse<any[]>> {
    return this.httpClient.post<ApiResponse<any[]>>(
      this.apiConnectionSettings.baseUrl + '/admin/access-grants/bulk',
      payload
    );
  }
}
