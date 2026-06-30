<?php

namespace App\Support\Query;

use Illuminate\Database\Eloquent\Builder;

class QueryPaginator
{
    public function paginate(
        Builder $query,
        array $filters,
        array $allowedSorts = [],
        array $searchable = []
    ) {
        if (!empty($filters['search']) && !empty($searchable)) {
            $query->where(function ($q) use ($filters, $searchable) {
                foreach ($searchable as $field) {
                    $q->orWhere($field, 'ILIKE', '%' . $filters['search'] . '%');
                }
            });
        }

        $sortBy = null;
        $sortDir = $filters['sort_dir'] ?? 'desc';

        if (!empty($filters['sort_by']) && in_array($filters['sort_by'], $allowedSorts)) {
            $sortBy = $filters['sort_by'];
        } else {
            $sortBy = 'id';
        }

        $query->orderBy($sortBy, $sortDir);


        return $query->paginate(
            $filters['per_page'] ?? 100
        );
    }
}
