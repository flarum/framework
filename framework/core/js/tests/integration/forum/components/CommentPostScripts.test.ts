import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import m from 'mithril';
import CommentPost from '../../../../src/forum/components/CommentPost';
import Comment from '../../../../src/forum/components/Comment';
import { override } from '../../../../src/common/extend';
import { app } from '../../../../src/forum';
import { jest } from '@jest/globals';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

let editing = false;

class TestCommentPost extends CommentPost {
  isEditing() {
    return editing;
  }
}

describe('CommentPost scripts', () => {
  const html = '<p>Find me</p><pre><code>x</code><script>window.scriptRuns++</script></pre>';
  const originalContentHtml = Comment.prototype.contentHtml;
  const originalRefresh = CommentPost.prototype.refreshContent;
  let root: HTMLElement;
  let post: any;
  let params: Record<string, string> | undefined;

  const runs = () => (window as any).scriptRuns;
  const mount = () => m.mount(root, { view: () => m(TestCommentPost as any, { post, params }) });
  // Changing the post's freshness makes it rebuild without changing its HTML.
  const rebuild = () => {
    post.freshness = new Date(post.freshness.getTime() + 1000);
    m.redraw.sync();
  };

  beforeEach(() => {
    (window as any).scriptRuns = 0;
    editing = false;
    params = undefined;
    post = app.store.pushPayload<any>({
      data: {
        type: 'posts',
        id: '1',
        attributes: { contentType: 'comment', number: 1, contentHtml: html, createdAt: '2024-01-01T00:00:00Z' },
        relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
      },
      included: [{ type: 'discussions', id: '1', attributes: { title: 'Scripts', slug: '1-scripts' } }],
    });
    root = document.createElement('div');
    document.body.append(root);
  });

  afterEach(() => {
    m.mount(root, null);
    root.remove();
    Comment.prototype.contentHtml = originalContentHtml;
    CommentPost.prototype.refreshContent = originalRefresh;
  });

  test('runs once on first render, not again when the post rebuilds unchanged', () => {
    mount();
    expect(runs()).toBe(1);

    rebuild();
    expect(runs()).toBe(1);
  });

  test('runs again when the post HTML changes', () => {
    mount();
    post.pushAttributes({ contentHtml: html + '<p>Edited</p>' });
    rebuild();

    expect(runs()).toBe(2);
  });

  test('runs again when an override changes its output for the same post HTML', () => {
    let wrap = false;
    override(Comment.prototype, 'contentHtml', function (this: Comment, orig: () => string) {
      return wrap ? `<div class="Gallery">${orig()}</div>` : orig();
    });

    mount();
    wrap = true;
    rebuild();

    expect(runs()).toBe(2);
    expect(root.querySelector('.Gallery script')).not.toBeNull();
  });

  test('runs again when search highlighting re-renders the body, not for a search that matches nothing', () => {
    mount();
    params = { q: 'nowhere' };
    rebuild();
    expect(runs()).toBe(1);

    params = { q: 'find' };
    rebuild();
    expect(runs()).toBe(2);
  });

  test('runs again when editing ends', () => {
    mount();
    editing = true;
    rebuild();
    editing = false;
    rebuild();

    expect(runs()).toBe(2);
  });

  test('an extension overriding refreshContent still controls the scripts', () => {
    override(CommentPost.prototype, 'refreshContent', function () {});

    mount();

    expect(runs()).toBe(0);
  });

  test.each([['<script>window.scriptRuns++</script><p>Lead</p>'], ['<!DOCTYPE html><body><script>window.scriptRuns++</script><p>Lead</p>']])(
    'keeps rendering after running a script at the very start of the HTML: %s',
    (leading) => {
      let lead = true;
      override(Comment.prototype, 'contentHtml', function (this: Comment, orig: () => string) {
        return lead ? leading : orig();
      });
      const errors = jest.spyOn(console, 'error').mockImplementation(() => {});

      mount();
      lead = false;
      rebuild();

      expect(runs()).toBe(2);
      expect(root.querySelector('.Post-body')!.textContent).not.toContain('Lead');
      expect(errors).not.toHaveBeenCalled();
      errors.mockRestore();
    }
  );
});
