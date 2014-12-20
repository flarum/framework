<?php namespace Flarum\Core\Search;

class Tokenizer {

	protected $query;

	public function __construct($query)
	{
		$this->query = $query;
	}

	public function tokenize()
	{
		return $this->query ? [$this->query] : [];
	}

}
