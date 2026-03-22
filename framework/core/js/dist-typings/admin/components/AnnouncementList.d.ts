/// <reference types="mithril" />
import Component from '../../common/Component';
import { AnnouncementData } from './AnnouncementItem';
export interface IAnnouncementListAttrs {
    announcements: AnnouncementData[] | null;
    loading: boolean;
    error: boolean;
    onRetry: () => void;
}
export default class AnnouncementList extends Component<IAnnouncementListAttrs> {
    view(): JSX.Element;
}
