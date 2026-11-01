/**
 * Where to move the address when it is the homepage's own: the page chosen
 * as the homepage is served at the forum's root, and the frontend has nothing
 * at its own address, so the router would fall back to the root and drop the
 * query, such as a search a browser saved as /all?q=…. Null for any other page.
 */
export default function homepageAddress(
  location: Pick<Location, 'pathname' | 'search' | 'hash'>,
  basePath: string,
  defaultRoute: string
): string | null {
  // A trailing slash would only be stripped after this, as the router does.
  const pathname = location.pathname.replace(/(.)\/+$/, '$1');

  if (defaultRoute === '/' || pathname !== basePath + defaultRoute) return null;

  return basePath + '/' + location.search + location.hash;
}
