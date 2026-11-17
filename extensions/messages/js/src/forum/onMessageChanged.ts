import app from 'flarum/forum/app';

/**
 * A message edited, deleted or restored, as realtime sends it to this member:
 * shown again if this browser has it, and otherwise left until it is loaded.
 */
export default function onMessageChanged(data: any): void {
  const id: string | undefined = data?.data?.id;

  if (!id || !app.store.getById('dialog-messages', id)) return;

  const attributes = data.data.attributes ?? {};

  // A deleted message comes without what it said, for those who may not see
  // it: the store would otherwise keep what it had.
  if (attributes.isHidden && !('contentHtml' in attributes)) attributes.contentHtml = null;

  app.store.pushPayload(data);
  m.redraw();
}
