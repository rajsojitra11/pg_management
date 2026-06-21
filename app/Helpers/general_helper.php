<?php

use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Modules\Role\Models\RoleYearAccess;
use Modules\Setting\Models\Setting;
use Modules\User\Models\UserProfile;
use Modules\Year\Models\Year;
use Spatie\Permission\Models\Role;

/* hierarchy tree create start */

if (! function_exists('getSalesTreeListUsingUserIdWeb')) {
    function getSalesTreeListUsingUserIdWeb($users)
    {
        $tree = [];
        foreach ($users as $user) {
            // Create node for the current user
            $node = [
                'user_id' => $user->user_id,  // Unique ID for the node
                'name' => $user->name,
                // 'is_show' => isset($user->user_tag) ? $user->user_tag->is_show : 'no',
            ];
            $tree[] = $node;
        }

        return $tree;
    }
}
/* hierarchy tree create end */

if (! function_exists('formatRoleName')) {
    function formatRoleName($roleName)
    {
        $words = explode(' ', $roleName);
        if (count($words) === 1) {
            return $roleName;
        }

        return implode(' ', array_map(function ($word) {
            return strtoupper(substr($word, 0, 1));
        }, $words));
    }
}

if (! function_exists('imageUploadBase64')) {
    function imageUploadBase64($data)
    {
        $file = $data['image'];
        $imageName = $data['fileName'];
        $originalStorage = $data['original_path'];
        $mediumStorage = $data['thumbnail_path'];
        explode('/', explode(':', substr($file, 0, strpos($file, ';')))[1])[1];
        $replace = substr($file, 0, strpos($file, ',') + 1);
        $image = str_replace($replace, '', $file);
        $image = str_replace(' ', '+', $image);
        $imageFile = Str::slug($imageName).'-'.sha1(time().uniqid()).'.png';
        $covertToImageFile = base64_decode($image);
        File::put($originalStorage.'/'.$imageFile, base64_decode($image));

        // $saveImages = Image::make($covertToImageFile)->insert($covertToImageFile);
        // $saveImages->resize(150, 150, function ($constraint) {
        //     $constraint->aspectRatio();
        // })->save($mediumStorage . '/' . $imageFile, 80);
        return $imageFile;
    }
}

if (! function_exists('imageUpload')) {
    // function imageUpload($data)
    // {
    //     $originalStorage = $data['original_path'];
    //     $mediumStorage = $data['thumbnail_path'];
    //     $file = $data['image'];
    //     $imageName = $data['fileName'];

    //     $images = Image::make($file)->insert($file);
    //     if (empty($imageName)) {
    //         $filename = sha1(time() . uniqid()) . '.' . $file->getClientOriginalExtension();
    //     } else {
    //         $filename = Str::slug($imageName) . sha1(time() . uniqid()) . '.' . $file->getClientOriginalExtension();
    //     }
    //     $images->resize(150, 150, function ($constraint) {
    //         $constraint->aspectRatio();
    //     })->save($mediumStorage . '/' . $filename, 80);
    //     $file->move($originalStorage, $filename);
    //     return $filename;
    // }

    function imageUpload($data)
    {
        $file = $data['image'];
        $imageName = $data['fileName'] ?? 'image';
        $imageFolder = $data['folder'];
        $imageThumFolder = $data['thumfolder'];

        if (! $file) {
            return null;
        }

        $filename = Str::slug($imageName).'-'.time().'.webp';

        $imageFullFolder = storage_path('app/public/'.$imageFolder);
        $thumbFullFolder = storage_path('app/public/'.$imageThumFolder);

        // 🛠 Make actual folders on disk (if not exist)
        if (! File::exists($imageFullFolder)) {
            File::makeDirectory($imageFullFolder, 0755, true);
        }
        if (! File::exists($thumbFullFolder)) {
            File::makeDirectory($thumbFullFolder, 0755, true);
        }

        // Final full paths
        $imagePath = $imageFullFolder.$filename;
        $thumbPath = $thumbFullFolder.$filename;

        // Load image from temp path
        $tempPath = $file->getRealPath();
        $src = imagecreatefromstring(file_get_contents($tempPath));

        // Save original .webp
        imagewebp($src, $imagePath, 90);

        // Make 200x200 thumbnail
        $thumb = imagecreatetruecolor(200, 200);
        [$width, $height] = getimagesize($imagePath);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, 200, 200, $width, $height);
        imagewebp($thumb, $thumbPath, 90);

        imagedestroy($src);
        imagedestroy($thumb);

        return $filename;
    }
}

