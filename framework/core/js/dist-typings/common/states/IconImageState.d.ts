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
    protected model: IModelWithIconImage;
    /** A file to upload, null to remove the current image, undefined to leave it. */
    protected change: File | null | undefined;
    protected objectUrl: string | null;
    constructor(model: IModelWithIconImage);
    /** The image to preview: the chosen file, or the model's current image. */
    url(): string | null;
    srcset(): string | null;
    isDirty(): boolean;
    set(file: File): void;
    remove(): void;
    /**
     * Upload or remove the image. Pass the model a new record was saved as: a
     * new record's save resolves to another instance.
     */
    save(model?: IModelWithIconImage, options?: Pick<FlarumRequestOptions<any>, 'errorHandler'>): Promise<void>;
    /** Release the chosen file's preview. Call when the form closes. */
    dispose(): void;
    protected revokeObjectUrl(): void;
}
