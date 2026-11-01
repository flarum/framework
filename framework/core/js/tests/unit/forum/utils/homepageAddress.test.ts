import homepageAddress from '../../../../src/forum/utils/homepageAddress';

/**
 * The page chosen as the homepage is served at the forum's root, and the
 * frontend has nothing at its own address any more, so a link to it there,
 * such as a search a browser saved as /all?q=…, would land on the homepage
 * without its query.
 */
describe('homepageAddress', () => {
  test('the homepage at its own address moves to the root, with its query', () => {
    expect(homepageAddress({ pathname: '/all', search: '?q=hello%20world', hash: '' }, '', '/all')).toBe('/?q=hello%20world');
  });

  test('a subfolder install keeps its base path', () => {
    expect(homepageAddress({ pathname: '/forum/tags', search: '', hash: '#top' }, '/forum', '/tags')).toBe('/forum/#top');
  });

  test('any other page stays where it is', () => {
    expect(homepageAddress({ pathname: '/all', search: '?q=x', hash: '' }, '', '/tags')).toBeNull();
    expect(homepageAddress({ pathname: '/', search: '?q=x', hash: '' }, '', '/all')).toBeNull();
    expect(homepageAddress({ pathname: '/all/more', search: '', hash: '' }, '', '/all')).toBeNull();
  });
});