if (! function_exists('getSalesTreeUsingUserId')) {
    function getSalesTreeUsingUserId($user_id, $avoidCurrentUserId)
    {
        if ($avoidCurrentUserId == 'yes') {
            $userData = UserProfile::where([['parent_id', $user_id]])->select('user_id', 'parent_id')->get();
        } else {
            $userData = UserProfile::where([['user_id', $user_id]])->select('user_id', 'parent_id')->get();
        }

        return buildSalesTreeIds($userData->toArray());
    }
}

if (! function_exists('buildSalesTreeIds')) {
    function buildSalesTreeIds($users)
    {
        $ids = [];
        foreach ($users as $user) {
            $userArray = is_array($user) ? $user : $user->toArray();
            $ids[] = $userArray['user_id'];
            $children = UserProfile::select('user_id', 'parent_id')
                ->where('parent_id', '=', $userArray['user_id'])
                // ->where('user_id', '!=', $userArray['user_id'])
                ->get();
            $ids = array_merge($ids, buildSalesTreeIds($children->toArray()));
        }

        return $ids;
    }
}

// if (!function_exists('setting')) {
//     function setting()
//     {
//         return Setting::select('id', 'company_name', 'tag_line', 'favicon', 'logo', 'gst_number', 'pancard_number', 'tan_number')->first();
//     }
// }
if (! function_exists('setting')) {
    function setting()
    {
        return Cache::remember('app_settings', 60 * 60, function () {
            $setting = Setting::select(
                'id',
                'company_name',
                'mobile',
                'email',
                'address',
                'tag_line',
                'gst_number',
                'pancard_number',
                'tan_number',
                'favicon',
                'logo',
                'logo_dark',
                'year_display_format'
            )->first();

            // Cast to plain object to avoid serialization issues with Eloquent models
            if ($setting) {
                return (object) $setting->toArray();
            }

            return (object) [
                'id' => null,
                'company_name' => 'Default Company',
                'mobile' => '+91 0000000000',
                'email' => 'company@compay.com',
                'address' => 'Address',
                'tag_line' => 'Your tagline here',
                'gst_number' => null,
                'pancard_number' => null,
                'tan_number' => null,
                'favicon' => 'default-favicon.png',
                'logo' => 'default-logo.png',
                'logo_dark' => 'default-logo.png',
                'year_display_format' => 'full_short',
            ];
        });
    }
}
if (! function_exists('defaultMigration')) {
    function defaultMigration($table)
    {
        $table->softDeletes();
        $table->unsignedBigInteger('created_by')->comment('user id')->nullable();
        $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        $table->unsignedBigInteger('updated_by')->comment('user id')->nullable();
        $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
        $table->unsignedBigInteger('deleted_by')->comment('user id')->nullable();
        $table->foreign('deleted_by')->references('id')->on('users')->onDelete('cascade');
    }
}

