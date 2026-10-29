import { override } from 'flarum/common/extend';
import PostMeta from 'flarum/forum/components/PostMeta';

/**
 * Within the embed frontend, discussion routes live under `/embed`. When
 * linking out (e.g. clicking a post permalink) we want to send the user to the
 * real forum URL under `/d`, opened in a new tab so they leave the embed.
 */
export default function overrideLinks(): void {
  override(m.route.Link as any, 'view', function (original: (vnode: any) => any, vnode: any) {
    if (typeof vnode.attrs.href === 'string') {
      vnode.attrs.href = vnode.attrs.href.replace('/embed', '/d');
      vnode.attrs.target = '_blank';
    }

    return original(vnode);
  });

  override(PostMeta.prototype, 'getPermalink', function (original: (post: any) => string | null, post: any) {
    // getPermalink can return null; only rewrite when there's a string.
    return original(post)?.replace('/embed', '/d') ?? null;
  });
}
