@php
    $isSortActive = $sortColumn === $column;
@endphp
<th scope="col"{{ $numeric ? ' class="numeric"' : '' }} aria-sort="{{ $sortAria($column) }}">
    <a class="sort-link{{ $isSortActive ? ' is-active' : '' }}" href="{{ $sortUrl($column) }}">
        <span>{{ $label }}</span>
        <span class="sort-caret{{ $isSortActive ? ' is-active' : '' }}"
              aria-hidden="true">{{ $sortCaret($column) }}</span>
    </a>
</th>
