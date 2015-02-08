<?php namespace Flarum\Core\Formatter;

use Misd\Linkify\Linkify;

class BasicFormatter
{
	public function format($text)
	{
		$text = htmlspecialchars($text);

		$linkify = new Linkify;
		$text = $linkify->process($text, ['attr' => ['target' => '_blank']]);

		$text = '<p>'.preg_replace(['/[\n]{2,}/', '/\n/'], ['</p><p>', '<br>'], trim($text)).'</p>';

		return $text;
	}
}
