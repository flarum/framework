<?php namespace Flarum\Core\Search;

interface SearchDriverInterface {

	// returns an array of matching conversation IDs
	public function results(SearchCriteria $criteria);

}
