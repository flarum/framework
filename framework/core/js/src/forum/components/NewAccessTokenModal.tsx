import Modal, {IInternalModalAttrs} from "../../common/components/Modal";
import Mithril from "mithril";
import Button from "../../common/components/Button";

export interface INewAccessTokenModalAttrs extends IInternalModalAttrs {}

export default class NewAccessTokenModal<CustomAttrs extends INewAccessTokenModalAttrs = INewAccessTokenModalAttrs> extends Modal<CustomAttrs> {


  className(): string {
    return "";
  }

  title(): Mithril.Children {
    return "";
  }

  content(): Mithril.Children {
    return (
      <div className="Form Form--centered">
        <div className="Form-group">
          <input className="FormControl" placeholder=""/>
        </div>
        <div className="Form-group">
          <Button className="Button Button--primary"></Button>
        </div>
      </div>
    );
  }
}
