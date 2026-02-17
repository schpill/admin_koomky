<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

class SegmentFilterEngine
{
    /**
     * @param  array<int, mixed>|array<string, mixed>  $filters
     * @return Builder<Contact>
     */
    public function apply(User $user, array $filters): Builder
    {
        $query = Contact::query()->whereHas('client', function (Builder $clientQuery) use ($user): void {
            $clientQuery->where('user_id', $user->id);
        });

        $normalizedFilters = $this->normalizeFilters($filters);
        $groups = $normalizedFilters['groups'];

        if ($groups === []) {
            return $query;
        }

        $groupBoolean = $normalizedFilters['group_boolean'];
        $criteriaBoolean = $normalizedFilters['criteria_boolean'];

        foreach ($groups as $groupIndex => $group) {
            if (! is_array($group)) {
                throw new InvalidArgumentException('Each filter group must be an array.');
            }

            $criteria = $group['criteria'] ?? null;
            if (! is_array($criteria) || $criteria === []) {
                continue;
            }

            $method = $groupIndex === 0 ? 'where' : ($groupBoolean === 'or' ? 'orWhere' : 'where');

            $query->{$method}(function (Builder $groupQuery) use ($criteria, $criteriaBoolean): void {
                foreach ($criteria as $criteriaIndex => $criterion) {
                    if (! is_array($criterion)) {
                        throw new InvalidArgumentException('Each criterion must be an array.');
                    }

                    $criteriaMethod = $criteriaIndex === 0 ? 'where' : ($criteriaBoolean === 'or' ? 'orWhere' : 'where');

                    $groupQuery->{$criteriaMethod}(function (Builder $criterionQuery) use ($criterion): void {
                        $this->applyCriterion($criterionQuery, $criterion);
                    });
                }
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $criterion
     * @param  Builder<Contact>  $query
     */
    private function applyCriterion(Builder $query, array $criterion): void
    {
        $type = (string) ($criterion['type'] ?? '');

        match ($type) {
            'tag' => $this->applyTagCriterion($query, $criterion),
            'last_interaction' => $this->applyLastInteractionCriterion($query, $criterion),
            'project_status' => $this->applyProjectStatusCriterion($query, $criterion),
            'revenue' => $this->applyRevenueCriterion($query, $criterion),
            'location' => $this->applyLocationCriterion($query, $criterion),
            'created_at' => $this->applyCreatedAtCriterion($query, $criterion),
            'custom', 'custom_field' => $this->applyCustomFieldCriterion($query, $criterion),
            default => throw new InvalidArgumentException('Unsupported filter type: '.$type),
        };
    }

    /**
     * @param  array<string, mixed>  $criterion
     * @param  Builder<Contact>  $query
     */
    private function applyTagCriterion(Builder $query, array $criterion): void
    {
        $operator = (string) ($criterion['operator'] ?? '');
        $value = $criterion['value'] ?? null;

        if ($operator === 'equals' && is_string($value)) {
            $query->whereHas('client.tags', function (Builder $tagQuery) use ($value): void {
                $tagQuery->where('name', $value);
            });

            return;
        }

        if ($operator === 'in' && is_array($value)) {
            /** @var list<string> $values */
            $values = array_values(array_filter($value, 'is_string'));

            $query->whereHas('client.tags', function (Builder $tagQuery) use ($values): void {
                $tagQuery->whereIn('name', $values);
            });

            return;
        }

        throw new InvalidArgumentException('Invalid tag filter operator or value.');
    }

    /**
     * @param  array<string, mixed>  $criterion
     * @param  Builder<Contact>  $query
     */
    private function applyLastInteractionCriterion(Builder $query, array $criterion): void
    {
        $operator = (string) ($criterion['operator'] ?? '');
        $value = $criterion['value'] ?? null;

        if (($operator === 'before' || $operator === 'after') && is_string($value)) {
            $comparison = $operator === 'before' ? '<=' : '>=';

            $query->whereHas('client.activities', function (Builder $activityQuery) use ($comparison, $value): void {
                $activityQuery->whereDate('created_at', $comparison, $value);
            });

            return;
        }

        if ($operator === 'older_than_months' && is_numeric($value)) {
            $months = max(0, (int) $value);
            $cutoff = now()->subMonths($months);

            $query->whereHas('client.activities', function (Builder $activityQuery) use ($cutoff): void {
                $activityQuery->where('created_at', '<=', $cutoff);
            });

            return;
        }

        throw new InvalidArgumentException('Invalid last_interaction filter operator or value.');
    }

    /**
     * @param  array<string, mixed>  $criterion
     * @param  Builder<Contact>  $query
     */
    private function applyProjectStatusCriterion(Builder $query, array $criterion): void
    {
        $operator = (string) ($criterion['operator'] ?? '');
        $value = $criterion['value'] ?? null;

        if ($operator === 'equals' && is_string($value)) {
            $query->whereHas('client.projects', function (Builder $projectQuery) use ($value): void {
                $projectQuery->where('status', $value);
            });

            return;
        }

        if ($operator === 'in' && is_array($value)) {
            /** @var list<string> $values */
            $values = array_values(array_filter($value, 'is_string'));

            $query->whereHas('client.projects', function (Builder $projectQuery) use ($values): void {
                $projectQuery->whereIn('status', $values);
            });

            return;
        }

        throw new InvalidArgumentException('Invalid project_status filter operator or value.');
    }

    /**
     * @param  array<string, mixed>  $criterion
     * @param  Builder<Contact>  $query
     */
    private function applyRevenueCriterion(Builder $query, array $criterion): void
    {
        $operator = (string) ($criterion['operator'] ?? '');
        $value = $criterion['value'] ?? null;

        if (! is_numeric($value)) {
            throw new InvalidArgumentException('Revenue filter value must be numeric.');
        }

        $comparison = match ($operator) {
            'gt', '>' => '>',
            'gte', '>=' => '>=',
            'lt', '<' => '<',
            'lte', '<=' => '<=',
            'eq', '=' => '=',
            default => throw new InvalidArgumentException('Invalid revenue filter operator.'),
        };

        $amount = (float) $value;

        $query->whereHas('client', function (Builder $clientQuery) use ($comparison, $amount): void {
            $clientQuery->whereRaw(
                '(select coalesce(sum(total), 0) from invoices where invoices.client_id = clients.id) '.$comparison.' ?',
                [$amount]
            );
        });
    }

    /**
     * @param  array<string, mixed>  $criterion
     * @param  Builder<Contact>  $query
     */
    private function applyLocationCriterion(Builder $query, array $criterion): void
    {
        $field = (string) ($criterion['field'] ?? 'city');
        $operator = (string) ($criterion['operator'] ?? '');
        $value = $criterion['value'] ?? null;

        if (! in_array($field, ['city', 'country'], true) || ! is_string($value)) {
            throw new InvalidArgumentException('Invalid location filter field or value.');
        }

        if ($operator === 'equals') {
            $query->whereHas('client', function (Builder $clientQuery) use ($field, $value): void {
                $clientQuery->where($field, $value);
            });

            return;
        }

        if ($operator === 'contains') {
            $query->whereHas('client', function (Builder $clientQuery) use ($field, $value): void {
                $clientQuery->where($field, 'like', '%'.$value.'%');
            });

            return;
        }

        throw new InvalidArgumentException('Invalid location filter operator.');
    }

    /**
     * @param  array<string, mixed>  $criterion
     * @param  Builder<Contact>  $query
     */
    private function applyCreatedAtCriterion(Builder $query, array $criterion): void
    {
        $operator = (string) ($criterion['operator'] ?? '');
        $value = $criterion['value'] ?? null;

        if (! is_string($value)) {
            throw new InvalidArgumentException('Created at filter value must be a date string.');
        }

        $comparison = match ($operator) {
            'before' => '<=',
            'after' => '>=',
            default => throw new InvalidArgumentException('Invalid created_at filter operator.'),
        };

        $query->whereDate('created_at', $comparison, $value);
    }

    /**
     * @param  array<string, mixed>  $criterion
     * @param  Builder<Contact>  $query
     */
    private function applyCustomFieldCriterion(Builder $query, array $criterion): void
    {
        $field = (string) ($criterion['field'] ?? '');
        $operator = (string) ($criterion['operator'] ?? '');

        if (! in_array($field, ['email', 'phone'], true)) {
            throw new InvalidArgumentException('Invalid custom field.');
        }

        if ($operator === 'exists') {
            $query->whereNotNull($field)->where($field, '!=', '');

            return;
        }

        if ($operator === 'not_exists') {
            $query->where(function (Builder $nestedQuery) use ($field): void {
                $nestedQuery->whereNull($field)->orWhere($field, '');
            });

            return;
        }

        throw new InvalidArgumentException('Invalid custom field operator.');
    }

    /**
     * @param  array<int, mixed>|array<string, mixed>  $filters
     * @return array{groups: array<int, mixed>, group_boolean: string, criteria_boolean: string}
     */
    private function normalizeFilters(array $filters): array
    {
        if (array_is_list($filters)) {
            return [
                'groups' => $filters,
                'group_boolean' => 'and',
                'criteria_boolean' => 'or',
            ];
        }

        $groups = $filters['groups'] ?? [];
        if (! is_array($groups)) {
            throw new InvalidArgumentException('Filters groups must be an array.');
        }

        return [
            'groups' => array_values($groups),
            'group_boolean' => $this->normalizeBoolean($filters['group_boolean'] ?? 'and', 'group_boolean'),
            'criteria_boolean' => $this->normalizeBoolean($filters['criteria_boolean'] ?? 'or', 'criteria_boolean'),
        ];
    }

    private function normalizeBoolean(mixed $value, string $field): string
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('Invalid '.$field.' value.');
        }

        $normalized = strtolower($value);
        if (! in_array($normalized, ['and', 'or'], true)) {
            throw new InvalidArgumentException('Invalid '.$field.' value.');
        }

        return $normalized;
    }
}
