<?php namespace Flarum\Web;

use Illuminate\Filesystem\Filesystem;

class AssetManager
{
	protected $css = [];
	protected $js = [];

	protected $publishPath;

	protected $files;

	public function __construct(Filesystem $files, $publishPath)
	{
		$this->files = $files;
		$this->publishPath = $publishPath;
	}

	public function add($files)
	{
		foreach ((array) $files as $file)
		{
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			switch ($ext)
			{
				case 'css':
					$this->css[] = $file;
					break;

				case 'js':
					$this->js[] = $file;
					break;
			}
		}
	}

    protected function getAssetDirectory()
    {
        $dir = $this->publishPath.'/flarum';
        if (! $this->files->isDirectory($dir)) {
            $this->files->makeDirectory($dir);
        }
        return $dir;
    }

	public function getCSSFiles()
	{
		// TODO: in a production environment, we would concat+minify all the CSS files together
		// (would probably need to check filemtimes etc.)

		// But in a development environment, we just copy all the css files to the public directory.
        $css = [];
        $dir = $this->getAssetDirectory();
		foreach ($this->css as $file)
		{
            $basename = pathinfo($file, PATHINFO_BASENAME);
            $target = $dir.'/'.$basename;
            $this->files->copy($file, $target);
            $css[] = str_replace($this->publishPath, '', $target);
		}

		return $css;
	}

	public function getJSFiles()
	{
		$js = [];
        $dir = $this->getAssetDirectory();
        foreach ($this->js as $file)
        {
            $basename = pathinfo($file, PATHINFO_BASENAME);
            $target = $dir.'/'.$basename;
            $this->files->copy($file, $target);
            $js[] = str_replace($this->publishPath, '', $target);
        }

        return $js;
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
