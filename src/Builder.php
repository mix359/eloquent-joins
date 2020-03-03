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

use Illuminate\Database\Eloquent\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Builder extends BaseBuilder
{
	/**
	 * The relationships that have been joined.
	 *
	 * @var array
	 */
	protected $joined = [];

	protected $relationNameToTable = [];

	/**
	 * Get the hydrated models without eager loading.
	 *
	 * @param array $columns
	 *
	 * @return \Illuminate\Database\Eloquent\Model[]
	 */
	public function getModels($columns = ['*'])
	{
		$results = $this->query->get($columns)->all();

		$connection = $this->model->getConnectionName();

		// Check for joined relations
		if (empty($this->joined)) {
			return $this->model->hydrate($results, $connection)->all();
		}

		$models = [];
		foreach ($results as $key => $result) {
			if(!isset($result->{$this->model->getKeyName()})) {
				continue;
			}

			if(!isset($models[$result->{$this->model->getKeyName()}])) {
				$newModel = true;
				$modelValues = [];
			} else {
				$currentModel = &$models[$result->{$this->model->getKeyName()}];
				$newModel = false;
			}

			$relationValues = [];
			foreach ($result as $column => $value) {
				if(strpos($column, ".") !== false) {
					Arr::set($relationValues, $column, $value);
				} elseif($newModel) {
					$modelValues[$column] = $value;
				}
			}

			if($newModel) {
				$models[$result->{$this->model->getKeyName()}] = $currentModel = $this->model->newFromBuilder($modelValues);
				unset($modelValues);
			}

			unset($newModel);

			foreach ($this->joined as $fullRelationName => $relationPath) {
				$modelForCurrentPath = $currentModel;
				foreach ($relationPath as $i => $currentRelationName) {
					$currentRelationPath = implode(".", array_slice($relationPath, 0, $i + 1));
					if(!$modelForCurrentPath->relationLoaded($currentRelationName)) {
						$relation = $modelForCurrentPath->newQuery()->getRelation($currentRelationName);
						$newRelationModel = $relation->getRelated()->newFromBuilder(Arr::pull($relationValues, $currentRelationPath), $connection);
						if($relation instanceof BelongsToMany || $relation instanceof HasOneOrMany || $relation instanceof HasManyThrough ) {
							$modelForCurrentPath->setRelation($currentRelationName, $relation->getRelated()->newCollection([$newRelationModel]));
						} else {
							$modelForCurrentPath->setRelation($currentRelationName, $newRelationModel);
						}
						$modelForCurrentPath = $newRelationModel;
					} elseif ($modelForCurrentPath->getRelation($currentRelationName) instanceof Collection) {
						$relation = $modelForCurrentPath->newQuery()->getRelation($currentRelationName);
						$relationModelKey = Arr::pull($relationValues, $currentRelationPath.".".$relation->getRelated()->getKeyName());
						if(!$modelForCurrentPath->getRelation($currentRelationName)->contains($relationModelKey)) {
							$newRelationModel = $relation->getRelated()->newFromBuilder(Arr::pull($relationValues, $currentRelationPath), $connection);
							$modelForCurrentPath->getRelation($currentRelationName)->add($newRelationModel);
							$modelForCurrentPath = $newRelationModel;
						} else {
							$modelForCurrentPath = $modelForCurrentPath->getRelation($currentRelationName)->get($relationModelKey);
						}

					} else {
						$modelForCurrentPath = $modelForCurrentPath->getRelation($currentRelationName);
					}
				}
			}
		}

		return array_values($models);
	}

	/**
	 * Add a join clause to the query.
	 *
	 * @param string $fullRelationName
	 * @param string $type
	 * @param bool   $where
	 * @param bool $renameTableAsRelation
	 *
	 * @return $this
	 */
	public function joinRelationship($fullRelationName, $type = 'inner', $where = false, $renameTableAsRelation = true)
	{
		$relationsName = explode(".", $fullRelationName);
		$relationsCount = count($relationsName);
		if($relationsCount < 1) {
			return $this;
		}

		$relatedQueryBuilder = $this;
		for($i=0; $i < $relationsCount; $i++) {
			$relationPath = array_slice($relationsName, 0, $i + 1);
			$relationName = implode(".", $relationPath);
			$currentRelationName = $relationsName[$i];

			if(isset($this->joined[$relationName])) {
				continue;
			}

			$relation = $relatedQueryBuilder->getRelation($currentRelationName);
            if($relation->getParent()->getConnection() !== $relation->getRelated()->getConnection()) {
                return $this;
            }

			$relatedTableName = $this->relationNameToTable[$relationName] = $relation->getRelated()->getTable();
//			if($renameTableAsRelation && $relatedTableName != $relationName) {
//				$relatedTableNameAs .= " as ".$relationName;
//				$relatedTableName = $relationName;
//			}

			if ($relation instanceof BelongsTo) {
				$this->query->join(
					$relatedTableName,
					$relatedQueryBuilder->model->getTable().'.'.$relation->getForeignKeyName(),
					'=',
					$relatedTableName.'.'.$relation->getOwnerKeyName(),
					$type,
					$where
				);
			} elseif ($relation instanceof BelongsToMany) {
				$this->query->join(
					$relation->getTable(),
					$relation->getQualifiedParentKeyName(),
					'=',
					$relation->getForeignKeyName(),
					$type,
					$where
				);

				$this->query->join(
					$relatedTableName,
					$relatedTableName.'.'.$relation->getRelated()->getKeyName(),
					'=',
					$relation->getOwnerKey(),
					$type,
					$where
				);
			} else {
				$this->query->join(
					$relatedTableName,
					$relation->getQualifiedParentKeyName(),
					'=',
					$relation->getQualifiedForeignKeyName(),
					$type,
					$where
				);
			}

			if($i != $relationsCount) {
				$relatedQueryBuilder = $relation->getRelated()->newQuery();
			}

			$this->joined[$relationName] = $relationPath;
		}

		if(isset($relation) && $relation instanceof Relation && $relation !== $this) {
			$relation_columns = $this->query
				->getConnection()
				->getSchemaBuilder()
				->getColumnListing($relation->getRelated()->getTable());

			array_walk($relation_columns, function (&$column) use ($relatedTableName, $relationName) {
				$column = $relatedTableName.'.'.$column.($relatedTableName != $relationName ? ' as '.$relationName.'.'.$column : '');
			});

			$this->query->addSelect(array_merge([$this->model->getTable().'.*'], $relation_columns));
		}

		return $this;
	}

	/**
	 * Add a "join where" clause to the query.
	 *
	 * @param string $relation
	 * @param string $type
	 *
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function joinRelationshipWhere($relation, $type = 'inner')
	{
		return $this->joinRelationship($relation, $type, true);
	}

	/**
	 * Add a left join to the query.
	 *
	 * @param string $relation
	 *
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function leftJoinRelationship($relation)
	{
		return $this->joinRelationship($relation, 'left');
	}

	/**
	 * Add a "join where" clause to the query.
	 *
	 * @param string $relation
	 *
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function leftJoinRelationshipWhere($relation)
	{
		return $this->joinRelationshipWhere($relation, 'left');
	}

	/**
	 * Add a right join to the query.
	 *
	 * @param string $relation
	 *
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function rightRelationshipJoin($relation)
	{
		return $this->joinRelationship($relation, 'right');
	}

	/**
	 * Add a "right join where" clause to the query.
	 *
	 * @param string $relation
	 *
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function rightJoinRelationshipWhere($relation)
	{
		return $this->joinRelationshipWhere($relation, 'right');
	}

	public function getTableFromRelationName($relationName) {
		return $this->relationNameToTable[$relationName] ?? null;
	}
}
