<?php

namespace Goodport\QueryDuplicates\Http\Requests;

use Goodport\QueryDuplicates\Aggregator\QueryGroup;
use Goodport\QueryDuplicates\Repository\QueryDuplicateRepository;
use Goodport\QueryDuplicates\Support\HttpMethods;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class QueryDuplicateIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', Rule::in([
                QueryGroup::TYPE__DUPLICATE,
                QueryGroup::TYPE__N_PLUS_ONE,
            ])],
            'method' => ['nullable', 'string', Rule::in(HttpMethods::ALL)],
            'sort' => ['nullable', 'string', Rule::in(QueryDuplicateRepository::SORTABLE)],
            'direction' => ['nullable', 'string', Rule::in(QueryDuplicateRepository::DIRECTIONS)],
        ];
    }

    /**
     * Drops unknown sort values instead of rejecting the request, so a stale
     * link falls back to the default order without a 500 or a SQL injection.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'sort' => $this->whitelisted('sort', QueryDuplicateRepository::SORTABLE),
            'direction' => $this->whitelisted('direction', QueryDuplicateRepository::DIRECTIONS),
        ]);
    }

    /**
     * Returns the input value when it is one of the allowed strings.
     *
     * @param  list<string>  $allowed
     */
    private function whitelisted(string $key, array $allowed): ?string
    {
        $value = $this->input($key);

        return is_string($value) && in_array($value, $allowed, true) ? $value : null;
    }
}
