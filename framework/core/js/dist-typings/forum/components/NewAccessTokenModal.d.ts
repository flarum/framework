import FormModal, { IFormModalAttrs } from '../../common/components/FormModal';
import Stream from '../../common/utils/Stream';
import type AccessToken from '../../common/models/AccessToken';
import type { SaveAttributes } from '../../common/Model';
import type Mithril from 'mithril';
export interface INewAccessTokenModalAttrs extends IFormModalAttrs {
    onsuccess: (token: AccessToken) => void;
}
export default class NewAccessTokenModal<CustomAttrs extends INewAccessTokenModalAttrs = INewAccessTokenModalAttrs> extends FormModal<CustomAttrs> {
    protected titleInput: Stream<string>;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    submitData(): SaveAttributes;
    onsubmit(e: SubmitEvent): void;
}
