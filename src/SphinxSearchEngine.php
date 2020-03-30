<?php
/**
 * SphinxSearchEngine.php file
 */

namespace Fobia\Database\SphinxConnection;

use Foolz\SphinxQL\Exception\ConnectionException;
use Foolz\SphinxQL\Exception\DatabaseException;
use Foolz\SphinxQL\Match;
use Foolz\SphinxQL\SphinxQL;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Illuminate\Support\Arr;

/**
 * Class SphinxSearchEngine
 */
class SphinxSearchEngine extends Engine
{
    /**
     * A non-static connection for the current Facet object
     *
     * @var SphinxConnection
     */
    protected $connection;

    public function __construct(SphinxConnection $connection)
    {
        $this->connection = $connection;
    }
    /**
     * @return SphinxConnection
     */
    public function getCconnection(): SphinxConnection
    {
        return $this->connection;
    }

    /**
     * @inheritDoc
     */
    public function update($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $table = $models->first()->searchableAs();

        if ($this->usesSoftDelete($models->first()) && config('scout.soft_delete', false)) {
            $models->each->pushSoftDeleteMetadata();
        }

        $modelsRelations = [];
        $items = collect();

        while (!$models->isEmpty()) {
            $model = $models->shift();

            $array = $model->toSearchableArray();
            $softDeleted = Arr::get($model->scoutMetadata(), '__soft_deleted', null);
            if ($softDeleted !== null) {
                $array['__soft_deleted'] = $softDeleted;
            }

            // Первичный ключ id для Sphinx
            $array = array_merge($array, ['id' => $model->getScoutKey()]);

            $array = array_filter($array, function ($val) {
                return !is_null($val);
            });

            $items->add($array);

            // Загрузка новых отношений в колекции
            $relations = array_diff_key(Arr::dot($this->getRelations($model)), $modelsRelations);
            if (count($relations)) {
                $modelsRelations = array_merge($modelsRelations, $relations);
                $relations = array_keys($relations);

                $models->loadMissing(...$relations);
            }
        }

        $this->connection->query()->from($table)->replace($items->toArray());
    }

    public function delete($models)
    {
        if ($models->isEmpty()) {
            return;
        }

        $table = $models->first()->searchableAs();
        $ids = $models->map(static function ($model) {
            return $model->getScoutKey();
        })->toArray();

        $this->connection->query()->from($table)->delete($ids);
    }

    public function search(Builder $builder)
    {
        $query = $this->connection->query();
        return $this->executeQuery($query, $builder, 0, $builder->limit ?: $builder->model->getPerPage());
    }

    public function paginate(Builder $builder, $perPage, $page)
    {
        $query = $this->connection->query();

        $offset = ($page - 1) * $perPage;

        return $this->executeQuery($query, $builder, $offset, $perPage);
    }

    public function mapIds($results)
    {
        return collect($results)->only('id');
    }

    public function map(Builder $builder, $results, $model)
    {
        /** @var \Solarium\QueryType\Select\Result\Result $results */
        if (count($results->getDocuments()) === 0) {
            return Collection::make();
        }
        $docs = Collection::make($results->getDocuments());

        $models = $model->getScoutModelsByIds(
            $builder,
            $docs->pluck('id')->values()->all()
        )->keyBy(function ($model) {
            return $model->getScoutKey();
        });

        return $docs->map(function ($hit) use ($models) {
            if (isset($models[$hit['id']])) {
                return $models[$hit['id']];
            }
        })->filter()->values();

        // $models->each(function ($model) use ($docs) {
        //     /** @var \Illuminate\Database\Eloquent\Model $model */
        //     $solrDock = $docs->firstWhere('id', '=', $model->getScoutKey());
        //     $model->setRelation('solr', new Fluent($solrDock));
        // });
        //
        // return $models;
    }

    public function getTotalCount($results)
    {
        // TODO: Implement getTotalCount() method.
    }

    /**
     * @inheritdoc
     */
    public function flush($model)
    {
        $table = $model->searchableAs();
        $this->connection->select('TRUNCATE TABLE ' . $table);
    }

    // =========================================

    /**
     * Возвращает ключи загруженных отношений
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    protected function getRelations(\Illuminate\Database\Eloquent\Model $model)
    {
        $relations = collect($model->getRelations())->except('pivot')->all();

        foreach ($relations as $key => $model) {
            $relations[$key] = true;

            if ($model instanceof Collection) {
                $model = $model->first();
            }

            if ($model instanceof \Illuminate\Database\Eloquent\Model) {
                $relations[$key] = $this->getRelations($model);
                if (!count($relations[$key])) {
                    $relations[$key] = true;
                }
            }
        }

        return $relations;
    }

    /**
     * Execute Select command on the index.
     *
     * @param \Fobia\Database\SphinxConnection\Eloquent\Query\Builder $query
     * @param \Laravel\Scout\Builder $builder
     * @param int $offset
     * @param int $limit
     * @return \Solarium\QueryType\Select\Result\Result
     */
    protected function executeQuery(Eloquent\Query\Builder $query, Builder $builder, $offset = 0, $limit = null)
    {
        $table = $builder->index ?: $builder->model->searchableAs();
        $query->from($table);

        $conditions = [];
        if (!empty($builder->query)) {
            $searchQuery = $builder->query;

            if ($searchQuery instanceof SphinxQL) {
                try {
                    $searchQuery = $searchQuery->compileMatch();
                    $query->match($searchQuery);
                } catch (ConnectionException $e) {
                } catch (DatabaseException $e) {
                }
            } elseif ($searchQuery instanceof  Match) {
                $query->match($searchQuery);
            } elseif ($searchQuery instanceof \Illuminate\Database\Query\Expression) {
                $query->match($searchQuery->getValue());
            // } elseif ($searchQuery instanceof \Minimalcode\Search\Criteria) {
            //     $searchQuery = $searchQuery->getQuery();
            } else {
                if ($searchQuery && is_string($searchQuery)) {
                    $query->match('*', $searchQuery);
                }
            }
        }

        // $specChar = ['+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', '/', '\\', ];

        foreach ($builder->wheres as $key => $value) {
            if ($value instanceof \Illuminate\Database\Query\Expression) {
                $query->where($key, $value->__toString());
            } else {
                $query->where($key, $value);
            }
        }

        // if (count($conditions)) {
        //     $query->setQuery(implode(' ', $conditions));
        // }

        if (!is_null($limit)) {
            $query->offset($offset)
                ->limit($limit);
        }

        if (!empty($builder->callback)) {
            $callback = $builder->callback;
            $result = $callback($this->connection, $query, $conditions);
            if ($result instanceof Result) {
                return $result;
            }
        }

        return $query->get();
    }

    /**
     * Determine if the given model uses soft deletes.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return bool
     */
    protected function usesSoftDelete($model)
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model));
    }
}
