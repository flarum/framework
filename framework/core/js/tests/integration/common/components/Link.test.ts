import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Link from '../../../../src/common/components/Link';
import LinkButton from '../../../../src/common/components/LinkButton';
import m from 'mithril';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

describe('Link displays as expected', () => {
  it('renders as simple link', () => {
    const link = mq(
      m(Link, {
        href: '/user',
      })
    );
    expect(link).toHaveElement('a');
  });

  it('renders as button link', () => {
    const link = mq(
      m(LinkButton, {
        href: '/user',
      })
    );
    expect(link).toHaveElement('a');
  });
});
