import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import GambitManager from '../../../src/common/GambitManager';
import { app } from '../../../src/forum';

const gambits = new GambitManager();

beforeAll(() => bootstrapForum());

describe('GambitManager', () => {
  beforeAll(() => {
    app.boot();
  });

  test('gambits are converted to filters', function () {
    expect(gambits.apply('discussions', { q: 'lorem created:2023-07-07 is:hidden author:behz' })).toStrictEqual({
      q: 'lorem',
      created: '2023-07-07',
      hidden: true,
      author: 'behz',
    });
  });

  test('gambits are negated when prefixed with a dash', function () {
    expect(gambits.apply('discussions', { q: 'lorem -created:2023-07-07 -is:hidden -author:behz' })).toStrictEqual({
      q: 'lorem',
      '-created': '2023-07-07',
      '-hidden': true,
      '-author': 'behz',
    });
  });

  test('gambits are only applied for the correct resource type', function () {
    expect(gambits.apply('users', { q: 'lorem created:2023-07-07 is:hidden author:behz email:behz@machine.local' })).toStrictEqual({
      q: 'lorem created:2023-07-07 is:hidden author:behz',
      email: 'behz@machine.local',
    });
    expect(gambits.apply('discussions', { q: 'lorem created:2023-07-07..2023-10-18 is:hidden -author:behz email:behz@machine.local' })).toStrictEqual(
      {
        q: 'lorem email:behz@machine.local',
        created: '2023-07-07..2023-10-18',
        hidden: true,
        '-author': 'behz',
      }
    );
  });

  test('filters are converted back to gambits', function () {
    expect(gambits.from('discussions', 'lorem', { created: '2023-07-07', hidden: true, author: 'behz' })).toStrictEqual(
      'lorem created:2023-07-07 is:hidden author:behz'
    );
  });

  test('negated filters are converted back to negated gambits', function () {
    expect(gambits.from('discussions', 'lorem', { '-hidden': true, '-unread': true })).toStrictEqual('lorem -is:hidden -is:unread');
    expect(gambits.from('discussions', 'lorem', { '-created': '2023-07-07', '-author': 'behz' })).toStrictEqual(
      'lorem -created:2023-07-07 -author:behz'
    );
  });
});
