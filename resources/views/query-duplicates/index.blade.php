<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <title>{{ trans('query-duplicates::query-duplicates.title') }}</title>
    <style>
        :root {
            color-scheme: light dark;

            --qd-sans: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            --qd-mono: ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, "Liberation Mono", monospace;

            --qd-bg: #f3f5f2;
            --qd-surface: #ffffff;
            --qd-surface-alt: #f7f9f6;
            --qd-border: #dce2dc;
            --qd-border-strong: #c5cfc8;
            --qd-ink: #1a2621;
            --qd-muted: #5f6d66;
            --qd-faint: #8a978f;

            --qd-accent: #0c6b56;
            --qd-accent-hover: #095544;
            --qd-accent-ink: #ffffff;
            --qd-accent-soft: #e3f2ec;
            --qd-focus: #0c6b56;

            --qd-danger: #a4372a;
            --qd-danger-soft: #fbe9e6;
            --qd-success: #116b4f;
            --qd-success-soft: #e4f4ec;
            --qd-warning: #8a5a12;
            --qd-warning-soft: #fbefd9;
            --qd-violet: #663a9e;
            --qd-violet-soft: #f1e9fb;

            --qd-head-bg: #1d2b25;
            --qd-head-ink: #e9f1ed;
            --qd-head-muted: #a9bcb3;
            --qd-head-border: #33473e;
            --qd-head-accent: #7ee7c4;

            --qd-code-bg: #f4f7f3;
            --qd-code-ink: #24332c;
            --qd-code-border: #e3e9e4;
            --qd-row-hover: #f2f8f4;
            --qd-shadow: 0 1px 2px rgba(20, 40, 30, 0.06), 0 18px 40px -32px rgba(20, 40, 30, 0.55);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --qd-bg: #0c1210;
                --qd-surface: #121a16;
                --qd-surface-alt: #16201b;
                --qd-border: #25322c;
                --qd-border-strong: #35463e;
                --qd-ink: #e6eee9;
                --qd-muted: #9aaca2;
                --qd-faint: #74857b;

                --qd-accent: #66d7b2;
                --qd-accent-hover: #8be6c8;
                --qd-accent-ink: #0b1512;
                --qd-accent-soft: #16352b;
                --qd-focus: #66d7b2;

                --qd-danger: #ff9c8e;
                --qd-danger-soft: #3a1d18;
                --qd-success: #7fe0b9;
                --qd-success-soft: #143329;
                --qd-warning: #efc06a;
                --qd-warning-soft: #382b12;
                --qd-violet: #caa3ff;
                --qd-violet-soft: #2c1e42;

                --qd-head-bg: #0f1814;
                --qd-head-ink: #e6eee9;
                --qd-head-muted: #8fa79c;
                --qd-head-border: #263830;
                --qd-head-accent: #7ee7c4;

                --qd-code-bg: #0f1814;
                --qd-code-ink: #dbe6e0;
                --qd-code-border: #22312a;
                --qd-row-hover: #16211c;
                --qd-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
            }
        }

        * {
            box-sizing: border-box;
        }

        ::selection {
            background: var(--qd-accent-soft);
            color: var(--qd-ink);
        }

        body {
            margin: 0;
            padding: 36px 24px 64px;
            background: var(--qd-bg);
            color: var(--qd-ink);
            font-family: var(--qd-sans);
            font-size: 14px;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }

        a,
        button,
        input,
        select {
            font-family: inherit;
        }

        a:focus-visible,
        button:focus-visible,
        input:focus-visible,
        select:focus-visible {
            outline: 2px solid var(--qd-focus);
            outline-offset: 2px;
        }

        .page {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
        }

        /* Header */

        .masthead {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .masthead-copy {
            min-width: 0;
        }

        .title {
            margin: 0;
            font-size: clamp(1.55rem, 1.2rem + 1.1vw, 1.95rem);
            font-weight: 650;
            letter-spacing: -0.025em;
            line-height: 1.15;
        }

        .subtitle {
            max-width: 66ch;
            margin: 8px 0 0;
            color: var(--qd-muted);
            font-size: 13.5px;
        }

        .storage-path {
            max-width: 90ch;
            margin: 6px 0 0;
            color: var(--qd-faint);
            font-family: var(--qd-mono);
            font-size: 11.5px;
            overflow-wrap: anywhere;
        }

        .clear-form {
            margin: 0;
            flex: none;
        }

        /* Buttons and controls */

        .button {
            appearance: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 38px;
            padding: 8px 14px;
            border: 1px solid var(--qd-border);
            border-radius: 8px;
            background: var(--qd-surface);
            color: var(--qd-ink);
            font: inherit;
            font-size: 13px;
            font-weight: 550;
            line-height: 1.2;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 120ms ease, border-color 120ms ease, color 120ms ease;
        }

        .button:hover {
            border-color: var(--qd-accent);
            color: var(--qd-accent);
        }

        .button-primary {
            border-color: var(--qd-accent);
            background: var(--qd-accent);
            color: var(--qd-accent-ink);
        }

        .button-primary:hover {
            border-color: var(--qd-accent-hover);
            background: var(--qd-accent-hover);
            color: var(--qd-accent-ink);
        }

        .button-danger {
            color: var(--qd-danger);
        }

        .button-danger:hover {
            border-color: var(--qd-danger);
            background: var(--qd-danger-soft);
            color: var(--qd-danger);
        }

        .button .icon {
            width: 15px;
            height: 15px;
        }

        .link-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 10px;
            border-radius: 8px;
            color: var(--qd-muted);
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: color 120ms ease, background-color 120ms ease;
        }

        .link-button:hover {
            background: var(--qd-surface-alt);
            color: var(--qd-ink);
            text-decoration: underline;
            text-underline-offset: 3px;
        }

        /* Notices */

        .notice,
        .setup {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 18px;
            padding: 14px 16px;
            border: 1px solid var(--qd-border);
            border-radius: 10px;
            background: var(--qd-surface);
        }

        .notice {
            border-left: 3px solid var(--qd-success);
            background: var(--qd-success-soft);
        }

        .setup {
            border-left: 3px solid var(--qd-warning);
            background: var(--qd-warning-soft);
        }

        .notice .icon,
        .setup .icon {
            flex: none;
            width: 18px;
            height: 18px;
            margin-top: 1px;
        }

        .notice .icon {
            color: var(--qd-success);
        }

        .setup .icon {
            color: var(--qd-warning);
        }

        .setup-title {
            margin: 0 0 3px;
            font-size: 14.5px;
            font-weight: 650;
            letter-spacing: -0.01em;
        }

        .setup-text {
            margin: 0;
            color: var(--qd-ink);
            font-size: 13.5px;
        }

        .setup-reason {
            margin: 6px 0 0;
            color: var(--qd-muted);
            font-family: var(--qd-mono);
            font-size: 12px;
            overflow-wrap: anywhere;
        }

        .setup-hint {
            margin: 6px 0 0;
            color: var(--qd-muted);
            font-size: 12.5px;
        }

        /* Workbench */

        .workbench {
            overflow: hidden;
            border: 1px solid var(--qd-border);
            border-radius: 14px;
            background: var(--qd-surface);
            box-shadow: var(--qd-shadow);
        }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            align-items: flex-end;
            padding: 16px;
            border-bottom: 1px solid var(--qd-border);
            background: var(--qd-surface-alt);
        }

        .field {
            display: flex;
            flex: 0 1 190px;
            flex-direction: column;
            gap: 6px;
            min-width: 0;
        }

        .field-grow {
            flex: 1 1 320px;
        }

        .label {
            color: var(--qd-muted);
            font-size: 12px;
            font-weight: 600;
            line-height: 1.2;
        }

        .input-wrap {
            position: relative;
        }

        .input-icon {
            position: absolute;
            top: 50%;
            left: 12px;
            width: 16px;
            height: 16px;
            color: var(--qd-faint);
            pointer-events: none;
            transform: translateY(-50%);
        }

        .input,
        .select {
            width: 100%;
            min-height: 40px;
            padding: 8px 11px;
            border: 1px solid var(--qd-border);
            border-radius: 8px;
            background: var(--qd-surface);
            color: var(--qd-ink);
            font: inherit;
            font-size: 13px;
            transition: border-color 120ms ease, box-shadow 120ms ease;
        }

        .input-search {
            padding-left: 37px;
        }

        .input::placeholder {
            color: var(--qd-faint);
        }

        .input:hover,
        .select:hover {
            border-color: var(--qd-border-strong);
        }

        .input:focus,
        .select:focus {
            outline: none;
            border-color: var(--qd-accent);
            box-shadow: 0 0 0 3px var(--qd-accent-soft);
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-left: auto;
            padding-bottom: 1px;
        }

        /* Table */

        .table-scroll {
            overflow-x: auto;
            scrollbar-color: var(--qd-border-strong) transparent;
            -webkit-overflow-scrolling: touch;
        }

        .table-scroll:focus-visible {
            outline: 2px solid var(--qd-focus);
            outline-offset: -2px;
        }

        table {
            width: 100%;
            min-width: 1180px;
            border-collapse: collapse;
        }

        caption {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
            border: 0;
        }

        thead th {
            padding: 10px 12px;
            border-bottom: 1px solid var(--qd-head-border);
            background: var(--qd-head-bg);
            color: var(--qd-head-muted);
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-align: left;
            white-space: nowrap;
        }

        thead th:first-child {
            padding-left: 16px;
        }

        thead th:last-child {
            padding-right: 16px;
            text-align: center;
        }

        thead th.numeric {
            text-align: right;
        }

        .sort-link {
            display: flex;
            width: 100%;
            align-items: center;
            gap: 6px;
            color: inherit;
            font: inherit;
            text-decoration: none;
            transition: color 120ms ease;
        }

        thead th.numeric .sort-link {
            justify-content: flex-end;
        }

        .sort-link:hover {
            color: var(--qd-head-ink);
        }

        .sort-link:focus-visible {
            outline-color: var(--qd-head-accent);
        }

        .sort-link.is-active {
            color: var(--qd-head-ink);
        }

        .sort-caret {
            color: var(--qd-head-muted);
            font-family: var(--qd-sans);
            font-size: 11px;
            line-height: 1;
            opacity: 0.65;
        }

        .sort-caret.is-active {
            color: var(--qd-head-accent);
            opacity: 1;
        }

        tbody td {
            padding: 12px;
            border-bottom: 1px solid var(--qd-border);
            background: transparent;
            vertical-align: top;
        }

        tbody td:first-child {
            padding-left: 16px;
        }

        tbody td:last-child {
            padding-right: 16px;
        }

        tbody tr:hover td {
            background: var(--qd-row-hover);
        }

        tbody tr:last-child td {
            border-bottom: 0;
        }

        .mono {
            font-family: var(--qd-mono);
            font-size: 12px;
        }

        .muted {
            color: var(--qd-muted);
        }

        .numeric {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .cell-time {
            white-space: nowrap;
            font-variant-numeric: tabular-nums;
        }

        .time-date {
            display: block;
            font-size: 12.5px;
            font-weight: 600;
            line-height: 1.4;
        }

        .time-clock {
            display: block;
            color: var(--qd-muted);
            font-size: 11.5px;
            line-height: 1.4;
        }

        .method {
            display: inline-block;
            padding: 2px 6px;
            border: 1px solid var(--qd-border);
            border-radius: 5px;
            background: var(--qd-surface-alt);
            color: var(--qd-muted);
            font-family: var(--qd-mono);
            font-size: 10.5px;
            font-weight: 600;
            letter-spacing: 0.04em;
            line-height: 1.5;
            text-transform: uppercase;
        }

        .cell-truncate {
            display: block;
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 2px 8px 2px 7px;
            border-radius: 5px;
            font-size: 11.5px;
            font-weight: 600;
            line-height: 1.6;
            white-space: nowrap;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
        }

        .badge-duplicate {
            background: var(--qd-warning-soft);
            color: var(--qd-warning);
        }

        .badge-n-plus-one {
            background: var(--qd-violet-soft);
            color: var(--qd-violet);
        }

        .badge-unknown {
            background: var(--qd-surface-alt);
            color: var(--qd-muted);
        }

        .noscript-hint {
            margin: 0;
            padding: 12px 16px;
            border-top: 1px solid var(--qd-border);
            color: var(--qd-muted);
            font-size: 12.5px;
        }

        .cell-sql {
            min-width: 240px;
        }

        .sql-box {
            max-width: 320px;
            padding: 7px 9px;
            overflow: hidden;
            border: 1px solid var(--qd-code-border);
            border-radius: 7px;
            background: var(--qd-code-bg);
        }

        .sql-text {
            display: -webkit-box;
            overflow: hidden;
            color: var(--qd-code-ink);
            font-family: var(--qd-mono);
            font-size: 11.5px;
            line-height: 1.5;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .sql-keyword {
            color: var(--qd-accent);
            font-weight: 650;
        }

        .sql-string {
            color: var(--qd-warning);
        }

        .sql-identifier {
            color: var(--qd-violet);
        }

        .sql-number {
            color: var(--qd-violet);
            font-variant-numeric: tabular-nums;
        }

        .sql-comment {
            color: var(--qd-muted);
            font-style: italic;
        }

        .cell-actions {
            width: 160px;
            min-width: 160px;
            white-space: nowrap;
        }

        .row-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .row-actions > * {
            width: 100%;
            min-width: 0;
        }

        .action-form {
            margin: 0;
        }

        .row-button {
            display: inline-flex;
            width: 100%;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 9px;
            border: 1px solid var(--qd-border);
            border-radius: 7px;
            background: var(--qd-surface);
            color: var(--qd-muted);
            font: inherit;
            font-size: 11.5px;
            font-weight: 500;
            line-height: 1.3;
            white-space: nowrap;
            cursor: pointer;
            transition: background-color 120ms ease, border-color 120ms ease, color 120ms ease;
        }

        .row-button .icon {
            width: 14px;
            height: 14px;
        }

        .copy-button:hover {
            border-color: var(--qd-accent);
            background: var(--qd-accent-soft);
            color: var(--qd-accent);
        }

        .copy-button .copied-icon {
            display: none;
        }

        .copy-button.copied {
            border-color: var(--qd-success);
            background: var(--qd-success-soft);
            color: var(--qd-success);
        }

        .copy-button.copied .copy-icon {
            display: none;
        }

        .copy-button.copied .copied-icon {
            display: block;
        }

        .copy-button.copy-error {
            border-color: var(--qd-danger);
            background: var(--qd-danger-soft);
            color: var(--qd-danger);
        }

        .delete-button {
            color: var(--qd-faint);
        }

        .delete-button:hover {
            border-color: var(--qd-danger);
            background: var(--qd-danger-soft);
            color: var(--qd-danger);
        }

        /* Empty state */

        .empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 56px 24px 60px;
            text-align: center;
        }

        .empty-icon {
            width: 42px;
            height: 42px;
            margin-bottom: 14px;
            color: var(--qd-faint);
        }

        .empty-title {
            margin: 0 0 6px;
            font-size: 16px;
            font-weight: 650;
            letter-spacing: -0.01em;
        }

        .empty-text {
            max-width: 50ch;
            margin: 0;
            color: var(--qd-muted);
            font-size: 13.5px;
        }

        .empty .button {
            margin-top: 18px;
        }

        /* Footer and pagination */

        .table-footer {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            border-top: 1px solid var(--qd-border);
            background: var(--qd-surface-alt);
        }

        .range {
            color: var(--qd-muted);
            font-size: 12.5px;
            font-variant-numeric: tabular-nums;
        }

        .pager {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .pager-info {
            padding: 0 4px;
            color: var(--qd-muted);
            font-size: 12.5px;
            font-variant-numeric: tabular-nums;
        }

        .pager-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border: 1px solid var(--qd-border);
            border-radius: 7px;
            background: var(--qd-surface);
            color: var(--qd-ink);
            font-size: 12.5px;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 120ms ease, border-color 120ms ease, color 120ms ease;
        }

        .pager-link:hover {
            border-color: var(--qd-accent);
            color: var(--qd-accent);
        }

        .pager-link.disabled {
            color: var(--qd-faint);
            pointer-events: none;
            opacity: 0.65;
        }

        .pager-link .icon {
            width: 13px;
            height: 13px;
        }

        .visually-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0 0 0 0);
            white-space: nowrap;
            border: 0;
        }

        @media (max-width: 760px) {
            body {
                padding: 22px 12px 44px;
            }

            .masthead {
                flex-direction: column;
                gap: 16px;
            }

            .clear-form {
                align-self: flex-start;
            }

            .toolbar {
                align-items: stretch;
            }

            .field,
            .field-grow {
                flex: 1 1 100%;
            }

            .actions {
                width: 100%;
                margin-left: 0;
            }

            .actions .button {
                flex: 1;
            }

            .actions .link-button {
                flex: 0 0 auto;
            }

            .table-footer {
                flex-direction: column;
                align-items: stretch;
            }

            .pager {
                justify-content: space-between;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition: none !important;
            }
        }
    </style>