if (! function_exists('getYearList')) {
    function getYearList($loadAll = false)
    {
        $cacheKey = 'year_list_'.($loadAll ? 'all' : 'limited');

        return Cache::remember($cacheKey, 300, function () use ($loadAll) {
            if ($loadAll) {
                return Year::select('id', 'name', 'full_short', 'short_full', 'short_short', 'full_full', 'short', 'full', 'set_default')
                    ->orderBy('full', 'desc')
                    ->get();
            }

            $currentFY = getCurrentFiscalYear();
            $nextFY = getFiscalYearRange($currentFY, 2);
            $maxYearName = end($nextFY);

            $years = Year::select('id', 'name', 'full_short', 'short_full', 'short_short', 'full_full', 'short', 'full', 'set_default')
                ->where('name', '<=', $maxYearName)
                ->orderBy('full', 'asc')
                ->get();

            if ($years->isEmpty()) {
                $years = Year::select('id', 'name', 'full_short', 'short_full', 'short_short', 'full_full', 'short', 'full', 'set_default')
                    ->orderBy('full', 'desc')
                    ->limit(5)
                    ->get();
            }

            return $years;
        });
    }
}

if (! function_exists('getYear')) {
    function getYear($loadAll = false, $includeSearch = true)
    {
        $html = '';

        try {
            $allYears = getYearList($loadAll);

            $allowedIds = getUserAllowedYearIds();
            $years = is_array($allowedIds)
                ? $allYears->whereIn('id', $allowedIds)
                : $allYears;

            if ($years->isNotEmpty()) {
                $sessionYearId = getSessionYearId();
                $sessionYearName = session('year');

                foreach ($years as $year) {
                    // Determine active class
                    if (! is_null($sessionYearId)) {
                        $class = $sessionYearId == $year->id ? 'active' : '';
                    } elseif (! is_null($sessionYearName)) {
                        $class = $sessionYearName == $year->name ? 'active' : '';
                    } else {
                        $class = $year->set_default == 1 ? 'active' : '';
                    }

                    // Get formatted year display
                    $displayName = getFormattedYear($year);

                    $html .= '<a class="dropdown-item m-0 p-2 year-change '.$class.'"
                                    data-value="'.$year->id.'"
                                    data-name="'.$year->name.'"
                                    title="'.$year->name.'">'.$displayName.'</a>';
                }
            }

            // Add search section only when includeSearch is true (old Bootstrap theme needs it)
            // Tailwind headers have their own search input — passing false avoids duplicate
            if ($years->isNotEmpty() && $includeSearch) {
                $html .= '<div class="dropdown-divider"></div>';
                $html .= '<div class="px-3 py-2 year-search-container sticky-bottom bg-white border-top">
                                <input type="text" class="form-control form-control-sm year-search-input"
                                       placeholder="Search years..." autocomplete="off">
                                <div class="year-search-results mt-2" style="max-height: 250px; overflow-y: auto;"></div>
                              </div>';
            }
        } catch (Exception $e) {
            $fallbackYears = getYearList(true)->take(5);
            foreach ($fallbackYears as $year) {
                $class = $year->set_default == 1 ? 'active' : '';
                $html .= '<a class="dropdown-item m-0 p-2 year-change '.$class.'" data-value="'.$year->id.'">'.$year->name.'</a>';
            }
        }

        return $html;
    }
}

if (! function_exists('getSelectedYear')) {
    /**
     * Get the selected year ID from session with optional validation and fallback
     *
     * @param  bool  $ensureFallback  Whether to ensure a year is always returned (with fallback if needed)
     * @return int|null Year ID
     */
    function getSelectedYear($ensureFallback = true)
    {
        $sessionYearId = getSessionYearId();
        if ($sessionYearId) {
            $year = getYearList(true)->firstWhere('id', $sessionYearId);
            if ($year) {
                return $sessionYearId;
            }
        }

        $sessionYearName = session('year');
        if ($sessionYearName) {
            $year = getYearList(true)->firstWhere('name', $sessionYearName);
            if ($year) {
                setSessionYear($year->id);

                return $year->id;
            }
        }

        if (! $ensureFallback) {
            return null;
        }

        $defaultYear = getYearList(true)->firstWhere('set_default', 1);
        if ($defaultYear) {
            setSessionYear($defaultYear->id);

            return $defaultYear->id;
        }

        $anyYear = getYearList(true)->sortByDesc('full')->first();
        if ($anyYear) {
            setSessionYear($anyYear->id);

            return $anyYear->id;
        }

        return null;
    }
}

