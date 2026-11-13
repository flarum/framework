import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';

import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import ItemList from 'flarum/common/utils/ItemList';
import m from 'mithril';
import mq from 'mithril-query';

import addTagComposer, { addTagsHeaderItem } from '../../../src/forum/addTagComposer';
import Tag from '../../../src/common/models/Tag';

/** The tag page being viewed. */
const tag = { parent: () => null };

beforeAll(() => {
  bootstrapForum();
  app.boot();

  (app as any).currentTag = () => tag;
  addTagComposer();

  (app.store.models as any).tags = Tag;
  app.store.pushPayload({ data: [{ type: 'tags', id: '1', attributes: { name: 'General', slug: 'general', canStartDiscussion: true } }] } as any);
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

/**
 * The discussion composer's header with these tags chosen. Core passes its body
 * `disabled` while the composer is minimized.
 */
function header(tags: Tag[], minimized: boolean) {
  const items = new ItemList();
  const composer = { attrs: { disabled: minimized }, composer: { fields: { tags } }, chooseTags() {} };

  addTagsHeaderItem.call(composer, items);

  return mq(m('ul', items.toArray()));
}

describe("the discussion composer's header", () => {
  const general = () => app.store.getById<Tag>('tags', '1')!;

  test('prompts to choose tags when none are chosen', () => {
    header([], false).should.have('.DiscussionComposer-changeTags .untagged');
  });

  test('shows the chosen tags', () => {
    header([general()], false).should.contain('General');
  });

  // A minimized composer's header can't be clicked: a click anywhere restores it.
  test("doesn't prompt to choose tags while minimized", () => {
    header([], true).should.not.have('.DiscussionComposer-changeTags');
  });

  // The minimized composer is itself a button that restores it.
  test('still shows the chosen tags while minimized, but not as a button', () => {
    const out = header([general()], true);

    out.should.contain('General');
    out.should.not.have('button');
  });
});
