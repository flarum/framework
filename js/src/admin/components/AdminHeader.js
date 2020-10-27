import Component from '../../common/Component';
import classList from '../../common/utils/classList';
import icon from "../../common/helpers/icon";

export default class AdminHeader extends Component {
  view(vnode) {
    const attrs = Object.assign({}, this.attrs);

    return [
      <div className={classList(['AdminHeader', attrs.className])}>
        <div className="container">
          <h2>
            {icon(attrs.icon)}
            {vnode.children}
          </h2>
          <div className="helpText">{attrs.description}</div>
        </div>
      </div>
    ]
  }
}
