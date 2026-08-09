<?php

namespace Modules\MenuMaster\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\MenuMaster\Http\Requests\DeleteMenuMasterRequest;
use Modules\MenuMaster\Http\Requests\StoreMenuMasterRequest;
use Modules\MenuMaster\Http\Requests\UpdateMenuMasterRequest;
use Modules\MenuMaster\Models\MenuMaster;
use Modules\MenuMaster\Services\MenuMasterService;

class MenuMasterController extends Controller
{
    public function __construct(private MenuMasterService $menuMasterService)
    {
        $this->middleware('permission:menu-master-list|menu-master-create|menu-master-edit|menu-master-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:menu-master-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:menu-master-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:menu-master-delete', ['only' => ['destroy']]);
        $this->middleware('permission:menu-master-export', ['only' => ['export']]);
    }

    /**
     * Display menu management page
     */
    public function index(Request $request): View
    {
        $moduleName = $request->get('module_name');

        $menuItems = $this->menuMasterService->getFlattenedMenu($moduleName);
        $menuTree = $this->menuMasterService->getMenuTree(null, $moduleName);

        // Get unique modules for filter
        // $modules = MenuMaster::whereNotNull('module_name')
        //     ->distinct()
        //     ->pluck('module_name', 'menu_title')
        //     ->sort();

        // Get unique modules for filter
        $modules = MenuMaster::pluck('module_name', 'menu_title')
            // ->whereNotNull('module_name')
            // ->distinct();
            ->sort();

        return view('menumaster::index', compact('menuItems', 'menuTree', 'modules', 'moduleName'));
    }

    /**
     * Show create menu form
     */
    public function create(): View
    {
        $parentOptions = MenuMaster::ordered()
            ->get()
            ->map(function ($item) {
                $level = $item->getLevel();
                $indent = str_repeat('  ', $level - 1);

                return [
                    'id' => $item->id,
                    'title' => $indent.$item->getHumanReadableOrder().' -  '.__($item->menu_title),
                    // 'title' => $indent . $item->getHumanReadableOrder() . ' ' . $item->menu_title
                ];
            });

        // Get unique modules
        $modules = MenuMaster::whereNotNull('module_name')
            ->distinct()
            ->pluck('module_name')
            ->sort();

        return view('menumaster::create', compact('parentOptions', 'modules'));
    }

