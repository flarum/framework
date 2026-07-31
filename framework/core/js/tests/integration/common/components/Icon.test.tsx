import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import Icon from '../../../../src/common/components/Icon';
import mq from 'mithril-query';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

describe('Icon', () => {
  const render = (name: string) => mq(Icon as any, { name });

  test('renders the given classes verbatim when no style is forced', () => {
    // The default: setting absent/empty means zero behavior change.
    expect(render('fas fa-user')).toHaveElement('i.icon.fas.fa-user');
  });

  test('rewrites the style when the forum forces one', () => {
    app.forum.pushAttributes({ fontAwesomeForcedStyle: 'fa-duotone fa-light' });

    try {
      expect(render('fas fa-user')).toHaveElement('i.icon.fa-duotone.fa-light.fa-user');
      expect(render('fa-solid fa-user')).toHaveElement('i.icon.fa-duotone.fa-light.fa-user');
      // Brands stay untouched.
      expect(render('fab fa-github')).toHaveElement('i.icon.fab.fa-github');
    } finally {
      app.forum.pushAttributes({ fontAwesomeForcedStyle: null });
    }
  });

  test('noStyleOverride keeps the declared style despite a forced one', () => {
    app.forum.pushAttributes({ fontAwesomeForcedStyle: 'fa-duotone fa-light' });

    try {
      const out = mq(Icon as any, { name: 'fas fa-user', noStyleOverride: true });

      expect(out).toHaveElement('i.icon.fas.fa-user');
      // The escape hatch is a component prop, not a DOM attribute.
      expect(out).not.toHaveElement('i[nostyleoverride]');
    } finally {
      app.forum.pushAttributes({ fontAwesomeForcedStyle: null });
    }
  });

  test('noStyleOverride is harmless when nothing is forced', () => {
    expect(mq(Icon as any, { name: 'fas fa-user', noStyleOverride: true })).toHaveElement('i.icon.fas.fa-user');
  });
});
