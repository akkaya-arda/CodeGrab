import { HttpClient } from "@angular/common/http";
import { ApiConnectionSettings } from "../settings/api-connection-settings";
import { Injectable, inject } from "@angular/core";
import { Observable } from "rxjs";
import { ApiResponse } from "../models/response/api-response";

export interface StaticPage {
    id?: number;
    title: string;
    slug?: string;
    content: string;
    is_published: boolean;
    show_in_footer: boolean;
    created_at?: string;
    updated_at?: string;
}

@Injectable({
    providedIn: 'root'
})
export class StaticPageService {
    private httpClient = inject(HttpClient);
    private apiConnectionSettings = inject(ApiConnectionSettings);

    // --- Admin Dashboard API ---
    public getPages(): Observable<ApiResponse<StaticPage[]>> {
        return this.httpClient.get<ApiResponse<StaticPage[]>>(
            this.apiConnectionSettings.baseUrl + '/admin/static-pages'
        );
    }

    public createPage(payload: StaticPage): Observable<ApiResponse<StaticPage>> {
        return this.httpClient.post<ApiResponse<StaticPage>>(
            this.apiConnectionSettings.baseUrl + '/admin/static-pages',
            payload
        );
    }

    public updatePage(id: number, payload: StaticPage): Observable<ApiResponse<StaticPage>> {
        return this.httpClient.put<ApiResponse<StaticPage>>(
            this.apiConnectionSettings.baseUrl + `/admin/static-pages/${id}`,
            payload
        );
    }

    public deletePage(id: number): Observable<ApiResponse<any>> {
        return this.httpClient.delete<ApiResponse<any>>(
            this.apiConnectionSettings.baseUrl + `/admin/static-pages/${id}`
        );
    }

    // --- Public Guest API ---
    public getPublicPages(): Observable<ApiResponse<StaticPage[]>> {
        return this.httpClient.get<ApiResponse<StaticPage[]>>(
            this.apiConnectionSettings.baseUrl + '/public/static-pages'
        );
    }

    public getPublicPage(slug: string): Observable<ApiResponse<StaticPage>> {
        return this.httpClient.get<ApiResponse<StaticPage>>(
            this.apiConnectionSettings.baseUrl + `/public/static-pages/${slug}`
        );
    }
}
