import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import ItemList from 'flarum/common/utils/ItemList';
import { applyEmbedSidebar } from '../../../src/forum/extendDiscussionPage';
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

// Flatten a vnode tree's text children into a single string.
const text = (vnode: any): string => {
  if (vnode == null || typeof vnode === 'boolean') return '';
  if (typeof vnode === 'string' || typeof vnode === 'number') return String(vnode);
  if (Array.isArray(vnode)) return vnode.map(text).join('');
  if (typeof vnode.children === 'string') return vnode.children;
  if (Array.isArray(vnode.children)) return vnode.children.map(text).join('');
  return '';
};

function discussion(commentCount: number) {
  app.store.pushPayload({
    data: { id: '5', type: 'discussions', attributes: { slug: '5-foo', title: 'Foo', commentCount } },
  });
  return app.store.getById('discussions', '5');
}

// A synthetic sidebar ItemList mirroring what core's DiscussionPage produces.
function sidebar() {
  const items = new ItemList<any>();
  items.add('controls', { tag: 'div', attrs: { className: 'App-primaryControl Button' }, children: [] }, 100);
  items.add('scrubber', { tag: 'div', attrs: {}, children: [] }, -100);
  return items;
}

describe('applyEmbedSidebar', () => {
  beforeEach(() => {
    app.boot();
    // Register routes so app.route.discussion() resolves to /embed/... .
    extenders.flat().forEach((e) => e.extend(app, { name: 'flarum-embed', exports: {} } as any));
  });

  it('removes the scrubber', () => {
    const items = sidebar();
    applyEmbedSidebar(items, discussion(5) as any);
    expect(items.has('scrubber')).toBe(false);
  });

  it('adds a replies link pointing at the /d discussion URL', () => {
    const items = sidebar();
    applyEmbedSidebar(items, discussion(5) as any);

    expect(items.has('replies')).toBe(true);
    const html = JSON.stringify(items.get('replies'));
    expect(html).toContain('/d/5');
    expect(html).not.toContain('/embed/5');
  });

  it('uses the singular "comment" for a reply count of one', () => {
    const items = sidebar();
    applyEmbedSidebar(items, discussion(2) as any); // commentCount 2 -> replyCount 1
    const repliesText = text(items.get('replies'));
    expect(repliesText).toContain('1 comment');
    expect(repliesText).not.toContain('1 comments');
  });

  it('uses the plural "comments" for a reply count above one', () => {
    const items = sidebar();
    applyEmbedSidebar(items, discussion(3) as any); // commentCount 3 -> replyCount 2
    expect(text(items.get('replies'))).toContain('2 comments');
  });

  it('strips App-primaryControl from the controls item', () => {
    const items = sidebar();
    applyEmbedSidebar(items, discussion(5) as any);
    expect(items.get('controls').attrs.className).not.toContain('App-primaryControl');
  });
});
