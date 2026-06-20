import { HttpClient } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable, inject } from "@angular/core";
import { Observable } from "rxjs";
import { ApiResponse } from "../models/response/api-response";

export interface AccessGrant {
  id?: number;
  token: string;
  email: string;
  platform: string;
  tag?: string | null;
  limit: number | null;
  uses: number;
  is_active: boolean;
  expires_at?: string | null;
  created_at?: string;
  updated_at?: string;
  hide_email?: boolean;
}

@Injectable({
  providedIn: 'root'
})
export class AccessGrantService {
  private httpClient = inject(HttpClient);
  private apiConnectionSettings = inject(ApiConnectionSettings);

  public getGrants(): Observable<ApiResponse<AccessGrant[]>> {
    return this.httpClient.get<ApiResponse<AccessGrant[]>>(this.apiConnectionSettings.baseUrl + '/admin/access-grants');
  }

  public createGrant(payload: { email: string; platform: string; limit: number | null; expires_at?: string | null; tag?: string | null; prefix?: string | null; hide_email?: boolean }): Observable<ApiResponse<AccessGrant>> {
    return this.httpClient.post<ApiResponse<AccessGrant>>(this.apiConnectionSettings.baseUrl + '/admin/access-grants', payload);
  }

  public deleteGrant(id: number): Observable<ApiResponse<any>> {
    return this.httpClient.delete<ApiResponse<any>>(this.apiConnectionSettings.baseUrl + `/admin/access-grants/${id}`);
  }

  public getEmails(): Observable<ApiResponse<string[]>> {
    return this.httpClient.get<ApiResponse<string[]>>(this.apiConnectionSettings.baseUrl + '/admin/access-grants/emails');
  }

  public verifyToken(token: string): Observable<ApiResponse<any>> {
    return this.httpClient.get<ApiResponse<any>>(this.apiConnectionSettings.baseUrl + '/public/access-grant', {
      params: { token }
    });
  }

  public revokeBulk(ids: number[]): Observable<ApiResponse<any>> {
    return this.httpClient.post<ApiResponse<any>>(this.apiConnectionSettings.baseUrl + '/admin/access-grants/revoke-bulk', { ids });
  }

  public revokeTag(tag: string): Observable<ApiResponse<any>> {
    return this.httpClient.post<ApiResponse<any>>(this.apiConnectionSettings.baseUrl + '/admin/access-grants/revoke-tag', { tag });
  }

  public getTags(): Observable<ApiResponse<string[]>> {
    return this.httpClient.get<ApiResponse<string[]>>(this.apiConnectionSettings.baseUrl + '/admin/access-grants/tags');
  }
}
