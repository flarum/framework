import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import LoadingIndicator from '../../../../src/common/components/LoadingIndicator';
import m from 'mithril';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

describe('LoadingIndicator displays as expected', () => {
  it('renders as simple link', () => {
    const indicator = mq(
      m(LoadingIndicator, {
        display: 'block',
        size: 'large',
        containerClassName: 'container',
        containerAttrs: { 'data-test': 'test' },
        className: 'indicator',
      })
    );
    expect(indicator).toHaveElementAttr('.container', 'data-test', 'test');
    expect(indicator).toHaveElement('.indicator');
  });
});
