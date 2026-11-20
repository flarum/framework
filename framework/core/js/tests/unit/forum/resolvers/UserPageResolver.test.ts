import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import PostsUserPage from '../../../../src/forum/components/PostsUserPage';
import UserPageResolver from '../../../../src/forum/resolvers/UserPageResolver';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

function keyFor(username: string): string {
  // @ts-expect-error a stand-in for the router that only answers `param`
  m.route = { param: (key?: string) => (key ? ({ username } as Record<string, string>)[key] : { username }) };

  return new UserPageResolver(PostsUserPage, 'user.posts').makeKey();
}

describe('UserPageResolver', () => {
  // A new key is what makes the router mount a new page for the other user.
  test('gives users whose usernames share the part before a hyphen different keys', () => {
    expect(keyFor('test-one')).not.toBe(keyFor('test-two'));
  });
});
