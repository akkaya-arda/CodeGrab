import { Component } from '@angular/core';
import { GmailAccountsList } from "../../../shared/gmail-accounts-list/gmail-accounts-list";

@Component({
  selector: 'app-gmail-emails',
  imports: [GmailAccountsList],
  templateUrl: './gmail-emails.html',
  styleUrl: './gmail-emails.css',
})
export class GmailEmails { }
