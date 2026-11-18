import app from 'flarum/forum/app';

/**
 * A message someone liked, or stopped liking, as realtime sends it: shown
 * again if this browser has it, and otherwise left until it is loaded.
 */
export default function onMessageLikesChanged(data: any): void {
  const id: string | undefined = data?.data?.id;

  if (!id || !app.store.getById('dialog-messages', id)) return;

  app.store.pushPayload(data);
  m.redraw();
}
