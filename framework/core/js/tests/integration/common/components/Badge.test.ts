import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Badge from '../../../../src/common/components/Badge';
import m from 'mithril';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

describe('Badge displays as expected', () => {
  it('renders badge without a tooltip', () => {
    const badge = mq(
      m(Badge, {
        icon: 'fas fa-user',
        type: 'Test',
      })
    );
    expect(badge).toHaveElement('.Badge.Badge--Test');
  });

  it('renders badge with a tooltip', () => {
    const badge = mq(
      m(Badge, {
        icon: 'fas fa-user',
        type: 'Test',
        label: 'Tooltip',
      })
    );
    expect(badge).toHaveElement('.Badge.Badge--Test');
  });
});
