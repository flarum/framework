import flarum from 'flarum';

/**
 * Make a request to save the given settings to the database.
 *
 * @param {Object} settings
 * @return {Promise}
 */
export default function saveSettings(settings) {
  const oldSettings = JSON.parse(JSON.stringify(flarum.data.settings));

  Object.assign(flarum.data.settings, settings);

  return flarum.ajax.request({
    method: 'POST',
    url: flarum.forum.apiUrl + '/settings',
    data: settings
  }).catch(error => {
    flarum.data.settings = oldSettings;
    throw error;
  });
}
