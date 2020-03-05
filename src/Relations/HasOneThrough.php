<?php

namespace EloquentJoins\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough as BaseHasOneThrough;

class HasOneThrough extends BaseHasOneThrough {

	/**
	 * @return Model
	 */
	public function getThroughParent(): Model {
		return $this->throughParent;
	}

}