import { readFileSync } from 'fs';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const source = readFileSync(resolve(dirname(fileURLToPath(import.meta.url)), '../../../preboot/colorScheme.js'), 'utf8');

// Run the file the way the page head does: inlined, then called with the
// forum's colour scheme.
const applyColorScheme = new Function(`${source}\nreturn applyColorScheme;`)() as (forumScheme: string) => void;

type Page = {
  forumScheme: string;
  rendered?: string | null;
  guest?: boolean;
  stored?: string | null;
  systemDark?: boolean;
  moreContrast?: boolean;
};

function schemeFor({ forumScheme, rendered = null, guest = false, stored = null, systemDark = false, moreContrast = false }: Page): string | null {
  const html = document.documentElement;

  if (rendered === null) html.removeAttribute('data-theme');
  else html.setAttribute('data-theme', rendered);

  html.className = guest ? 'guest-user' : 'logged-in';

  sessionStorage.clear();
  if (stored !== null) sessionStorage.setItem('colorScheme', stored);

  window.matchMedia = ((query: string) => {
    const feature = query.replace(/\s/g, '');

    return { matches: (feature.includes('prefers-color-scheme:dark') && systemDark) || (feature.includes('prefers-contrast:more') && moreContrast) };
  }) as any;

  applyColorScheme(forumScheme);

  return html.getAttribute('data-theme');
}

describe('the colour scheme set before the page is painted', () => {
  test("a user's own scheme is kept, whatever the system prefers", () => {
    expect(schemeFor({ forumScheme: 'auto', rendered: 'light', systemDark: true })).toBe('light');
    expect(schemeFor({ forumScheme: 'auto', rendered: 'dark-hc' })).toBe('dark-hc');
  });

  test('a scheme an extension registered is kept as it is', () => {
    expect(schemeFor({ forumScheme: 'auto', rendered: 'sepia', systemDark: true })).toBe('sepia');
  });

  test('a user following the system gets the system scheme', () => {
    expect(schemeFor({ forumScheme: 'auto', rendered: 'auto', systemDark: true })).toBe('dark');
    expect(schemeFor({ forumScheme: 'auto', rendered: 'auto' })).toBe('light');
  });

  test('the system asking for more contrast picks the high contrast scheme', () => {
    expect(schemeFor({ forumScheme: 'auto', rendered: 'auto', systemDark: true, moreContrast: true })).toBe('dark-hc');
    expect(schemeFor({ forumScheme: 'auto', rendered: 'auto', moreContrast: true })).toBe('light-hc');
  });

  test("a guest's choice for this tab wins", () => {
    expect(schemeFor({ forumScheme: 'auto', rendered: 'auto', guest: true, stored: 'light', systemDark: true })).toBe('light');
  });

  test('a guest who chose to follow the system gets the system scheme', () => {
    expect(schemeFor({ forumScheme: 'auto', rendered: 'auto', guest: true, stored: 'auto', systemDark: true })).toBe('dark');
  });

  test('a guest with no choice gets the system scheme', () => {
    expect(schemeFor({ forumScheme: 'auto', rendered: 'auto', guest: true, systemDark: true })).toBe('dark');
  });

  test('a signed-in user is not swayed by a choice left from before they signed in', () => {
    expect(schemeFor({ forumScheme: 'auto', rendered: 'light', stored: 'dark' })).toBe('light');
  });

  test("the forum's own scheme wins when the forum sets one", () => {
    expect(schemeFor({ forumScheme: 'dark', rendered: 'dark', guest: true, stored: 'light' })).toBe('dark');
    expect(schemeFor({ forumScheme: 'light-hc', rendered: null, systemDark: true })).toBe('light-hc');
  });

  test('blocked storage leaves the guest on the system scheme', () => {
    const getItem = Storage.prototype.getItem;
    Storage.prototype.getItem = () => {
      throw new Error('blocked');
    };

    try {
      expect(schemeFor({ forumScheme: 'auto', rendered: 'auto', guest: true, systemDark: true })).toBe('dark');
    } finally {
      Storage.prototype.getItem = getItem;
    }
  });
});
