import { HttpClient } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable } from "@angular/core";
import { Observable } from "rxjs";
import { ApiResponse } from "../models/response/api-response";

export interface Platform {
  id?: number;
  name: string;
  logo: string;
  sender: string;
  subject: string;
  regex?: string;
  enable_heuristic?: boolean;
  grabbing_strategy?: 'heuristic_first' | 'regex_first';
}

@Injectable({
    providedIn: 'root'
})
export class PlatformService {
    private adminUrl: string;

    constructor(private httpClient: HttpClient, private apiConnectionSettings: ApiConnectionSettings) {
        this.adminUrl = this.apiConnectionSettings.baseUrl + '/admin';
    }

    public getPlatforms(): Observable<ApiResponse<Platform[]>> {
        return this.httpClient.get<ApiResponse<Platform[]>>(`${this.adminUrl}/platforms`);
    }

    public createPlatform(platform: Platform): Observable<ApiResponse<Platform>> {
        return this.httpClient.post<ApiResponse<Platform>>(`${this.adminUrl}/platforms`, platform);
    }

    public updatePlatform(id: number, platform: Platform): Observable<ApiResponse<Platform>> {
        return this.httpClient.put<ApiResponse<Platform>>(`${this.adminUrl}/platforms/${id}`, platform);
    }

    public deletePlatform(id: number): Observable<ApiResponse<any>> {
        return this.httpClient.delete<ApiResponse<any>>(`${this.adminUrl}/platforms/${id}`);
    }

    public testRegex(regex: string, body: string): Observable<ApiResponse<{ matched: boolean; code: string | null; matches: string[] }>> {
        return this.httpClient.post<ApiResponse<any>>(`${this.adminUrl}/platforms/test-regex`, { regex, body });
    }

    public uploadLogo(file: File): Observable<ApiResponse<{ logo_url: string }>> {
        const formData = new FormData();
        formData.append('logo', file);
        return this.httpClient.post<ApiResponse<{ logo_url: string }>>(`${this.adminUrl}/platforms/logo`, formData);
    }

    public getAssignments(email: string): Observable<ApiResponse<number[]>> {
        return this.httpClient.get<ApiResponse<number[]>>(`${this.adminUrl}/assignments/${encodeURIComponent(email)}`);
    }

    public saveAssignments(email: string, platformIds: number[]): Observable<ApiResponse<any>> {
        return this.httpClient.post<ApiResponse<any>>(`${this.adminUrl}/assignments`, { email, platform_ids: platformIds });
    }
}
