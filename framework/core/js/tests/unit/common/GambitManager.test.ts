import GambitManager from '../../../src/common/GambitManager';

const gambits = new GambitManager();

test('gambits are converted to filters', function () {
  expect(gambits.apply('discussions', { q: 'lorem created:2023-07-07 is:hidden author:behz' })).toStrictEqual({
    q: 'lorem',
    created: '2023-07-07',
    hidden: true,
    author: ['behz'],
  });
});

test('gambits are negated when prefixed with a dash', function () {
  expect(gambits.apply('discussions', { q: 'lorem -created:2023-07-07 -is:hidden -author:behz' })).toStrictEqual({
    q: 'lorem',
    '-created': '2023-07-07',
    '-hidden': true,
    '-author': ['behz'],
  });
});

test('gambits are only applied for the correct resource type', function () {
  expect(gambits.apply('users', { q: 'lorem created:2023-07-07 is:hidden author:behz email:behz@machine.local' })).toStrictEqual({
    q: 'lorem created:2023-07-07 is:hidden author:behz',
    email: 'behz@machine.local',
  });
  expect(gambits.apply('discussions', { q: 'lorem created:2023-07-07..2023-10-18 is:hidden -author:behz email:behz@machine.local' })).toStrictEqual({
    q: 'lorem email:behz@machine.local',
    created: '2023-07-07..2023-10-18',
    hidden: true,
    '-author': ['behz'],
  });
});
