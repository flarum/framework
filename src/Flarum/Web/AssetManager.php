<?php namespace Flarum\Web;

use Illuminate\Filesystem\Filesystem;

class AssetManager {

	protected $css = [];
	protected $js = [];

	protected $publishPath;

	protected $files;

	public function __construct(Filesystem $files, $publishPath)
	{
		$this->files = $files;
		$this->publishPath = $publishPath;
	}

	protected function getPackageDir($package)
	{
		// TODO: First search vendor, then search workbench.
		// TODO: inject path.base
		return app('path.base').'/workbench/'.$package.'/dist/';
	}

	public function add($package, $files)
	{
		$packageDir = $this->getPackageDir($package);

		foreach ((array) $files as $file)
		{
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			switch ($ext)
			{
				case 'css':
					$this->css[] = 'packages/'.$package.'/'.$file;
					break;

				case 'js':
					$this->js[] = 'packages/'.$package.'/'.$file;
					break;
			}
		}
	}

	public function getCSSFiles()
	{
		// TODO: in a production environment, we would concat+minify all the CSS files together
		// (would probably need to check filemtimes etc.)

		// But in a development environment, we just copy all the css files to the public directory.
		// foreach ($this->css as $file)
		// {

		// }

		return $this->css;
	}

	public function getJSFiles()
	{
		return $this->js;
	}

	public function styles()
	{
		$output = '';

		foreach ($this->getCSSFiles() as $file)
		{
      		$output .= '<link rel="stylesheet" href="'.asset($file).'">'.PHP_EOL;
		}

		return $output;
	}

	public function scripts()
	{
		$output = '';

		foreach ($this->getJSFiles() as $file)
		{
      		$output .= '<script src="'.asset($file).'"></script>'.PHP_EOL;
		}

		return $output;
	}

}
