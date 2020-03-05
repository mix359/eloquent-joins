<?php

/*

This file is part of Eloquent Joins.

Eloquent Joins is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Eloquent Joins is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Eloquent Joins.  If not, see <http://www.gnu.org/licenses/>.

*/

namespace EloquentJoins;

use EloquentJoins\Relations\BelongsToMany;
use EloquentJoins\Relations\HasManyThrough;
use EloquentJoins\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

trait ModelTrait
{
	/**
	 * Create a new Eloquent query builder for the model.
	 *
	 * @param \Illuminate\Database\Query\Builder $query
	 *
	 * @return EloquentBuilder|static
	 */
	public function newEloquentBuilder($query)
	{
		return new Builder($query);
	}


	/**
	 * Instantiate a new HasManyThrough relationship.
	 *
	 * @param  EloquentBuilder  $query
	 * @param  Model  $farParent
	 * @param  Model  $throughParent
	 * @param  string  $firstKey
	 * @param  string  $secondKey
	 * @param  string  $localKey
	 * @param  string  $secondLocalKey
	 * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
	 */
	protected function newHasManyThrough(EloquentBuilder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey)
	{
		return new HasManyThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
	}

	/**
	 * Instantiate a new HasOneThrough relationship.
	 *
	 * @param  EloquentBuilder  $query
	 * @param  Model  $farParent
	 * @param  Model  $throughParent
	 * @param  string  $firstKey
	 * @param  string  $secondKey
	 * @param  string  $localKey
	 * @param  string  $secondLocalKey
	 * @return \Illuminate\Database\Eloquent\Relations\HasOneThrough
	 */
	protected function newHasOneThrough(EloquentBuilder $query, Model $farParent, Model $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey)
	{
		return new HasOneThrough($query, $farParent, $throughParent, $firstKey, $secondKey, $localKey, $secondLocalKey);
	}

	//IS NEEDED?? REMOVE?
	// /**
	//  * Instantiate a new BelongsToMany relationship.
	//  *
	//  * @param  \Illuminate\Database\Eloquent\Builder  $query
	//  * @param  \Illuminate\Database\Eloquent\Model  $parent
	//  * @param  string  $table
	//  * @param  string  $foreignPivotKey
	//  * @param  string  $relatedPivotKey
	//  * @param  string  $parentKey
	//  * @param  string  $relatedKey
	//  * @param  string  $relationName
	//  * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
	//  */
	// protected function newBelongsToMany(Builder $query, Model $parent, $table, $foreignPivotKey, $relatedPivotKey,
	//                                     $parentKey, $relatedKey, $relationName = null)
	// {
	//     return new BelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
	// }
}
