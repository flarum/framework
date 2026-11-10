import app from '../app';
import type Model from '../Model';
import type { FlarumRequestOptions } from '../Application';

export interface IModelWithIconImage extends Model {
  iconImageUrl(): string | null | undefined;
  iconImageSrcset(): string | null | undefined;
}

/**
 * An icon image being edited in a form: a file chosen to upload, or the
 * removal of the current image. Nothing is sent until `save()`, so a form can
 * create its model first and upload the image to it afterwards.
 */
export default class IconImageState {
  /** A file to upload, null to remove the current image, undefined to leave it. */
  protected change: File | null | undefined = undefined;
  protected objectUrl: string | null = null;

  constructor(protected model: IModelWithIconImage) {}

  /** The image to preview: the chosen file, or the model's current image. */
  url(): string | null {
    if (this.change === undefined) return this.model.iconImageUrl() || null;

    return this.objectUrl;
  }

  srcset(): string | null {
    return this.change === undefined ? this.model.iconImageSrcset() || null : null;
  }

  isDirty(): boolean {
    return this.change !== undefined;
  }

  set(file: File): void {
    this.revokeObjectUrl();
    this.change = file;
    this.objectUrl = URL.createObjectURL(file);
  }

  remove(): void {
    this.revokeObjectUrl();
    this.change = this.model.iconImageUrl() ? null : undefined;
  }

  /**
   * Upload or remove the image. Pass the model a new record was saved as: a
   * new record's save resolves to another instance.
   */
  async save(model: IModelWithIconImage = this.model, options: Pick<FlarumRequestOptions<any>, 'errorHandler'> = {}): Promise<void> {
    this.model = model;

    if (this.change === undefined) return;

    const url = `${app.forum.attribute('apiUrl')}/${model.data.type}/${model.id()}/icon-image`;
    let payload: any;

    if (this.change === null) {
      payload = await app.request({ ...options, method: 'DELETE', url });
    } else {
      const body = new FormData();
      body.append('iconImage', this.change);

      payload = await app.request({ ...options, method: 'POST', url, serialize: (raw: FormData) => raw, body });
    }

    app.store.pushPayload(payload);

    this.revokeObjectUrl();
    this.change = undefined;
  }

  /** Release the chosen file's preview. Call when the form closes. */
  dispose(): void {
    this.revokeObjectUrl();
  }

  protected revokeObjectUrl(): void {
    if (this.objectUrl) URL.revokeObjectURL(this.objectUrl);

    this.objectUrl = null;
  }
}
