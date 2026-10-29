/**
 * Follow links that point back at this forum without reloading the page.
 *
 * A discussion link copied from the address bar and pasted into a post is an
 * ordinary `<a href="https://myforum.com/d/1">`. Left alone, clicking it tears
 * down the whole application and boots it again, which is slow and loses the
 * reader's place — even though the destination is a route this app already
 * knows how to render.
 *
 * The server marks these links while rendering the post, so this only has to
 * recognise the marker rather than work out what is internal on the client.
 *
 * One listener on the document handles every post on the page, including posts
 * loaded later and content rendered by extensions, because the check happens
 * when a click actually arrives rather than when the markup is created.
 */
export default function routeInternalLinks(): void;
