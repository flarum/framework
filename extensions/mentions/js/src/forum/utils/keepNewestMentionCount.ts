import { override } from 'flarum/common/extend';
import User from 'flarum/common/models/User';

/**
 * Keep the newest mention count a user model has been given, whatever order
 * the responses carrying it arrived in.
 *
 * Several responses carry a user's `mentionCount`: a profile read, the users a
 * new post or discussion mentions, an update's `affectedMentionUsers`. The
 * server can answer them in any order, and the store would keep whichever
 * arrived last, so an older profile read could put back a count a later edit
 * had already corrected. Every write of the count raises its
 * `mentionCountRevision`, and the two are always sent together, so a count
 * whose revision is lower than the one the model holds is older and is
 * dropped. The rest of the payload is applied as usual.
 *
 * A count without a revision, or a model without one yet, is taken as before:
 * there is nothing to compare.
 */
export default function keepNewestMentionCount(): void {
  override(User.prototype, 'pushData', function (this: User, original, data) {
    const attributes = 'attributes' in data ? data.attributes : undefined;
    const incoming = attributes?.mentionCountRevision;
    const stored = this.attribute<number | undefined>('mentionCountRevision');

    if (attributes && typeof incoming === 'number' && typeof stored === 'number' && incoming < stored) {
      // Copy rather than edit: the payload belongs to the response, and other
      // code may still read it.
      const rest = { ...attributes };
      delete rest.mentionCount;
      delete rest.mentionCountRevision;

      return original({ ...data, attributes: rest });
    }

    return original(data);
  });
}
