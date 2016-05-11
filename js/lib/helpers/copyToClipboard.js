/**
 * The `copyToClipboard` helper copies text to the user's clipboard
 * Returns a boolean
 *
 * @param {String} Text to be copied
 */
export default function copyToClipboard(text) {
    var textArea = document.createElement("textarea");

    textArea.style.position = 'fixed';
    textArea.style.top = 0;
    textArea.style.left = 0;
    textArea.style.width = '2em';
    textArea.style.height = '2em';
    textArea.style.padding = 0;
    textArea.style.border = 'none';
    textArea.style.outline = 'none';
    textArea.style.boxShadow = 'none';
    textArea.style.background = 'transparent';

    textArea.value = text;

    document.body.appendChild(textArea);
  
    textArea.select();
  
    var attempt = document.execCommand('copy');
  
    document.body.removeChild(textArea);
    
    return attempt;
}