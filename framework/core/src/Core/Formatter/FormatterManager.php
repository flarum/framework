<?php namespace Flarum\Core\Formatter;

use Illuminate\Container\Container;

class FormatterManager
{
	protected $formatters = [];

	/**
	 * The IoC container instance.
	 *
	 * @var \Illuminate\Container\Container
	 */
	protected $container;

	/**
	 * Create a new formatter manager instance.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @return void
	 */
	public function __construct(Container $container = null)
	{
		$this->container = $container ?: new Container;
	}

	public function add($name, $formatter, $priority = 0)
	{
		$this->remove($name);

		if (is_string($formatter)) {
			$container = $this->container;
			$formatter = function () use ($container, $formatter) {
				$callable = array($container->make($formatter), 'format');
				$data = func_get_args();
				return call_user_func_array($callable, $data);
			};
		}

		$this->formatters[$name] = [$formatter, $priority];
	}

	public function remove($name)
	{
		unset($this->formatters[$name]);
	}

	public function format($text)
	{
		$sorted = [];

		foreach ($this->formatters as $array) {
			list($formatter, $priority) = $array;
			$sorted[$priority][] = $formatter;
		}

		ksort($sorted);

		foreach ($sorted as $formatters) {
			foreach ($formatters as $formatter) {
				$text = $formatter($text);
			}
		}

		return $text;
	}
}
