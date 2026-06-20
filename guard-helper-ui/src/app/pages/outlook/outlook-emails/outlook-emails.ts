import { Component } from '@angular/core';
import { OutlookAccountsList } from "../../../shared/outlook-accounts-list/outlook-accounts-list";

@Component({
  selector: 'app-outlook-emails',
  imports: [OutlookAccountsList],
  templateUrl: './outlook-emails.html',
  styleUrl: './outlook-emails.css',
})
export class OutlookEmails { }
