import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OutlookAccountsList } from './outlook-accounts-list';

describe('OutlookAccountsList', () => {
  let component: OutlookAccountsList;
  let fixture: ComponentFixture<OutlookAccountsList>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [OutlookAccountsList],
    }).compileComponents();

    fixture = TestBed.createComponent(OutlookAccountsList);
    component = fixture.componentInstance;
    await fixture.whenStable();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
