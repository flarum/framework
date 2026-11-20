import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import mq from 'mithril-query';
import { app } from '../../../../src/forum';
import UserPage from '../../../../src/forum/components/UserPage';
import { makeUser } from '../../../factory';

beforeAll(() => bootstrapForum());

/**
 * `loadUser` is what every profile page runs to get its user, so this mounts a real
 * `UserPage` that calls it, and reports what was shown and when it resolved.
 */
function open(username: string) {
  const shown: unknown[] = [];
  let loaded: Promise<void> = Promise.resolve();

  class TestUserPage extends UserPage {
    oninit(vnode: any) {
      super.oninit(vnode);
      loaded = this.loadUser(username);
    }

    show(user: any) {
      shown.push(user);
      super.show(user);
    }

    content() {
      return null;
    }

    // AffixedSidebar measures elements with jQuery, which cannot run under jsdom.
    sidebar() {
      return null;
    }
  }

  mq(TestUserPage, {});

  return { shown, loaded };
}

/**
 * A user as another endpoint carries them: with their own attributes, `joinTime`
 * included (the user resource always serializes it), but only the relationships
 * that endpoint was asked to include.
 *
 * Each test uses its own user: the store, and the record of who has been fetched,
 * outlive a single test.
 */
function pushAuthor(id: string, username: string) {
  return app.store.pushObject<any>(
    makeUser({ id, attributes: { username, slug: username, displayName: username, joinTime: '2020-05-21T11:51:06+00:00' } })
  );
}

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('UserPage.loadUser', () => {
  beforeAll(() => app.boot());

  let find: ReturnType<typeof jest.spyOn>;

  beforeEach(() => {
    // A request that never finishes, unless a test says otherwise.
    find = jest.spyOn(app.store, 'find').mockImplementation((() => new Promise(() => {})) as any);
  });

  afterEach(() => {
    find.mockRestore();
    jest.restoreAllMocks();
  });

  describe('for a user that is already in the store', () => {
    it('shows them straight away, without waiting for a request', async () => {
      const author = pushAuthor('5', 'bob');

      const { shown, loaded } = open('bob');

      expect(shown).toEqual([author]);
      // The revalidation below never finishes in this test, so this would hang if
      // `loadUser` waited for it.
      await loaded;
    });

    it('also fetches them from the user endpoint, because the stored record may lack what a profile includes', () => {
      pushAuthor('6', 'carol');

      open('carol');

      expect(find).toHaveBeenCalledTimes(1);
      expect(find).toHaveBeenCalledWith('users', 'carol', { bySlug: true }, expect.anything());
    });

    it('records them as resolved for their slug', () => {
      const dana = pushAuthor('7', 'dana');

      open('dana');

      expect(app.current.get('resolvedUser')).toEqual({ slug: 'dana', user: dana });
    });

    it('fetches silently: a failure raises no alert', () => {
      pushAuthor('8', 'eve');

      open('eve');

      const options = (find.mock.calls[0] as any[])[3];

      expect(typeof options.errorHandler).toBe('function');
      // Returning anything but `false` keeps the default handler, and its alert, away.
      expect(options.errorHandler()).toBeUndefined();
    });

    it('fetches a user once, however often their profile is opened', () => {
      pushAuthor('9', 'frank');

      open('frank');
      open('frank');
      open('frank');

      expect(find).toHaveBeenCalledTimes(1);
    });

    it('tries again on the next visit if the fetch failed', async () => {
      pushAuthor('10', 'grace');
      find.mockImplementationOnce((() => Promise.reject(new Error('offline'))) as any);

      open('grace');
      await flush();
      open('grace');

      expect(find).toHaveBeenCalledTimes(2);
    });
  });

  describe('for a user that is not in the store yet', () => {
    it('fetches them, shows the result, and does not fetch them again on the next visit', async () => {
      find.mockImplementation((async () => pushAuthor('11', 'heidi')) as any);

      const first = open('heidi');
      await first.loaded;

      expect(find).toHaveBeenCalledTimes(1);
      expect(first.shown).toHaveLength(1);

      // Now in the store, and already fetched from the user endpoint.
      const second = open('heidi');
      await flush();

      expect(second.shown).toHaveLength(1);
      expect(find).toHaveBeenCalledTimes(1);
    });

    it('records the address the server resolved them by', async () => {
      let judy: any;
      find.mockImplementation((async () => (judy = pushAuthor('13', 'judy'))) as any);

      await open('13').loaded;

      expect(app.current.get('resolvedUser')).toEqual({ slug: '13', user: judy });
    });

    it('records nothing on a page the visitor went on to', async () => {
      let respond: (user: any) => void = () => {};
      find.mockImplementationOnce((() => new Promise((resolve) => (respond = resolve))) as any);

      const left = open('18');
      open('oscar');
      const current = app.current;
      respond(pushAuthor('18', 'pat'));
      await left.loaded;

      expect(current.get('resolvedUser')).toBeUndefined();
    });
  });

  describe('for the document the server preloaded', () => {
    it('shows it and does not fetch it, as it is the profile itself', () => {
      const preloaded = pushAuthor('12', 'ivan');
      jest.spyOn(app, 'preloadedApiDocument').mockReturnValue(preloaded as any);

      const { shown } = open('ivan');

      expect(shown).toEqual([preloaded]);
      expect(find).not.toHaveBeenCalled();
    });

    it('records the address the server resolved it by', () => {
      const preloaded = pushAuthor('14', 'kim');
      jest.spyOn(app, 'preloadedApiDocument').mockReturnValue(preloaded as any);

      open('14');

      expect(app.current.get('resolvedUser')).toEqual({ slug: '14', user: preloaded });
    });
  });

  // Under the username slug driver `/u/16` is the user named "16", not user 16.
  describe('for an address that is not the slug of a user in the store', () => {
    it('shows the user the server resolves it to', async () => {
      pushAuthor('16', 'mia');
      let sixteen: any;
      find.mockImplementationOnce((async () => (sixteen = pushAuthor('160', '16'))) as any);

      const { shown, loaded } = open('16');

      expect(shown).toEqual([]);
      await loaded;

      expect(find).toHaveBeenCalledWith('users', '16', { bySlug: true });
      expect(shown).toEqual([sixteen]);
      expect(app.current.get('resolvedUser')).toEqual({ slug: '16', user: sixteen });
    });
  });
});
