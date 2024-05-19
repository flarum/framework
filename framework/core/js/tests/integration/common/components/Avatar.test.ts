import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Avatar from '../../../../src/common/components/Avatar';
import m from 'mithril';
import mq from 'mithril-query';
import User from '../../../../src/common/models/User';

beforeAll(() => bootstrapForum());

describe('Avatar displays as expected', () => {
  it('renders avatar when user is deleted', () => {
    const avatar = mq(m(Avatar, { user: null, className: 'test' }));
    expect(avatar).toHaveElement('span.Avatar.test');
  });

  it('renders avatar when user has avatarUrl', () => {
    const user = new User({
      attributes: {
        avatarUrl: 'http://example.com/avatar.png',
        color: '#000000',
        username: 'test',
        displayName: 'test',
      },
    });
    const avatar = mq(m(Avatar, { user }));
    expect(avatar).toHaveElement('img.Avatar');
    expect(avatar).toHaveElementAttr('img.Avatar', 'src', 'http://example.com/avatar.png');
  });
});
