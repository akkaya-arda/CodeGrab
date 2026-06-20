import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OutlookEmails } from './outlook-emails';

describe('OutlookEmails', () => {
  let component: OutlookEmails;
  let fixture: ComponentFixture<OutlookEmails>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [OutlookEmails],
    }).compileComponents();

    fixture = TestBed.createComponent(OutlookEmails);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
