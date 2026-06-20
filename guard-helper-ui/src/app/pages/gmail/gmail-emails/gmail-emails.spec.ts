import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GmailEmails } from './gmail-emails';

describe('GmailEmails', () => {
  let component: GmailEmails;
  let fixture: ComponentFixture<GmailEmails>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [GmailEmails],
    }).compileComponents();

    fixture = TestBed.createComponent(GmailEmails);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
