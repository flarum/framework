import Extend from 'flarum/common/extenders';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import DiscussionPageResolver from 'flarum/forum/resolvers/DiscussionPageResolver';
import type DefaultResolver from 'flarum/common/resolvers/DefaultResolver';

// DiscussionPageResolver's generic signature doesn't structurally match the
// DefaultResolver parameter type, though it's the exact resolver core uses for
// these routes. Cast to satisfy the extender's signature.
const resolver = DiscussionPageResolver as unknown as typeof DefaultResolver;

export default [
  // Within the embed frontend, a discussion is viewed at `/embed/:id` rather
  // than `/d/:id`. We re-declare the core `discussion` routes at the embed
  // path, keeping the same component and resolver so navigation behaviour
  // (same-discussion scroll, key generation) is preserved.
  new Extend.Routes().add('discussion', '/embed/:id', DiscussionPage, resolver).add('discussion.near', '/embed/:id/:near', DiscussionPage, resolver),
];
