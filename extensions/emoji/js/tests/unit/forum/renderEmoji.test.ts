import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import { jest } from '@jest/globals';
import renderEmoji from '../../../src/forum/renderEmoji';

const originalPreview = jest.fn((text: string, element: HTMLElement) => {
  element.innerHTML = text;
});

beforeAll(() => {
  bootstrapForum();

  app.store.pushPayload({
    data: { type: 'forums', id: '1', attributes: { 'flarum-emoji.cdn': 'https://example.com/twemoji/[version]/' } },
  } as any);
  app.forum = app.store.getById('forums', '1')!;

  // The s9e global is provided by the page in the browser.
  (globalThis as any).s9e = { TextFormatter: { preview: originalPreview } };

  renderEmoji();
});

let nextPostId = 1;

function contentHtml(html: string): string {
  const id = String(nextPostId++);

  app.store.pushPayload({
    data: { type: 'posts', id, attributes: { contentHtml: html } },
  } as any);

  return app.store.getById<Post>('posts', id)!.contentHtml();
}

function preview(html: string): string {
  const element = document.createElement('div');

  (globalThis as any).s9e.TextFormatter.preview(html, element);

  return element.innerHTML;
}

describe('renderEmoji', () => {
  test('turns emoji in text into images', () => {
    expect(contentHtml('<p>Cold ❄️</p>')).toContain('<img');
    expect(preview('<p>Cold ❄️</p>')).toContain('<img');
  });

  test('keeps emoji in code blocks as plain text', () => {
    const html = '<pre><code>echo "❄️";</code></pre>';

    expect(contentHtml(html)).toBe(html);
    expect(preview(html)).toBe(html);
  });

  test('keeps emoji in inline code as plain text', () => {
    const html = '<p>Use <code>❄️</code> here</p>';

    expect(contentHtml(html)).toBe(html);
    expect(preview(html)).toBe(html);
  });

  test('still turns emoji next to code into images', () => {
    const html = '<p>❄️ <code>❄️</code></p>';

    for (const result of [contentHtml(html), preview(html)]) {
      expect(result.match(/<img/g)).toHaveLength(1);
      expect(result).toContain('<code>❄️</code>');
    }
  });
});
