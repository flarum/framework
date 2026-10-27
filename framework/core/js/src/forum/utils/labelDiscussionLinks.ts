import app from '../app';

/**
 * Label links to discussions in the composer preview.
 *
 * The server does this while rendering a post, but the preview never reaches
 * the server: it is produced in the browser by the JavaScript TextFormatter,
 * working from the raw text alone. Without this, a link the writer has just
 * pasted looks like a bare address while they are typing and then changes into
 * a label once the post is saved.
 *
 * This mirrors the decision made in `Formatter::configureDefaultsOnLinks()`.
 * The two have to agree, so that what the writer sees is what they get.
 */
export default function labelDiscussionLinks(element: HTMLElement) {
  const basePath = app.forum.attribute<string>('basePath') || '';
  const faviconUrl = app.forum.attribute<string | null>('faviconUrl');

  for (const link of Array.from(element.querySelectorAll('a'))) {
    // The label stands in for the address only when the address is all the
    // link says. If the writer wrote their own text, that text is theirs.
    if (link.textContent !== link.getAttribute('href')) continue;

    let url: URL;

    try {
      url = new URL(link.href, document.baseURI);
    } catch {
      continue;
    }

    if (url.origin !== window.location.origin) continue;

    let path = url.pathname;

    if (basePath) {
      if (path !== basePath && !path.startsWith(basePath + '/')) continue;

      path = path.slice(basePath.length);
    }

    const matches = /^\/d\/(\d+)(?:-[^/]*)?(?:\/([^/]*))?\/?$/.exec(path);

    if (!matches) continue;

    const near = matches[2];

    // Positions this cannot label — `near-something`, and anything else that
    // is not a plain post number — keep the address rather than claiming a
    // post number that was never found.
    if (near !== undefined && near !== '' && !/^\d+$/.test(near)) continue;

    link.classList.add('UrlLink', 'UrlLink--internal', 'UrlLink--discussion');
    link.textContent = '';

    if (faviconUrl) {
      const favicon = document.createElement('img');
      favicon.src = faviconUrl;
      favicon.className = 'UrlLink-favicon';
      favicon.alt = '';
      favicon.setAttribute('aria-hidden', 'true');
      link.appendChild(favicon);
    }

    const discussion = document.createElement('span');
    discussion.className = 'UrlLink-discussion';
    discussion.textContent = '#' + matches[1];
    link.appendChild(discussion);

    if (near) {
      const post = document.createElement('span');
      post.className = 'UrlLink-post';

      const icon = document.createElement('i');
      icon.className = 'icon fas fa-comment UrlLink-postIcon';
      icon.setAttribute('aria-hidden', 'true');
      post.appendChild(icon);
      post.appendChild(document.createTextNode(near));

      link.appendChild(post);
    }
  }
}