</head>
<body
    data-copy-label="{{ trans('query-duplicates::query-duplicates.copy') }}"
    data-copied-label="{{ trans('query-duplicates::query-duplicates.copied') }}"
    data-error-label="{{ trans('query-duplicates::query-duplicates.copy_failed') }}"
>
@php
    $duplicateType = \Goodport\QueryDuplicates\Aggregator\QueryGroup::TYPE__DUPLICATE;
    $nPlusOneType = \Goodport\QueryDuplicates\Aggregator\QueryGroup::TYPE__N_PLUS_ONE;
    $typeLabels = [
        $duplicateType => trans('query-duplicates::query-duplicates.types.duplicate'),
        $nPlusOneType => trans('query-duplicates::query-duplicates.types.n+1'),
    ];
    $unknownTypeLabel = trans('query-duplicates::query-duplicates.types.unknown');
    $sortColumn = $filters['sort'];
    $sortDirection = $filters['direction'];
    $hasFilters = ($filters['q'] ?? '') !== '' || ($filters['type'] ?? '') !== '';
    $sortUrl = function (string $column) use ($filters, $sortColumn, $sortDirection): string {
        $nextDirection = $sortColumn === $column && $sortDirection === 'desc' ? 'asc' : 'desc';

        return route('query-duplicates.index', array_merge(request()->query(), [
            'sort' => $column,
            'direction' => $nextDirection,
            'page' => null,
        ]));
    };
    $sortAria = function (string $column) use ($sortColumn, $sortDirection): string {
        if ($sortColumn !== $column) {
            return 'none';
        }

        return $sortDirection === 'asc' ? 'ascending' : 'descending';
    };
    $sortCaret = function (string $column) use ($sortColumn, $sortDirection): string {
        if ($sortColumn !== $column) {
            return '⇅';
        }

        return $sortDirection === 'asc' ? '↑' : '↓';
    };
    $typeBadgeClass = function (string $type) use ($duplicateType, $nPlusOneType): string {
        return match ($type) {
            $nPlusOneType => 'badge-n-plus-one',
            $duplicateType => 'badge-duplicate',
            default => 'badge-unknown',
        };
    };
