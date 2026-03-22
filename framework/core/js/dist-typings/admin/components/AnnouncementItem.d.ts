/// <reference types="mithril" />
import Component from '../../common/Component';
export interface AnnouncementData {
    id: string;
    title: string;
    slug: string;
    commentCount: number;
    createdAt: string;
    isSticky: boolean;
    url: string;
    excerpt: string;
    authorName: string | null;
    avatarUrl: string | null;
}
export interface IAnnouncementItemAttrs {
    announcement: AnnouncementData;
}
export default class AnnouncementItem extends Component<IAnnouncementItemAttrs> {
    view(): JSX.Element;
}
