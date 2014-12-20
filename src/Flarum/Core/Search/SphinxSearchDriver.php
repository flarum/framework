<?php namespace Flarum\Core\Search;

use Sphinx\SphinxClient;

use Flarum\Core\Search\ConditionCollection;
use Flarum\Core\Search\ConditionNegate;
use Flarum\Core\Search\ConditionOr;
use Flarum\Core\Search\Conditions\ConditionComparison;
use Flarum\Core\Search\Conditions\ConditionNull;

class SphinxSearchDriver implements SearchDriverInterface {
	
	protected $client;

	public function __construct(SphinxClient $client, $index)
	{
		$this->client = $client;
		$this->index = $index;
	}

	public function results(SearchCriteria $criteria)
	{
		foreach ($query->conditions as $condition)
		{
			if ($condition instanceof ConditionOr)
			{
				// $search->setSelect("*, IF(code = 1 OR productid = 2, 1,0) AS filter");
				// $->setFilter('filter',array(1));
			}
		}

		// etc
	}
}
