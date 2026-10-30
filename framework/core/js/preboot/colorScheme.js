// Inlined at the top of every page's <head>, ahead of the stylesheets, so the
// page is painted in the visitor's colour scheme from the start. It makes the
// same choice Application#initColorScheme makes once the app has booted.
// Not bundled: keep it plain ES5 with no dependencies.
function applyColorScheme(forumScheme) {
  var html = document.documentElement;

  // The server has rendered the forum's scheme when it sets one, and
  // otherwise the user's own preference, which is "auto" for a guest.
  var scheme = html.getAttribute('data-theme') || forumScheme;

  // A guest's choice is kept for the tab only, where the server can't see it.
  if (forumScheme === 'auto' && html.classList.contains('guest-user')) {
    try {
      scheme = sessionStorage.getItem('colorScheme') || scheme;
    } catch (e) {
      // The browser blocks storage.
    }
  }

  if (scheme === 'auto') {
    scheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';

    if (window.matchMedia('(prefers-contrast: more)').matches) {
      scheme += '-hc';
    }
  }

  html.setAttribute('data-theme', scheme);
}
