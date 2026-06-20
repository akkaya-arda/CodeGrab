import { Injectable, signal } from "@angular/core";
import { BehaviorSubject, Subject } from "rxjs";

@Injectable({
    providedIn: 'root'
})
export class LayoutServices {
    private _pageTitle = signal<string>('Dashboard');
    public pageTitle = this._pageTitle.asReadonly();

    public setPageTitle(title: string) {
        this._pageTitle.set(title);
    }
}
