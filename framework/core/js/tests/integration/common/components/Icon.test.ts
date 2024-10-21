import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Icon from '../../../../src/common/components/Icon';
import m from 'mithril';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

describe('Icon displays as expected', () => {
  it('renders', () => {
    const icon = mq(
      m(Icon, {
        name: 'fas fa-user',
      })
    );
    expect(icon).toHaveElement('.fas.fa-user');
  });
});
