import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import DiscussionPageResolver from 'flarum/forum/resolvers/DiscussionPageResolver';
import extenders from '../../../src/forum/extend';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const coreJsDir = resolve(dirname(fileURLToPath(import.meta.url)), '../../../../../../framework/core/js');

beforeAll(() => {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapForum();
  } finally {
    process.chdir(cwd);
  }
});

describe('extend (routes)', () => {
  beforeEach(() => {
    app.boot();

    // Apply the extension's extenders the way core does at boot.
    extenders.flat().forEach((extender) => extender.extend(app, { name: 'flarum-embed', exports: {} } as any));
  });

  it('declares the discussion routes at the /embed path', () => {
    expect(app.routes.discussion.path).toBe('/embed/:id');
    expect(app.routes['discussion.near'].path).toBe('/embed/:id/:near');
  });

  it('keeps the DiscussionPage component and its resolver', () => {
    expect(app.routes.discussion.component).toBe(DiscussionPage);
    expect((app.routes.discussion as any).resolverClass).toBe(DiscussionPageResolver);
    expect((app.routes['discussion.near'] as any).resolverClass).toBe(DiscussionPageResolver);
  });
});
