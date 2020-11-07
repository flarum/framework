import ItemList from '../../common/utils/ItemList';
import listItems from "../../common/helpers/listItems";

export default class ExtensionData {
  constructor(extension) {
    this.extension = extension;
    app.extensionData[extension] = app.extensionData[extension] || {};
  }

  registerData(type, content, permissionType = null, priority = 0) {
    if (content instanceof ItemList) {
      content = listItems(content.toArray());
    }

    app.extensionData[this.extension][type] = app.extensionData[this.extension][type] || {};

    if (type === 'permissions') {
      if (!app.extensionData[this.extension][type][permissionType]) {
        app.extensionData[this.extension][type][permissionType] = new ItemList();
      }
      app.extensionData[this.extension][type][permissionType]
        .add(content.permission, content)
    } else {
      app.extensionData[this.extension][type] = content;
    }

    return this;
  }

  registerPage(component) {
    app.routes[this.extension] = { path: this.extension, component}

    return this;
  }
}