    /**
     * Store new menu item
     */
    public function store(StoreMenuMasterRequest $request): JsonResponse|RedirectResponse
    {
        // Validation is handled by StoreMenuMasterRequest

        try {
            $menuMaster = $this->menuMasterService->createMenuItem($request->validated());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Menu item created successfully',
                    'data' => $menuMaster->load('parent'),
                ]);
            }

            return redirect()->route('menumasters.index')
                ->with('success', 'Menu item created successfully');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating menu item: '.$e->getMessage(),
                ], 500);
            }

            return back()->withInput()
                ->withErrors(['error' => 'Error creating menu item: '.$e->getMessage()]);
        }
    }

    /**
     * Show edit menu form
     */
    public function edit(MenuMaster $menuMaster): View
    {
        $parentOptions = MenuMaster::where('id', '!=', $menuMaster->id)
            ->ordered()
            ->get()
            ->filter(function ($item) use ($menuMaster) {
                // Prevent selecting self or descendants as parent
                $normalizedCurrent = $this->normalizeOrderForComparison($menuMaster->order_display);
                $normalizedItem = $this->normalizeOrderForComparison($item->order_display);

                return ! str_starts_with($normalizedItem, $normalizedCurrent.'.');
            })
            ->map(function ($item) {
                $level = $item->getLevel();
                $indent = str_repeat('  ', $level - 1);

                return [
                    'id' => $item->id,
                    // 'title' => $indent . $item->getHumanReadableOrder() . ' ' . $item->menu_title
                    'title' => $indent.$item->getHumanReadableOrder().' -  '.__($item->menu_title),
                ];
            });

        // Get unique modules
        $modules = MenuMaster::whereNotNull('module_name')
            ->distinct()
            ->pluck('module_name')
            ->sort();

        return view('menumaster::edit', compact('menuMaster', 'parentOptions', 'modules'));
    }

    /**
     * Update menu item
     */
    public function update(UpdateMenuMasterRequest $request, MenuMaster $menuMaster): JsonResponse|RedirectResponse
    {
        // Validation is handled by UpdateMenuMasterRequest

        // Prevent moving to self or descendants
        if ($request->filled('parent_id') && $request->parent_id == $menuMaster->id) {
            $error = 'Cannot set menu as parent of itself';

            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }

            return back()->withErrors(['parent_id' => $error]);
        }

        try {
            $updatedMenu = $this->menuMasterService->updateMenuItem($menuMaster, $request->validated());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Menu item updated successfully',
                    'data' => $updatedMenu->load('parent'),
                ]);
            }

            return redirect()->route('menumasters.index')
                ->with('success', 'Menu item updated successfully');
        } catch (Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating menu item: '.$e->getMessage(),
                ], 500);
            }

            return back()->withInput()
                ->withErrors(['error' => 'Error updating menu item: '.$e->getMessage()]);
        }
    }

    /**
     * Move menu item to new position
     */
    public function move(Request $request, MenuMaster $menuMaster): JsonResponse
    {
        $request->validate([
            'parent_id' => 'nullable|integer|min:0|exists:menu_masters,id',
            'position' => 'nullable|integer|min:0',
        ]);

        try {
            $this->menuMasterService->moveItem(
                $menuMaster,
                $request->input('parent_id'),
                $request->input('position')
            );

            return response()->json([
                'success' => true,
                'message' => 'Menu item moved successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error moving menu item: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete menu item
     */
    public function destroy(DeleteMenuMasterRequest $request, MenuMaster $menuMaster): JsonResponse|RedirectResponse
    {
        try {
            $this->menuMasterService->deleteMenuItem($menuMaster);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Menu item deleted successfully',
                ]);
            }

            return redirect()->route('menumasters.index')
                ->with('success', 'Menu item deleted successfully');
        } catch (Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting menu item: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('menumasters.index')
                ->withErrors(['error' => 'Error deleting menu item: '.$e->getMessage()]);
        }
    }

    /**
     * Get menu tree as JSON (for frontend components)
     */
    public function getTree(Request $request): JsonResponse
    {
        $parentId = $request->input('parent_id');
        $moduleName = $request->input('module_name');

        $menuTree = $this->menuMasterService->getMenuTree($parentId, $moduleName);

        return response()->json([
            'success' => true,
            'data' => $menuTree,
        ]);
    }

    /**
     * Get flattened menu for select dropdowns
     */
    public function getFlattened(Request $request): JsonResponse
    {
        $moduleName = $request->input('module_name');
        $menuItems = $this->menuMasterService->getFlattenedMenu($moduleName);

        return response()->json([
            'success' => true,
            'data' => $menuItems,
        ]);
    }

    /**
     * Get navigation menu for current user
     */
    public function getNavigation(Request $request): JsonResponse
    {
        $moduleName = $request->input('module_name');
        $user = Auth::user();

        $navigationMenu = $this->menuMasterService->getNavigationMenuForUser($moduleName, $user);

        return response()->json([
            'success' => true,
            'data' => $navigationMenu,
        ]);
    }

    /**
     * Show menu item details
     */
    public function show(MenuMaster $menuMaster): View|JsonResponse
    {
        $menuMaster->load(['parent', 'children.children', 'creator', 'updater']);

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $menuMaster,
            ]);
        }

        return view('menumaster::show', compact('menuMaster'));
    }

    /**
     * Duplicate menu item
     */
    public function duplicate(MenuMaster $menuMaster): JsonResponse|RedirectResponse
    {
        try {
            $duplicateData = [
                'menu_title' => $menuMaster->menu_title,
                'menu_icon' => $menuMaster->menu_icon,
                'menu_route' => $menuMaster->menu_route, // Clear route to avoid conflicts
                'parent_id' => $menuMaster->parent_id,
                'module_name' => $menuMaster->module_name,
                'if_can' => $menuMaster->if_can,
                'is_main_menu' => $menuMaster->is_main_menu,
            ];

            $duplicatedMenu = $this->menuMasterService->createMenuItem($duplicateData);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Menu item duplicated successfully',
                    'data' => $duplicatedMenu,
                ]);
            }

            return redirect()->route('menumasters.index')
                ->with('success', 'Menu item duplicated successfully');
        } catch (Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error duplicating menu item: '.$e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => 'Error duplicating menu item: '.$e->getMessage()]);
        }
    }

    /**
     * Normalize menu orders (admin utility)
     */
    public function normalizeOrders(): JsonResponse|RedirectResponse
    {
        try {
            $this->menuMasterService->normalizeAllMenuOrders();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Menu orders normalized successfully',
                ]);
            }

            return redirect()->route('menumasters.index')
                ->with('success', 'Menu orders normalized successfully');
        } catch (Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error normalizing menu orders: '.$e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => 'Error normalizing menu orders: '.$e->getMessage()]);
        }
    }

    /**
     * Rebuild menu hierarchy (admin utility)
     */
    public function rebuildHierarchy(): JsonResponse|RedirectResponse
    {
        try {
            $this->menuMasterService->rebuildMenuHierarchy();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Menu hierarchy rebuilt successfully',
                ]);
            }

            return redirect()->route('menumasters.index')
                ->with('success', 'Menu hierarchy rebuilt successfully');
        } catch (Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error rebuilding menu hierarchy: '.$e->getMessage(),
                ], 500);
            }

            return back()->withErrors(['error' => 'Error rebuilding menu hierarchy: '.$e->getMessage()]);
        }
    }

    /**
     * Export menu structure
     */
    public function export(Request $request): JsonResponse
    {
        $moduleName = $request->input('module_name');

        $query = MenuMaster::with('children')->ordered();

        if ($moduleName) {
            $query->byModule($moduleName);
        }

        $menus = $query->get();

        return response()->json([
            'success' => true,
            'data' => $menus,
            'exported_at' => now(),
            'module_name' => $moduleName,
        ]);
    }

    /**
     * Get menu statistics
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_menus' => MenuMaster::count(),
            'main_menus' => MenuMaster::where('is_main_menu', true)->count(),
            'sub_menus' => MenuMaster::where('is_main_menu', false)->count(),
            'menus_with_routes' => MenuMaster::whereNotNull('menu_route')->count(),
            'menus_with_permissions' => MenuMaster::whereNotNull('if_can')->count(),
            'modules' => MenuMaster::whereNotNull('module_name')->distinct()->count('module_name'),
            'max_depth' => $this->getMaxDepth(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Calculate maximum menu depth
     */
    private function getMaxDepth(): int
    {
        $maxDepth = 0;
        $menus = MenuMaster::all();

        foreach ($menus as $menu) {
            $depth = $menu->getLevel();
            if ($depth > $maxDepth) {
                $maxDepth = $depth;
            }
        }

        return $maxDepth;
    }

    /**
     * Normalize order display for comparison
     */
    private function normalizeOrderForComparison(string $orderDisplay): string
    {
        if (empty($orderDisplay) || $orderDisplay === '0.00') {
            return '001';
        }

        $parts = explode('.', $orderDisplay);
        $normalizedParts = array_map(function ($part) {
            return str_pad((int) $part, 3, '0', STR_PAD_LEFT);
        }, $parts);

        return implode('.', $normalizedParts);
    }
}
