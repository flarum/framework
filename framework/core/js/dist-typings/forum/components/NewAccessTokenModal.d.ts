import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Stream from '../../common/utils/Stream';
import type AccessToken from '../../common/models/AccessToken';
import type { SaveAttributes } from '../../common/Model';
import type Mithril from 'mithril';
export interface INewAccessTokenModalAttrs extends IInternalModalAttrs {
    onsuccess: (token: AccessToken) => void;
}
export default class NewAccessTokenModal<CustomAttrs extends INewAccessTokenModalAttrs = INewAccessTokenModalAttrs> extends Modal<CustomAttrs> {
    protected titleInput: Stream<string>;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    submitData(): SaveAttributes;
    onsubmit(e: SubmitEvent): void;
}
