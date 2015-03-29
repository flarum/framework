import ApplicationSerializer from '../serializers/application';

export default ApplicationSerializer.extend({
  attrs: {
    number: {serialize: false},
    time: {serialize: false},
    type: {serialize: false},
    contentHtml: {serialize: false},
    editTime: {serialize: false},
    editUser: {serialize: false},
    hideTime: {serialize: false},
    hideUser: {serialize: false},
    canEdit: {serialize: false},
    canDelete: {serialize: false}
  }
});