if (! function_exists('allowedYearIds')) {
    /**
     * Resolve the financial-year ids the given (or current) user is allowed to see,
     * based on their roles' RoleYearAccess configuration.
     *
     * Returns:
     *   - null  => UNRESTRICTED (show every year). Used for guests, users with no
     *              roles, roles without a year-access row, or any role with all_years=1.
     *   - array => RESTRICTED to exactly these Year ids = current FY + N previous FYs,
     *              where N = the most permissive `allowed_year` count across the user's
     *              roles. May be an empty array (fail-closed) when no current FY exists.
     *
     * Request-memoized per user id to avoid repeating the lookup on every list/partial.
     *
     * @param  Authenticatable|null  $user
     * @return array<int>|null
     */
    function allowedYearIds($user = null)
    {
        static $cache = [];

        $user = $user ?: auth()->user();
        if (! $user) {
            return null;
        }

        $key = $user->getKey();
        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        // Check session cache first to avoid DB query every page load
        $sessionKey = 'allowed_year_ids_'.$key;
        if (session()->exists($sessionKey)) {
            return $cache[$key] = session($sessionKey);
        }

        $roleIds = $user->roles->pluck('id')->all();
        if (empty($roleIds)) {
            $result = null;
        } else {
            $accesses = RoleYearAccess::whereIn('role_id', $roleIds)->get(['all_years', 'allowed_year']);

            if ($accesses->isEmpty()) {
                $result = null;
            } elseif ($accesses->contains(fn ($a) => $a->all_years === true)) {
                $result = null;
            } else {
                $allYears = getYearList(true);
                $current = $allYears->firstWhere('set_default', 1);
                if (! $current) {
                    $result = [];
                } else {
                    $n = (int) $accesses->max('allowed_year');
                    $ids = $allYears
                        ->where('full', '<=', $current->full)
                        ->sortByDesc('full')
                        ->take($n + 1)
                        ->pluck('id')
                        ->all();
                    $result = $ids;
                }
            }
        }

        // Store in session so subsequent page loads skip the DB query
        session([$sessionKey => $result]);

        return $cache[$key] = $result;
    }
}

if (! function_exists('roleYearAccessSummary')) {
    /**
     * Summarise a single role's year-access for display on the roles list.
     *
     * Mirrors allowedYearIds() enforcement so the card never diverges from the
     * real access window: current FY + N previous FYs, where N = allowed_year.
     *
     * Returns:
     *   - ['restricted' => false, 'years' => collect()]  => Full access (all years,
     *       or no year-access row configured).
     *   - ['restricted' => true,  'years' => Collection<Year>]  => the exact FYs the
     *       role can see, newest first (empty when no current FY exists — fail-closed).
     *
     * The descending FY window is resolved once per request (static-cached) so a loop
     * over many roles issues a single `years` query.
     *
     * @param  Role  $role  (with `yearAccess` eager-loaded)
     * @return array{restricted: bool, years: Collection}
     */
    function roleYearAccessSummary($role)
    {
        static $yearsDesc = null; // Year models <= current FY, newest first
        static $resolved = false;

        $access = $role->yearAccess; // RoleYearAccess|null

        // Unrestricted: no access row, or an explicit all-years grant.
        if (! $access || $access->all_years === true) {
            return ['restricted' => false, 'years' => collect()];
        }

        // Resolve the descending FY window once across the whole loop.
        if (! $resolved) {
            $resolved = true;
            $current = Year::where('set_default', 1)->first(['id', 'full']);
            $yearsDesc = $current
                ? Year::where('full', '<=', $current->full)->orderByDesc('full')->get()
                : collect();
        }

        if ($yearsDesc->isEmpty()) {
            return ['restricted' => true, 'years' => collect()]; // no current FY — fail-closed
        }

        $n = (int) $access->allowed_year; // null => 0

        return ['restricted' => true, 'years' => $yearsDesc->take($n + 1)->values()];
    }
}

