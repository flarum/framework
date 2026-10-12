import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import DiscussionPage from '../../../../src/forum/components/DiscussionPage';
import RequestError from '../../../../src/common/utils/RequestError';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

// Jest fails the test if a rejection goes unhandled, which Node reports only
// once the microtask queue has drained.
const settle = () => new Promise((resolve) => setTimeout(resolve, 10));

describe('DiscussionPage read position', () => {
  test('a read position that fails to save is left for the next scroll to save', async () => {
    const page = Object.create(DiscussionPage.prototype) as DiscussionPage;

    // Already at this position, so only the read state is written.
    (page as any).near = 1;
    (page as any).discussion = {
      title: () => 'Discussion title',
      lastReadPostNumber: () => 0,
      save: () => Promise.reject(new RequestError(0, '', { url: '/api/discussions/1' } as any, {} as any)),
    };

    page.positionChanged(1, 5);

    await settle();
  });
});
