<?php namespace Flarum\Core\Formatter;

use Misd\Linkify\Linkify;

class BasicFormatter
{
	public function format($text)
	{
		$text = htmlspecialchars($text);

		$linkify = new Linkify;
		$text = $linkify->process($text, ['attr' => ['target' => '_blank']]);

        $text = preg_replace_callback('/(?:^ *[-*]\s*([^\n]*)(?:\n|$)){2,}/m', function ($matches) {
            return '</p><ul>'.preg_replace('/^ *[-*]\s*([^\n]*)(?:\n|$)/m', '<li>$1</li>', trim($matches[0])).'</ul><p>';
        }, $text);

		$text = '<p>'.preg_replace(['/[\n]{2,}/', '/\n/'], ['</p><p>', '<br>'], trim($text)).'</p>';

        $text = preg_replace(array("/<p>\s*<\/p>/i", "/(?<=<p>)\s*(?:<br>)*/i", "/\s*(?:<br>)*\s*(?=<\/p>)/i"), "", $text);
        $text = str_replace("<p></p>", "", $text);

		return $text;
	}
}
