import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';

import IndexSidebar from 'flarum/forum/components/IndexSidebar';

import addTagComposer from '../../../src/forum/addTagComposer';

/** The tag page being viewed. */
const tag = { parent: () => null };

beforeAll(() => {
  bootstrapForum();
  app.boot();

  (app as any).currentTag = () => tag;
  addTagComposer();
});

afterEach(() => jest.restoreAllMocks());

// Jest fails the test if a rejection goes unhandled, which Node reports only
// once the microtask queue has drained.
const settle = () => new Promise((resolve) => setTimeout(resolve, 10));

/** Start a discussion as the sidebar's button does, which handles a refusal. */
function startDiscussion() {
  const sidebar = Object.create(IndexSidebar.prototype) as IndexSidebar;

  return sidebar.newDiscussionAction().catch(() => {});
}

describe('starting a discussion on a tag page', () => {
  test('the new discussion is tagged with the tag', async () => {
    jest.spyOn(app.composer, 'load').mockResolvedValue(undefined as any);
    jest.spyOn(app.composer, 'show').mockImplementation(() => {});
    app.composer.fields = {} as any;

    startDiscussion();

    await settle();

    expect(app.composer.fields.tags).toEqual([tag]);
  });

  test('a guest is asked to log in, and nothing more', async () => {
    const user = app.session.user;
    app.session.user = null;
    jest.spyOn(app.modal, 'show').mockImplementation(() => {});

    try {
      startDiscussion();

      await settle();

      expect(app.modal.show).toHaveBeenCalled();
    } finally {
      app.session.user = user;
    }
  });
});
