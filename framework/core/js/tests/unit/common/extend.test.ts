import { extend, override } from '../../../src/common/extend';
import Component from '../../../src/common/Component';
import Mithril from 'mithril';
import m from 'mithril';
import mq from 'mithril-query';

describe('extend', () => {
  test('can extend component methods', () => {
    extend(Acme.prototype, 'view', function (original) {
      original.children.push(m('p', 'This is a test extension.'));
    });

    const acme = mq(Acme);

    expect(acme).toContainRaw('This is a test extension.');
  });

  test('can extend multiple methods at once', () => {
    let counter = 0;
    extend(Acme.prototype, ['items', 'otherItems'], function (original) {
      const dataCount = counter++;
      original.children.push(m('li', { 'data-count': dataCount }, 'Breaking News!'));
    });

    const acme = mq(Acme);

    expect(acme).toContainRaw('Breaking News!');
    expect(acme).toHaveElement('li[data-count="0"]');
    expect(acme).toHaveElement('li[data-count="1"]');
  });

  test('can extend lazy loaded components', () => {
    extend('flarum/common/components/Lazy', 'view', function (original) {
      original.children.push(m('div', 'Loaded the lazy component.'));
    });

    const lazy = mq(Lazy);

    expect(lazy).toContainRaw('Lazy loaded component.');
    expect(lazy).not.toContainRaw('Loaded the lazy component.');

    // Emulate the lazy loading of the component.
    // @ts-ignore
    flarum.reg.add('core', 'common/components/Lazy', Lazy);

    const lazy2 = mq(Lazy);

    expect(lazy2).toContainRaw('Lazy loaded component.');
    expect(lazy2).toContainRaw('Loaded the lazy component.');
  });
});

describe('override', () => {
  test('can override component methods', () => {
    override(Acme.prototype, 'items', function (original) {
      return m('ul', [m('li', 'ItemOverride 1'), m('li', 'ItemOverride 2'), m('li', 'ItemOverride 3')]);
    });

    const acme = mq(Acme);

    expect(acme).toContainRaw('ItemOverride 1');
    expect(acme).toContainRaw('ItemOverride 2');
    expect(acme).toContainRaw('ItemOverride 3');

    expect(acme).not.toContainRaw('Item 1');
    expect(acme).not.toContainRaw('Item 2');
    expect(acme).not.toContainRaw('Item 3');
  });

  test('can override multiple methods at once', () => {
    override(Acme.prototype, ['items', 'otherItems'], function (original) {
      return m('ul', [m('li', 'ItemOverride 1'), m('li', 'ItemOverride 2'), m('li', 'ItemOverride 3')]);
    });

    const acme = mq(Acme);

    expect(acme).toContainRaw('ItemOverride 1');
    expect(acme).toContainRaw('ItemOverride 2');
    expect(acme).toContainRaw('ItemOverride 3');

    expect(acme).not.toContainRaw('Item 1');
    expect(acme).not.toContainRaw('Item 2');
    expect(acme).not.toContainRaw('Item 3');

    expect(acme).not.toContainRaw('ItemOther 1');
    expect(acme).not.toContainRaw('ItemOther 2');
    expect(acme).not.toContainRaw('ItemOther 3');
  });

  test('can override lazy loaded components', () => {
    override('flarum/common/components/Lazy', 'view', function (original) {
      return m('div', 'Overridden lazy component.');
    });

    const lazy = mq(Lazy);

    expect(lazy).toContainRaw('Lazy loaded component.');
    expect(lazy).not.toContainRaw('Overridden lazy component.');

    // Emulate the lazy loading of the component.
    // @ts-ignore
    flarum.reg.add('core', 'common/components/Lazy', Lazy);

    const lazy2 = mq(Lazy);

    expect(lazy2).not.toContainRaw('Lazy loaded component.');
    expect(lazy2).toContainRaw('Overridden lazy component.');
  });
});

class Acme extends Component {
  view(): Mithril.Children {
    return m('div', { class: 'Acme' }, [m('h1', m('div', this.items())), m('p', 'This is a test component.'), m('div', this.otherItems())]);
  }

  items() {
    return m('ul', [m('li', 'Item 1'), m('li', 'Item 2'), m('li', 'Item 3')]);
  }

  otherItems() {
    return m('ul', [m('li', 'ItemOther 1'), m('li', 'ItemOther 2'), m('li', 'ItemOther 3')]);
  }
}

class Lazy extends Component {
  view(): Mithril.Children {
    return m('div', 'Lazy loaded component.');
  }
}
