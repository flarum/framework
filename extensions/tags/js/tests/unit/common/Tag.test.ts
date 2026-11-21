import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';

import Tag from '../../../src/common/models/Tag';
import sortTags from '../../../src/common/utils/sortTags';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  (app.store.models as any).tags = Tag;

  const tag = (id: string, name: string, attributes: Record<string, unknown>, parent: string | null = null) => ({
    id,
    type: 'tags',
    attributes: { slug: name.toLowerCase(), name, isChild: parent !== null, ...attributes },
    relationships: { parent: { data: parent ? { type: 'tags', id: parent } : null } },
  });

  app.store.pushPayload({
    data: [
      tag('1', 'General', { isPrimary: true, position: 0, discussionCount: 1 }),
      tag('2', 'Support', { isPrimary: true, position: 1, discussionCount: 1 }),
      tag('3', 'Support Child', { isPrimary: true, position: 0, discussionCount: 1 }, '2'),
      tag('4', 'Rare', { isPrimary: false, position: null, discussionCount: 1 }),
      tag('5', 'Popular', { isPrimary: false, position: null, discussionCount: 9 }),
    ],
  });
});

const get = (id: string) => app.store.getById<Tag>('tags', id)!;

describe('Tag kind helpers', () => {
  it('tells a top-level primary tag apart', () => {
    const tag = get('1');

    expect(tag.isPrimary()).toBe(true);
    expect(tag.isSecondary()).toBe(false);
    expect(tag.isChild()).toBe(false);
    expect(tag.isPrimaryParent()).toBe(true);
  });

  it('treats a child of a primary tag as primary, but not as a primary parent', () => {
    const tag = get('3');

    expect(tag.isPrimary()).toBe(true);
    expect(tag.isSecondary()).toBe(false);
    expect(tag.isChild()).toBe(true);
    expect(tag.isPrimaryParent()).toBe(false);
  });

  it('tells a secondary tag apart', () => {
    const tag = get('4');

    expect(tag.isPrimary()).toBe(false);
    expect(tag.isSecondary()).toBe(true);
    expect(tag.isChild()).toBe(false);
    expect(tag.isPrimaryParent()).toBe(false);
  });

  it('reads the kind from isPrimary, not from position', () => {
    // A secondary tag given a position (as a future order for secondary tags
    // would) stays secondary.
    get('4').pushAttributes({ position: 3 });

    try {
      expect(get('4').isSecondary()).toBe(true);
      expect(get('4').isPrimaryParent()).toBe(false);
    } finally {
      get('4').pushAttributes({ position: null });
    }
  });

  it('follows a change of kind', () => {
    get('4').pushAttributes({ isPrimary: true, position: 2 });

    try {
      expect(get('4').isPrimaryParent()).toBe(true);
    } finally {
      get('4').pushAttributes({ isPrimary: false, position: null });
    }
  });
});

describe('sortTags', () => {
  it('lists primary tags in order with children after their parent, then secondary tags by discussion count', () => {
    const sorted = sortTags(['5', '3', '4', '2', '1'].map(get)).map((tag) => tag.name());

    expect(sorted).toEqual(['General', 'Support', 'Support Child', 'Popular', 'Rare']);
  });
});