if (! function_exists('loginStatus')) {
    function loginStatus($row)
    {
        if (! Gate::allows('users-activate') && ! Gate::allows('users-deactivate')) {
            // Show status badge only if user doesn't have permission
            $statusBadge = '';
            if ($row->user->status === 'Active') {
                $statusBadge .= '<span class="badge bg-label-success me-1">Active</span>';
            } else {
                $statusBadge .= '<span class="badge bg-label-warning me-1">Inactive</span>';
            }

            if ($row->user->is_blocked == 1) {
                $statusBadge .= '<span class="badge bg-label-danger">Blocked</span>';
            }

            return $statusBadge;
        }

        // Determine current status display and button style
        $isActive = $row->user->status === 'Active';
        $isBlocked = $row->user->is_blocked == 1;

        $statusText = $isActive ? 'Active' : 'Inactive';
        $buttonClass = $isActive ? 'btn-outline-success' : 'btn-outline-warning';

        if ($isBlocked) {
            $statusText = 'Blocked';
            $buttonClass = 'btn-outline-danger';
        }

        $dropdownItems = [];

        // Add activation/deactivation options based on current status
        if (! $isBlocked) {
            if ($isActive) {
                $dropdownItems[] = '<li><a class="dropdown-item user-status-change" href="javascript:void(0);"
                    data-id="'.$row->user->id.'" data-action="deactivate" data-status="Inactive" data-name="'.htmlspecialchars($row->user->name).'">
                    <i class="fas fa-user-times text-warning me-2"></i>Deactivate</a></li>';
            } else {
                $dropdownItems[] = '<li><a class="dropdown-item user-status-change" href="javascript:void(0);"
                    data-id="'.$row->user->id.'" data-action="activate" data-status="Active" data-name="'.htmlspecialchars($row->user->name).'">
                    <i class="fas fa-user-check text-success me-2"></i>Activate</a></li>';
            }
        }

        // Add block/unblock options
        if ($isBlocked) {
            $dropdownItems[] = '<li><a class="dropdown-item user-block-change" href="javascript:void(0);"
                data-id="'.$row->user->id.'" data-action="unblock" data-status="0" data-name="'.htmlspecialchars($row->user->name).'">
                <i class="fas fa-unlock text-success me-2"></i>Unblock</a></li>';
        } else {
            // $dropdownItems[] = '<li><a class="dropdown-item user-block-change" href="javascript:void(0);"
            //     data-id="' . $row->user->id . '" data-action="block" data-status="1" data-name="' . htmlspecialchars($row->user->name) . '">
            //     <i class="fas fa-ban text-danger me-2"></i>Block</a></li>';
        }

        $dropDown = '<div class="dropdown">
                        <button class="btn px-2 py-1 '.$buttonClass.' dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                            '.$statusText.'
                        </button>
                        <ul class="dropdown-menu">
                            '.implode('', $dropdownItems).'
                        </ul>
                    </div>';

        return $dropDown;
    }
}

if (! function_exists('getDefaultMigrationDate')) {
    /**
     * Get the default migration date from environment variable
     * Used for seeding operations to maintain consistent timestamps
     *
     * @return string The migration date in Y-m-d H:i:s format
     */
    function getDefaultMigrationDate()
    {
        $defaultDate = config('business.default_migration_date', '2025-01-01 00:00:00');

        // Validate the date format
        if (! Carbon::hasFormat($defaultDate, 'Y-m-d H:i:s')) {
            // Try to parse and reformat if it's in a different valid format
            try {
                $parsedDate = Carbon::parse($defaultDate);

                return $parsedDate->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                // Return default if parsing fails
                return '2025-01-01 00:00:00';
            }
        }

        return $defaultDate;
    }
}

