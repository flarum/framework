<?php namespace Flarum\Core\Search;

use Illuminate\Database\Query;

use Flarum\Core\Search\ConditionCollection;
use Flarum\Core\Search\ConditionNegate;
use Flarum\Core\Search\ConditionOr;
use Flarum\Core\Search\Conditions\ConditionComparison;
use Flarum\Core\Search\Conditions\ConditionNull;

class FulltextSearchDriver implements SearchDriverInterface {
	
	protected $table;

	public function __construct($table)
	{
		$this->table = $table;
		// inject db connection?
		// pass primary key name?
	}

	public function results(SearchCriteria $criteria)
	{
		$query = DB::table($this->table);

		$this->parseConditions($criteria->conditions, $query);

		return $query->get('id');
	}

	protected function parseConditions(ConditionCollection $conditions, Query $query)
	{
		foreach ($conditions as $condition)
		{
			if ($condition instanceof ConditionOr)
			{
				$query->orWhere(function($query)
				{
					$this->parseConditions($condition->conditions, $query);
				})
			}
			elseif ($condition instanceof ConditionComparison)
			{
				// etc
			}
		}
	}
}