@endphp

<main class="page">
    <!-- Header -->
    <header class="masthead">
        <div class="masthead-copy">
            <h1 class="title">{{ trans('query-duplicates::query-duplicates.title') }}</h1>
            <p class="subtitle">{{ trans('query-duplicates::query-duplicates.subtitle') }}</p>
            <p class="storage-path">{{ trans('query-duplicates::query-duplicates.storage_path', ['path' => $storagePath]) }}</p>
        </div>

        @if ($duplicates !== null && ($hasFilters || $duplicates->total() > 0))
            <form class="clear-form" method="POST" action="{{ route('query-duplicates.clear') }}"
                  onsubmit="return confirm(@js(trans('query-duplicates::query-duplicates.clear_confirm')))">
                @csrf
                <button class="button button-danger" type="submit">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M4 7h16"/>
                        <path d="M10 11v6"/>
                        <path d="M14 11v6"/>
                        <path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"/>
                        <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                    </svg>
                    {{ trans('query-duplicates::query-duplicates.clear') }}
                </button>
            </form>
        @endif
    </header>

    @if (session('status'))
        <div class="notice" role="status">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <path d="m8 12 2.5 2.5L16 9"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($duplicates === null)
        <section class="setup" role="alert">
            <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <ellipse cx="12" cy="5" rx="7" ry="3"/>
                <path d="M5 5v6c0 1.7 3.1 3 7 3s7-1.3 7-3V5"/>
                <path d="M5 11v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>
                <path d="M12 14v5"/>
                <path d="M9.5 16.5 12 19l2.5-2.5"/>
            </svg>
            <div>
                <h2 class="setup-title">{{ trans('query-duplicates::query-duplicates.setup_title') }}</h2>
                <p class="setup-text">{{ trans('query-duplicates::query-duplicates.storage_unavailable', ['path' => $storagePath]) }}</p>
                <p class="setup-reason">{{ trans('query-duplicates::query-duplicates.storage_reason', ['reason' => $storageError]) }}</p>
                <p class="setup-hint">{{ trans('query-duplicates::query-duplicates.setup_hint') }}</p>
            </div>
        </section>
    @else
        <section class="workbench">
            <!-- Filters -->
            <form class="toolbar" method="GET" action="{{ route('query-duplicates.index') }}">
                <label class="field field-grow">
                    <span class="label">{{ trans('query-duplicates::query-duplicates.search') }}</span>
                    <span class="input-wrap">
                        <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7"/>
                            <path d="m16 16 4 4"/>
                        </svg>
                        <input class="input input-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}"
                               placeholder="{{ trans('query-duplicates::query-duplicates.search_placeholder') }}"
                               enterkeyhint="search" spellcheck="false">
                    </span>
                </label>

                <label class="field">
                    <span class="label">{{ trans('query-duplicates::query-duplicates.type') }}</span>
                    <select class="select" name="type">
                        <option value="">{{ trans('query-duplicates::query-duplicates.type_all') }}</option>
                        @foreach ($typeLabels as $type => $typeLabel)
                            <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>
                                {{ $typeLabel }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <input type="hidden" name="sort" value="{{ $sortColumn }}">
                <input type="hidden" name="direction" value="{{ $sortDirection }}">

                <div class="actions">
                    <button class="button button-primary" type="submit">
                        {{ trans('query-duplicates::query-duplicates.apply') }}
                    </button>
                    <a class="link-button" href="{{ route('query-duplicates.index') }}">
                        {{ trans('query-duplicates::query-duplicates.reset') }}
                    </a>
                </div>
            </form>

            @if ($duplicates->isEmpty())
                <!-- Empty state -->
                <div class="empty">
                    <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="4" width="18" height="16" rx="2"/>
                        <path d="M3 9h18"/>
                        <path d="M9 9v11"/>
                        <path d="M13 13h4"/>
                        <path d="M13 16h4"/>
                    </svg>

                    @if ($hasFilters)
                        <h2 class="empty-title">{{ trans('query-duplicates::query-duplicates.empty_filtered_title') }}</h2>
                        <p class="empty-text">{{ trans('query-duplicates::query-duplicates.empty_filtered_hint') }}</p>
                        <a class="button" href="{{ route('query-duplicates.index') }}">
                            {{ trans('query-duplicates::query-duplicates.empty_filtered_reset') }}
                        </a>
                    @else
                        <h2 class="empty-title">{{ trans('query-duplicates::query-duplicates.empty') }}</h2>
                        <p class="empty-text">{{ trans('query-duplicates::query-duplicates.empty_hint') }}</p>
                    @endif
                </div>
            @else
                <!-- Table -->
                <div class="table-scroll" role="region" tabindex="0"
                     aria-label="{{ trans('query-duplicates::query-duplicates.table_label') }}">
                    <table>
                        <caption>{{ trans('query-duplicates::query-duplicates.table_label') }}</caption>
                        <thead>
                        <tr>
                            @include('query-duplicates::query-duplicates.partials.sort-header', [
                                'column' => 'updated_at',
                                'label' => trans('query-duplicates::query-duplicates.columns.last_seen'),
                                'numeric' => false,
                            ])
                            <th scope="col">{{ trans('query-duplicates::query-duplicates.columns.type') }}</th>
                            @include('query-duplicates::query-duplicates.partials.sort-header', [
                                'column' => 'method',
                                'label' => trans('query-duplicates::query-duplicates.columns.method'),
                                'numeric' => false,
                            ])
                            <th scope="col">{{ trans('query-duplicates::query-duplicates.columns.route') }}</th>
                            <th scope="col">{{ trans('query-duplicates::query-duplicates.columns.path') }}</th>
                            @include('query-duplicates::query-duplicates.partials.sort-header', [
                                'column' => 'query_count',
                                'label' => trans('query-duplicates::query-duplicates.columns.query_count'),
                                'numeric' => true,
                            ])
                            @include('query-duplicates::query-duplicates.partials.sort-header', [
                                'column' => 'time_ms',
                                'label' => trans('query-duplicates::query-duplicates.columns.time_ms'),
                                'numeric' => true,
                            ])
                            @include('query-duplicates::query-duplicates.partials.sort-header', [
                                'column' => 'occurrences',
                                'label' => trans('query-duplicates::query-duplicates.columns.occurrences'),
                                'numeric' => true,
                            ])
                            <th scope="col">{{ trans('query-duplicates::query-duplicates.columns.sql') }}</th>
                            <th scope="col">{{ trans('query-duplicates::query-duplicates.columns.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($duplicates as $duplicate)
                            <tr>
                                <td class="cell-time">
                                    @if ($duplicate->updated_at)
                                        <time datetime="{{ $duplicate->updated_at->toIso8601String() }}">
                                            <span class="time-date">{{ $duplicate->updated_at->format('Y-m-d') }}</span>
                                            <span class="time-clock">{{ $duplicate->updated_at->format('H:i:s') }}</span>
                                        </time>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ $typeBadgeClass($duplicate->type) }}">
                                        <span class="badge-dot" aria-hidden="true"></span>
                                        {{ $typeLabels[$duplicate->type] ?? $unknownTypeLabel }}
                                    </span>
                                </td>
                                <td>
                                    <span class="method" dir="ltr">{{ $duplicate->method }}</span>
                                </td>
                                <td class="mono" dir="ltr">
                                    @if ($duplicate->route)
                                        <span class="cell-truncate" title="{{ $duplicate->route }}">{{ $duplicate->route }}</span>
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </td>
                                <td class="mono" dir="ltr">
                                    <span class="cell-truncate" title="{{ $duplicate->path }}">{{ $duplicate->path }}</span>
                                </td>
                                <td class="numeric">{{ $duplicate->query_count }}</td>
                                <td class="numeric">{{ number_format($duplicate->time_ms, 2) }}</td>
                                <td class="numeric">{{ $duplicate->occurrences }}</td>
                                <td class="cell-sql">
                                    <div class="sql-box" title="{{ $duplicate->sql }}">
                                        <code class="sql-text" dir="ltr">{{ $duplicate->sql }}</code>
                                    </div>
                                </td>
                                <td class="cell-actions">
                                    <div class="row-actions">
                                        <button type="button" class="row-button copy-button"
                                                data-context="{{ json_encode($duplicate->context(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR) }}"
                                                aria-label="{{ trans('query-duplicates::query-duplicates.copy_aria', ['target' => $duplicate->route ?: $duplicate->path]) }}">
                                            <svg class="icon copy-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                 aria-hidden="true">
                                                <rect x="9" y="9" width="11" height="11" rx="2"/>
                                                <path d="M5 15V6a2 2 0 0 1 2-2h9"/>
                                            </svg>
                                            <svg class="icon copied-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                 stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                 aria-hidden="true">
                                                <path d="m5 12 4.5 4.5L19 7"/>
                                            </svg>
                                            <span class="copy-label">{{ trans('query-duplicates::query-duplicates.copy') }}</span>
                                        </button>

                                        <form class="action-form" method="POST"
                                              action="{{ route('query-duplicates.destroy', array_merge(request()->query(), ['queryDuplicate' => $duplicate->id])) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="row-button delete-button"
                                                    aria-label="{{ trans('query-duplicates::query-duplicates.delete_aria', ['target' => $duplicate->route ?: $duplicate->path]) }}">
                                                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
                                                     aria-hidden="true">
                                                    <path d="M4 7h16"/>
                                                    <path d="M10 11v6"/>
                                                    <path d="M14 11v6"/>
                                                    <path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"/>
                                                    <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>
                                                </svg>
                                                <span>{{ trans('query-duplicates::query-duplicates.delete') }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                <noscript>
                    <p class="noscript-hint">{{ trans('query-duplicates::query-duplicates.noscript_copy') }}</p>
                </noscript>

                <!-- Pagination -->
                <div class="table-footer">
                    <p class="range">
                        {{ trans('query-duplicates::query-duplicates.range', [
                            'from' => $duplicates->firstItem(),
                            'to' => $duplicates->lastItem(),
                            'total' => $duplicates->total(),
                        ]) }}
                    </p>

                    @if ($duplicates->hasPages())
                        <nav class="pager" aria-label="{{ trans('query-duplicates::query-duplicates.pagination') }}">
                            @if ($duplicates->onFirstPage())
                                <span class="pager-link disabled" aria-disabled="true">
                                    {{ trans('query-duplicates::query-duplicates.previous') }}
                                </span>
                            @else
                                <a class="pager-link" href="{{ $duplicates->previousPageUrl() }}" rel="prev">
                                    {{ trans('query-duplicates::query-duplicates.previous') }}
                                </a>
                            @endif

                            <span class="pager-info">
                                {{ trans('query-duplicates::query-duplicates.page', [
                                    'current' => $duplicates->currentPage(),
                                    'last' => $duplicates->lastPage(),
                                ]) }}
                            </span>

                            @if ($duplicates->hasMorePages())
                                <a class="pager-link" href="{{ $duplicates->nextPageUrl() }}" rel="next">
                                    {{ trans('query-duplicates::query-duplicates.next') }}
                                </a>
                            @else
                                <span class="pager-link disabled" aria-disabled="true">
                                    {{ trans('query-duplicates::query-duplicates.next') }}
                                </span>
                            @endif
                        </nav>
                    @endif
                </div>
            @endif
        </section>
    @endif
</main>

<div class="visually-hidden" id="qd-copy-status" aria-live="polite" aria-atomic="true"></div>

<script>
    (function () {
        'use strict';

        // Adds lightweight, safe SQL highlighting without changing the stored text.
        function escapeHtml(value) {
            return value
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function highlightSql(element) {
            var source = element.textContent;
            var pattern = /('(?:''|[^'])*'|"(?:[^"]|"")*"|`[^`]*`|--[^\n]*|\/\*[\s\S]*?\*\/|\b(?:select|from|where|and|or|not|null|is|in|like|join|left|right|inner|outer|on|as|order|by|group|having|limit|offset|insert|into|values|update|set|delete|create|table|alter|drop|index|distinct|count|sum|avg|min|max|case|when|then|else|end|exists|between|asc|desc|union|all|primary|key|foreign|references|default|unique|constraint|with|returning)\b|\b\d+(?:\.\d+)?\b)/gi;
            var parts = source.split(pattern);

            element.innerHTML = parts.map(function (part, index) {
                if (index % 2 === 0) {
                    return escapeHtml(part);
                }

                if (part.charAt(0) === "'" || part.charAt(0) === '"') {
                    return '<span class="sql-string">' + escapeHtml(part) + '</span>';
                }

                if (part.charAt(0) === '`') {
                    return '<span class="sql-identifier">' + escapeHtml(part) + '</span>';
                }

                if (part.slice(0, 2) === '--' || part.slice(0, 2) === '/*') {
                    return '<span class="sql-comment">' + escapeHtml(part) + '</span>';
                }

                if (/^\d/.test(part)) {
                    return '<span class="sql-number">' + escapeHtml(part) + '</span>';
                }

                return '<span class="sql-keyword">' + escapeHtml(part) + '</span>';
            }).join('');
        }

        Array.prototype.forEach.call(document.querySelectorAll('.sql-text'), highlightSql);

        function fallbackCopy(text) {
            return new Promise(function (resolve, reject) {
                var textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.top = '-1000px';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();

                try {
                    var copied = document.execCommand('copy');
                    document.body.removeChild(textarea);
                    copied ? resolve() : reject(new Error('Copy failed.'));
                } catch (error) {
                    document.body.removeChild(textarea);
                    reject(error);
                }
            });
        }

        function copyText(text) {
            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                try {
                    return navigator.clipboard.writeText(text).catch(function () {
                        return fallbackCopy(text);
                    });
                } catch (error) {
                    return fallbackCopy(text);
                }
            }

            return fallbackCopy(text);
        }

        var COPY_RESET_DELAY_MS = 2400;
        var ANNOUNCE_DELAY_MS = 20;
        var body = document.body;
        var copyLabel = body.getAttribute('data-copy-label');
        var copiedLabel = body.getAttribute('data-copied-label');
        var errorLabel = body.getAttribute('data-error-label');

        var statusRegion = document.getElementById('qd-copy-status');
        var announceTimer = null;

        function announce(message) {
            if (!statusRegion) {
                return;
            }

            if (announceTimer) {
                window.clearTimeout(announceTimer);
            }

            statusRegion.textContent = '';
            announceTimer = window.setTimeout(function () {
                statusRegion.textContent = message;
            }, ANNOUNCE_DELAY_MS);
        }

        Array.prototype.forEach.call(document.querySelectorAll('[data-context]'), function (button) {
            var label = button.querySelector('.copy-label');
            var defaultAriaLabel = button.getAttribute('aria-label');
            var resetTimer = null;

            function setState(text, stateClass, accessibleLabel) {
                if (resetTimer) {
                    window.clearTimeout(resetTimer);
                }

                button.classList.remove('copied', 'copy-error');

                if (stateClass) {
                    button.classList.add(stateClass);
                }

                if (accessibleLabel) {
                    button.setAttribute('aria-label', accessibleLabel);
                } else if (defaultAriaLabel) {
                    button.setAttribute('aria-label', defaultAriaLabel);
                }

                if (label) {
                    label.textContent = text;
                } else {
                    button.textContent = text;
                }

                resetTimer = window.setTimeout(function () {
                    button.classList.remove('copied', 'copy-error');

                    if (defaultAriaLabel) {
                        button.setAttribute('aria-label', defaultAriaLabel);
                    }

                    if (label) {
                        label.textContent = copyLabel;
                    } else {
                        button.textContent = copyLabel;
                    }
                }, COPY_RESET_DELAY_MS);
            }

            button.addEventListener('click', function () {
                copyText(button.getAttribute('data-context')).then(function () {
                    setState(copiedLabel, 'copied', copiedLabel);
                    announce(copiedLabel);
                }).catch(function () {
                    setState(errorLabel, 'copy-error', errorLabel);
                    announce(errorLabel);
                });
            });
        });
    })();
</script>
</body>
</html>