if (! function_exists('setDefaultTimestampsForTable')) {
    // No-op stub. The historical-data-entry subsystem that backfilled
    // created_at/updated_at column defaults on freshly-created tables has been
    // removed; seeders set timestamps explicitly via getDefaultMigrationDate().
    // Kept as a function so legacy migration calls don't fatal.
    function setDefaultTimestampsForTable(string $table): void {}
}

if (! function_exists('getCurrentFiscalYear')) {
    /**
     * Get the current fiscal year in YYYY-YY format
     * Assumes April-March fiscal year cycle
     *
     * @return string Current fiscal year (e.g., "2023-24")
     */
    function getCurrentFiscalYear()
    {
        $now = Carbon::now();
        $month = $now->month;
        $year = $now->year;
        $fyStartMonth = (int) config('business.fy_start_month', 4);

        if ($month >= $fyStartMonth) {
            $startYear = $year;
            $endYear = $year + 1;
        } else {
            $startYear = $year - 1;
            $endYear = $year;
        }

        // When FY starts in January, start and end year are the same
        if ($fyStartMonth === 1) {
            return $startYear.'-'.sprintf('%02d', $startYear % 100);
        }

        return $startYear.'-'.sprintf('%02d', $endYear % 100);
    }
}

if (! function_exists('getFiscalYearRange')) {
    /**
     * Get a range of fiscal years starting from given year
     *
     * @param  string  $startFiscalYear  Starting fiscal year (e.g., "2023-24")
     * @param  int  $count  Number of years to include
     * @return array Array of fiscal year strings
     */
    function getFiscalYearRange($startFiscalYear, $count = 3)
    {
        $years = [];
        $parts = explode('-', $startFiscalYear);

        if (count($parts) !== 2) {
            return [$startFiscalYear]; // Return as-is if format is unexpected
        }

        $startYear = (int) $parts[0];

        for ($i = 0; $i < $count; $i++) {
            $currentStart = $startYear + $i;
            $currentEnd = $currentStart + 1;
            $years[] = $currentStart.'-'.sprintf('%02d', $currentEnd % 100);
        }

        return $years;
    }
}

if (! function_exists('calculateFiscalYear')) {
    /**
     * Calculate fiscal year for a given date
     *
     * @param  string|Carbon  $date  Date to calculate fiscal year for
     * @return string Fiscal year in YYYY-YY format
     */
    function calculateFiscalYear($date)
    {
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        $month = $date->month;
        $year = $date->year;
        $fyStartMonth = (int) config('business.fy_start_month', 4);

        if ($month >= $fyStartMonth) {
            $startYear = $year;
            $endYear = $year + 1;
        } else {
            $startYear = $year - 1;
            $endYear = $year;
        }

        if ($fyStartMonth === 1) {
            return $startYear.'-'.sprintf('%02d', $startYear % 100);
        }

        return $startYear.'-'.sprintf('%02d', $endYear % 100);
    }
}

if (! function_exists('setSessionYear')) {
    /**
     * Set year ID in session
     *
     * @param  int  $yearId  Year ID to set in session
     * @return bool Success status
     */
    function setSessionYear($yearId)
    {
        $year = getYearList(true)->firstWhere('id', $yearId);
        if (! $year) {
            return false;
        }

        session(['year_id' => $yearId, 'year' => $year->name]);
        session()->save();

        return true;
    }
}

if (! function_exists('getSessionYearId')) {
    /**
     * Get year ID from session
     *
     * @return int|null Year ID from session
     */
    function getSessionYearId()
    {
        return session('year_id');
    }
}

