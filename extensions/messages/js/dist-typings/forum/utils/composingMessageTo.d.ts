import type Dialog from '../../common/models/Dialog';
/**
 * Whether the composer is open on a reply to this conversation: it is loaded
 * with `replyingTo`, so that is what must match. An extension's
 * module is looked up by its `ext:` path: without the prefix, the registry
 * looks in core and never finds it.
 */
export default function composingMessageTo(dialog: Dialog): boolean;
