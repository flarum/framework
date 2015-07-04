<?php namespace Flarum\Core\Search;

use Illuminate\Contracts\Container\Container;
use LogicException;

/**
 * @todo This whole gambits thing needs way better documentation.
 */
class GambitManager
{
    /**
     * @var array
     */
    protected $gambits = [];

    /**
     * @var string
     */
    protected $fulltextGambit;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Add a gambit.
     *
     * @param string $gambit
     */
    public function add($gambit)
    {
        $this->gambits[] = $gambit;
    }

    /**
     * Apply gambits to a search, given a search query.
     *
     * @param Search $search
     * @param string $query
     */
    public function apply(Search $search, $query)
    {
        $query = $this->applyGambits($search, $query);

        if ($query) {
            $this->applyFulltext($search, $query);
        }
    }

    /**
     * Set the gambit to handle fulltext searching.
     *
     * @param string $gambit
     */
    public function setFulltextGambit($gambit)
    {
        $this->fulltextGambit = $gambit;
    }

    /**
     * Explode a search query into an array of bits.
     *
     * @param string $query
     * @return array
     */
    protected function explode($query)
    {
        return str_getcsv($query, ' ');
    }

    /**
     * @param Search $search
     * @param string $query
     * @return string
     */
    protected function applyGambits(Search $search, $query)
    {
        $bits = $this->explode($query);

        $gambits = array_map([$this->container, 'make'], $this->gambits);

        foreach ($bits as $k => $bit) {
            foreach ($gambits as $gambit) {
                if (! $gambit instanceof GambitInterface) {
                    throw new LogicException('Gambit ' . get_class($gambit)
                        . ' does not implement ' . GambitInterface::class);
                }

                if ($gambit->apply($search, $bit)) {
                    $search->addActiveGambit($gambit);
                    unset($bits[$k]);
                    break;
                }
            }
        }

        return implode(' ', $bits);
    }

    /**
     * @param Search $search
     * @param string $query
     */
    protected function applyFulltext(Search $search, $query)
    {
        if (! $this->fulltextGambit) {
            return;
        }

        $gambit = $this->container->make($this->fulltextGambit);

        $search->addActiveGambit($gambit);
        $gambit->apply($search, $query);
    }
}
