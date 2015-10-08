export default function saveSettings(settings) {
  const oldSettings = JSON.parse(JSON.stringify(app.settings));

  Object.assign(app.settings, settings);

  return app.request({
    method: 'POST',
    url: app.forum.attribute('apiUrl') + '/settings',
    data: settings
  }).catch(error => {
    app.settings = oldSettings;
    throw error;
  });
}
