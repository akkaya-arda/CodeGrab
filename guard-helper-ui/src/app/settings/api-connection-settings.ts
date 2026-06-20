import { Injectable } from "@angular/core";

@Injectable({
    providedIn: 'root'
})
export class ApiConnectionSettings {
    public baseUrl: string = (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1')
        ? 'http://localhost:8000/api'
        : '/api';
    public authenticationUrl: string = this.baseUrl + '/auth';
    public gmailServiceUrl: string = this.baseUrl + '/email/gmail';
    public oAuthServiceUrl: string = this.baseUrl + '/oauth';
    public outlookServiceUrl: string = this.baseUrl + '/email/outlook';
    public imapServiceUrl: string = this.baseUrl + '/email/imap';
}
