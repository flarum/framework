export default function extensionEnabled(name) {
  const enabled = JSON.parse(app.data.settings.extensions_enabled);

  return enabled.includes(name);
}
