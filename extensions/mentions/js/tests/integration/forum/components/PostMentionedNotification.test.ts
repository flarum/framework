import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Notification from 'flarum/common/models/Notification';
import PostMentionedNotification from '../../../../src/forum/components/PostMentionedNotification';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const coreJsDir = resolve(dirname(fileURLToPath(import.meta.url)), '../../../../../../../framework/core/js');

beforeAll(() => {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapForum();
  } finally {
    process.chdir(cwd);
  }
});

describe('PostMentionedNotification', () => {
  beforeAll(() => {
    app.boot();

    app.store.pushPayload({
      data: [
        {
          id: '1',
          type: 'discussions',
          attributes: {
            slug: '1-original-discussion',
            title: 'Original discussion',
          },
        },
        {
          id: '2',
          type: 'discussions',
          attributes: {
            slug: '2-reply-discussion',
            title: 'Reply discussion',
          },
        },
      ],
    });

    app.store.pushPayload({
      data: {
        id: '10',
        type: 'posts',
        attributes: {
          number: 3,
          contentHtml: '<p>Original post</p>',
        },
        relationships: {
          discussion: {
            data: { type: 'discussions', id: '1' },
          },
        },
      },
    });
  });

  function makeNotification(content: Record<string, unknown>) {
    return new Notification(
      {
        id: '1',
        type: 'notifications',
        attributes: {
          content,
          contentType: 'postMentioned',
          createdAt: new Date(),
          isRead: false,
        },
        relationships: {
          subject: {
            data: { type: 'posts', id: '10' },
          },
        },
      },
      app.store
    );
  }

  function hrefFor(content: Record<string, unknown>) {
    const component = new PostMentionedNotification();

    component.attrs = {
      notification: makeNotification(content),
    } as any;

    return component.href();
  }

  it.each([
    { content: { replyNumber: 5, discussionId: 2 }, expectedHref: '/d/2/5' },
    { content: { replyNumber: 5 }, expectedHref: '/d/1-original-discussion/5' },
    { content: { replyNumber: 1, discussionId: 2 }, expectedHref: '/d/2' },
  ])('returns $expectedHref for notification content $content', ({ content, expectedHref }) => {
    expect(hrefFor(content)).toBe(expectedHref);
  });
});
