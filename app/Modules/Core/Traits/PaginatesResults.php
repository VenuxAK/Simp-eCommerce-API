<?php

namespace App\Modules\Core\Traits;

use Illuminate\Http\Request;

/**
 * Standardized per_page resolution for controller index actions.
 *
 * Reads the ?per_page query parameter, applies the application-wide
 * default of 20, and clamps the result to a maximum of 100 items per page.
 * Use this trait in any controller that exposes a paginated listing.
 */
trait PaginatesResults
{
    /** Default items per page when ?per_page is absent. */
    protected const DEFAULT_PER_PAGE = 20;

    /** Hard cap on items per page to prevent unbounded queries. */
    protected const MAX_PER_PAGE = 100;

    /**
     * Resolve the per_page value from the current request.
     *
     * Reads ?per_page, falls back to DEFAULT_PER_PAGE, and clamps
     * the result to [1, MAX_PER_PAGE].
     */
    protected function resolvePerPage(?Request $request = null): int
    {
        $request ??= request();
        $perPage = (int) $request->input('per_page', static::DEFAULT_PER_PAGE);

        return max(1, min($perPage, static::MAX_PER_PAGE));
    }
}
