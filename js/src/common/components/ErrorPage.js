import Page from './Page';

export default class ErrorPage extends Page {
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
