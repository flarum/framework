import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import overrideLinks from '../../../src/forum/overrideLinks';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const coreJsDir = resolve(dirname(fileURLToPath(import.meta.url)), '../../../../../../framework/core/js');

beforeAll(() => {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapForum();
  } finally {
    process.chdir(cwd);
  }
});

describe('overrideLinks', () => {
  beforeEach(() => {
    app.boot();
    overrideLinks();
  });

  it('rewrites /embed hrefs to /d and opens them in a new tab', () => {
    const vnode: any = { attrs: { href: '/embed/5-foo' } };

    // Invoke the overridden Link view; the original just needs to be callable.
    (m.route.Link as any).view(vnode);

    expect(vnode.attrs.href).toBe('/d/5-foo');
    expect(vnode.attrs.target).toBe('_blank');
  });

  it('leaves the vnode alone when there is no href', () => {
    const vnode: any = { attrs: {} };

    expect(() => (m.route.Link as any).view(vnode)).not.toThrow();
    expect(vnode.attrs.target).toBeUndefined();
  });
});
