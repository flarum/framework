<?php namespace Flarum\Core\Search;

use Illuminate\Contracts\Container\Container;

class GambitManager
{
    protected $gambits = [];

    protected $fulltextGambit;

    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function add($gambit)
    {
        $this->gambits[] = $gambit;
    }

    public function apply($string, $searcher)
    {
        $string = $this->applyGambits($string, $searcher);

        if ($string) {
            $this->applyFulltext($string, $searcher);
        }
    }

    public function setFulltextGambit($gambit)
    {
        $this->fulltextGambit = $gambit;
    }

    protected function bits($string)
    {
        return str_getcsv($string, ' ');
    }

    protected function applyGambits($string, $searcher)
    {
        $bits = $this->bits($string);

        $gambits = array_map([$this->container, 'make'], $this->gambits);

        foreach ($bits as $k => $bit) {
            foreach ($gambits as $gambit) {
                if ($gambit->apply($bit, $searcher)) {
                    $searcher->addActiveGambit($gambit);
                    unset($bits[$k]);
                    break;
                }
            }
        }

        return implode(' ', $bits);
    }

    protected function applyFulltext($string, $searcher)
    {
        if (! $this->fulltextGambit) {
            return;
        }

        $gambit = $this->container->make($this->fulltextGambit);

        $searcher->addActiveGambit($gambit);
        $gambit->apply($string, $searcher);
    }
}
