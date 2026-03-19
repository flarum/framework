import Component from '../../common/Component';
import AnnouncementItem, { AnnouncementData } from './AnnouncementItem';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Icon from '../../common/components/Icon';
import Link from '../../common/components/Link';
import app from '../../admin/app';

export interface IAnnouncementListAttrs {
  announcements: AnnouncementData[] | null;
  loading: boolean;
  error: boolean;
  onRetry: () => void;
}

export default class AnnouncementList extends Component<IAnnouncementListAttrs> {
  view() {
    const { announcements, loading, error, onRetry } = this.attrs;

    if (loading) {
      return (
        <div className="AnnouncementList-state">
          <LoadingIndicator />
        </div>
      );
    }

    if (error) {
      return (
        <div className="AnnouncementList-state AnnouncementList-state--error">
          <Icon name="fas fa-exclamation-circle" />
          <p>{app.translator.trans('core.admin.announcements.load_error')}</p>
          <button className="Button" onclick={onRetry}>
            {app.translator.trans('core.admin.announcements.retry')}
          </button>
        </div>
      );
    }

    if (!announcements?.length) {
      return (
        <div className="AnnouncementList-state AnnouncementList-state--empty">
          <Icon name="fas fa-bullhorn" />
          <p>{app.translator.trans('core.admin.announcements.empty')}</p>
        </div>
      );
    }

    return (
      <div className="AnnouncementList">
        {announcements.map((a) => (
          <AnnouncementItem key={a.id} announcement={a} />
        ))}
        <Link className="AnnouncementList-viewAll" href="https://discuss.flarum.org/t/blog" external={true} target="_blank">
          <Icon name="fas fa-external-link-alt" />
          {app.translator.trans('core.admin.announcements.view_all')}
        </Link>
      </div>
    );
  }
}
