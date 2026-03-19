import app from '../../admin/app';
import Component from '../../common/Component';
import type Mithril from 'mithril';
import Icon from '../../common/components/Icon';
import Link from '../../common/components/Link';
import dayjs from 'dayjs';

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
  view() {
    const a = this.attrs.announcement;
    const date = dayjs(a.createdAt).format('MMM D, YYYY');

    return (
      <Link className="AnnouncementItem" href={a.url} external={true} target="_blank">
        <div className="AnnouncementItem-body">
          <h3 className="AnnouncementItem-title">
            {a.isSticky && <Icon name="fas fa-thumbtack" className="AnnouncementItem-stickyIcon" />}
            {a.title}
          </h3>
          {a.excerpt && <p className="AnnouncementItem-excerpt">{a.excerpt}</p>}
        </div>
        <div className="AnnouncementItem-footer">
          <div className="AnnouncementItem-byline">
            {a.avatarUrl ? (
              <img className="AnnouncementItem-avatar" src={a.avatarUrl} alt={a.authorName ?? ''} loading="lazy" />
            ) : (
              <span className="AnnouncementItem-avatarFallback">
                <Icon name="fas fa-user" />
              </span>
            )}
            <div className="AnnouncementItem-bylineText">
              {a.authorName && <span className="AnnouncementItem-authorName">{a.authorName}</span>}
              <span className="AnnouncementItem-meta">
                <span className="AnnouncementItem-date">{date}</span>
                <span className="AnnouncementItem-sep">·</span>
                <span className="AnnouncementItem-comments">
                  <Icon name="fas fa-comment-alt" />
                  {app.translator.trans('core.admin.announcements.comments_label', { count: a.commentCount })}
                </span>
              </span>
            </div>
          </div>
          <div className="AnnouncementItem-readMore">
            {app.translator.trans('core.admin.announcements.read_more')}
            <Icon name="fas fa-arrow-right" />
          </div>
        </div>
      </Link>
    );
  }
}
