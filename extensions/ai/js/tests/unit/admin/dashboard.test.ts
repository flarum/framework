import app from 'flarum/admin/app';
import '../../../src/admin/index';
import { bootAdmin, payload } from './setup';

const issue = {
  connection: 'claude',
  model: 'claude-x',
  status: 'missing',
  since: '2026-11-06T12:00:00+00:00',
  retiresAt: null,
  features: ['acme.thing'],
};

// Taken from the routes once booted: importing it first runs into core's
// import cycle between the app and its routes.
const dashboard = () => new (app.routes as any).dashboard.component();

afterEach(() => {
  (payload as any).modelIssues = [];
});

describe('the dashboard', () => {
  it('warns when a model in use is deprecated or gone', () => {
    (payload as any).modelIssues = [issue];
    bootAdmin();

    expect(dashboard().availableWidgets().has('flarum-ai-models')).toBe(true);
  });

  it('says nothing when every model in use is fine', () => {
    bootAdmin();

    expect(dashboard().availableWidgets().has('flarum-ai-models')).toBe(false);
  });
});
