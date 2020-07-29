import Page from '../../common/components/Page';

export default class NotAuthenticatedErrorPage extends Page {
  view() {
    return (
      <div className="ErrorPage">
        <div className="container">
          <p>Error!</p>
        </div>
      </div>
    );
  }
}
