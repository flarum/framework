import ItemList from '../../common/utils/ItemList';
import listItems from "../../common/helpers/listItems";

export default class extensionData {
  constructor(extension) {
    this.extension = extension;
    app.extensionSettings[extension] = app.extensionSettings[extension] || {};
  }

  registerData(type, content, permissionType = null, priority = 0) {
    if (content instanceof ItemList) {
      content = listItems(content.toArray());
    }

    app.extensionSettings[this.extension][type] = app.extensionSettings[this.extension][type] || {};

    if (typeof content === 'object' && type === 'permissions') {
      if (!app.extensionSettings[this.extension][type][permissionType]) {
        app.extensionSettings[this.extension][type][permissionType] = new ItemList();
      }
      app.extensionSettings[this.extension][type][permissionType]
        .add(content.permission, content)
    } else {
      app.extensionSettings[this.extension][type] = content;
    }

    return this;
  }

  registerPage(component) {
    app.routes[this.extension] = { path: this.extension, component}

    return this;
  }
}
