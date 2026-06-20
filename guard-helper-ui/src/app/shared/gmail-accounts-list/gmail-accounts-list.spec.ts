import { ComponentFixture, TestBed } from '@angular/core/testing';

import { GmailAccountsList } from './gmail-accounts-list';

describe('GmailAccountsList', () => {
  let component: GmailAccountsList;
  let fixture: ComponentFixture<GmailAccountsList>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [GmailAccountsList],
    }).compileComponents();

    fixture = TestBed.createComponent(GmailAccountsList);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
