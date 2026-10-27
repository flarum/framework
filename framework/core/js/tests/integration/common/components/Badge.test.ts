import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
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

  it('gives a labelled badge the img role, so its label is its accessible name', () => {
    const badge = mq(
      m(Badge, {
        icon: 'fas fa-user',
        type: 'Test',
        label: 'Tooltip',
      })
    );
    expect(badge).toHaveElement('.Badge[role="img"]');
  });

  it('leaves a badge without a label without a role', () => {
    const badge = mq(
      m(Badge, {
        icon: 'fas fa-user',
        type: 'Test',
      })
    );
    expect(badge).not.toHaveElement('.Badge[role]');
  });

  it('keeps a role the caller sets', () => {
    const badge = mq(
      m(Badge, {
        icon: 'fas fa-user',
        type: 'Test',
        label: 'Tooltip',
        role: 'status',
      })
    );
    expect(badge).toHaveElement('.Badge[role="status"]');
  });
});
