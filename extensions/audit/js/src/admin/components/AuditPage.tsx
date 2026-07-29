import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import AuditBrowser from '../../common/components/AuditBrowser';

export default class AuditPage extends ExtensionPage {
  className() {
    return super.className() + ' AuditPage';
  }

  content() {
    return (
      <div className="AuditPageContainer">
        <AuditBrowser />
      </div>
    );
  }
}
