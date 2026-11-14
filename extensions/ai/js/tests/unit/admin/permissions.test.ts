import app from 'flarum/admin/app';
import '../../../src/admin/index';
import { bootAdmin, payload } from './setup';

const feature = {
  key: 'acme-tldr.summaries',
  label: 'acme-tldr.admin.summaries_label',
  description: null,
  extension: 'acme-tldr',
  permission: 'ai.feature.acme-tldr.summaries',
};

describe('feature permissions', () => {
  beforeEach(() => {
    payload.features = [feature] as any;
    bootAdmin();
  });

  afterEach(() => {
    payload.features = [];
  });

  it('can be granted to guests, since features can be called for them', () => {
    const permission = app.registry.getExtensionPermissions('flarum-ai', 'reply').get(feature.permission);

    expect(permission.allowGuest).toBe(true);
  });
});