if (! function_exists('validateSessionYear')) {
    /**
     * Validate that session year still exists and optionally set fallback
     *
     * @param  bool  $autoFallback  Whether to automatically set a fallback year if session year is invalid
     * @return int|null Year ID (validated, fallback if enabled, or null)
     */
    function validateSessionYear($autoFallback = false)
    {
        $sessionYearId = getSessionYearId();

        if ($sessionYearId) {
            $year = getYearList(true)->firstWhere('id', $sessionYearId);
            if ($year) {
                return $sessionYearId;
            }
        }

        if (! $autoFallback) {
            return null;
        }

        $defaultYear = getYearList(true)->firstWhere('set_default', 1);
        if ($defaultYear) {
            setSessionYear($defaultYear->id);

            return $defaultYear->id;
        }

        $firstYear = getYearList(true)->sortByDesc('full')->first();
        if ($firstYear) {
            setSessionYear($firstYear->id);

            return $firstYear->id;
        }

        return null;
    }
}

if (! function_exists('getYearDisplayFormat')) {
    /**
     * Get the current year display format from settings with fallback to config
     * Uses caching for better performance
     *
     * @return string Year display format
     */
    function getYearDisplayFormat()
    {
        return Cache::remember('year_display_format', 60 * 60, function () {
            try {
                $settings = setting();

                // First try to get from database settings
                if ($settings && isset($settings->year_display_format) && ! empty($settings->year_display_format)) {
                    return $settings->year_display_format;
                }

                // Fallback to config/env
                return config('app.year_display_format', 'full_short');
            } catch (Exception $e) {
                // Ultimate fallback
                return 'full_short';
            }
        });
    }
}

if (! function_exists('updateYearDisplayFormat')) {
    /**
     * Update the year display format in settings and clear cache
     *
     * @param  string  $format  New year display format
     * @return bool Success status
     */
    function updateYearDisplayFormat($format)
    {
        try {
            $validFormats = ['full_short', 'short_full', 'short_short', 'full_full', 'short', 'full'];

            if (! in_array($format, $validFormats)) {
                return false;
            }

            $setting = Setting::first();
            if (! $setting) {
                $setting = new Setting;
            }

            $setting->year_display_format = $format;
            $result = $setting->save();

            // Clear both settings and year display format cache
            Cache::forget('app_settings');
            Cache::forget('year_display_format');

            return $result;
        } catch (Exception $e) {

            return false;
        }
    }
}

if (! function_exists('getUserAllowedYearIds')) {
    /**
     * Get year IDs the current user's role(s) grant access to.
     * Returns null if all years are allowed (no restriction).
     *
     * Thin alias over {@see allowedYearIds()} (the canonical, memoized resolver)
     * so existing call sites keep working. `allowed_year` is the number of PREVIOUS
     * financial years to include in addition to the current FY (set_default).
     */
    function getUserAllowedYearIds(): ?array
    {
        return allowedYearIds();
    }
}

if (! function_exists('clientIp')) {
    /**
     * Resolve the real client IP for logging. With trusted proxies configured,
     * request()->ip() returns the X-Forwarded-For client address instead of the
     * reverse-proxy's loopback. The IPv6 loopback (::1) is normalized to the more
     * familiar 127.0.0.1 so logs never store a bare "::1".
     */
    function clientIp(?Request $request = null): ?string
    {
        $request = $request ?: request();
        $ip = ($request ? $request->ip() : null) ?: ($_SERVER['REMOTE_ADDR'] ?? null);

        return $ip === '::1' ? '127.0.0.1' : $ip;
    }
}

if (! function_exists('getLocationFromIp')) {
    function getLocationFromIp(?string $ip): string
    {
        if (! $ip) {
            return 'Unknown';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return 'Local Network';
        }

        try {
            $ctx = stream_context_create(['http' => ['timeout' => 2, 'method' => 'GET']]);
            $resp = @file_get_contents("http://ip-api.com/json/{$ip}?fields=city,regionName,country", false, $ctx);
            if ($resp) {
                $data = json_decode($resp, true);
                if ($data && ($data['city'] || $data['regionName'])) {
                    $parts = array_filter([$data['city'] ?? '', $data['regionName'] ?? '', $data['country'] ?? '']);

                    return implode(', ', $parts);
                }
            }
        } catch (Exception $e) {
            // Silently fail — geo-lookup is best-effort
        }

        return 'Unknown';
    }
}
