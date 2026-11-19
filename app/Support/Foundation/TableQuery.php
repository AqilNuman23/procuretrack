<?php

namespace App\Support\Foundation;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TableQuery
{
    public const PAGE_SIZES = [10, 25, 50, 100];

    public function __construct(private Request $request, private string $prefix = '') {}

    public function key(string $name): string
    {
        return $this->prefix === '' ? $name : $this->prefix.'_'.$name;
    }

    public function value(string $name, array $allowed, string $default = ''): string
    {
        $value = $this->request->query($this->key($name));

        return in_array($value, $allowed, true) ? $value : $default;
    }

    public function search(Builder $query, array $columns, array $relations = []): Builder
    {
        $value = $this->request->query($this->key('search'));
        if (! is_string($value) || trim($value) === '') {
            return $query;
        }

        $pattern = '%'.mb_substr(trim($value), 0, 150).'%';

        return $query->where(function (Builder $matches) use ($columns, $relations, $pattern) {
            if ($columns) {
                $matches->whereAny($columns, 'like', $pattern);
            }
            foreach ($relations as $relation => $fields) {
                $matches->orWhereHas($relation, fn (Builder $related) => $related->whereAny($fields, 'like', $pattern));
            }
        });
    }

    public function pageSize(): int
    {
        return (int) $this->value('per_page', array_map('strval', self::PAGE_SIZES), '10');
    }

    public function paginate(Builder $query): Paginator
    {
        return $query->simplePaginate($this->pageSize(), ['*'], $this->key('page'))->withQueryString();
    }
}
