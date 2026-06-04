<?php

namespace Modules\MenuMaster\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Modules\MenuMaster\Models\MenuMaster;

class MenuMasterService
{
    public function handle() {}

    /**
     * Create a new menu item with proper ordering
     */
    public function createMenuItem(array $data): MenuMaster
    {
        $parentId = $data['parent_id'] ?? null;
        $orderDisplay = $this->generateOrderDisplay($parentId);

        $menuMaster = MenuMaster::create([
            'menu_icon' => $data['menu_icon'] ?? null,
            'menu_title' => $data['menu_title'],
            'menu_route' => $data['menu_route'] ?? null,
            'is_main_menu' => $data['is_main_menu'] ?? ($parentId === null),
            'parent_id' => $parentId,
            'module_name' => $data['module_name'] ?? null,
            'order_display' => $orderDisplay,
            'display_order' => $this->convertToHumanReadable($orderDisplay),
            'if_can' => $data['if_can'] ?? null,
        ]);

        return $menuMaster;
    }

    /**
     * Generate next order display for a given parent
     */
    private function generateOrderDisplay(?int $parentId): string
    {
        if ($parentId === null) {
            // Root level
            $lastRootItem = MenuMaster::whereNull('parent_id')
                ->orderBy('order_display', 'desc')
                ->first();

            if (! $lastRootItem || $lastRootItem->order_display === '0.00') {
                return '001';
            }

            // Handle existing non-zero-padded format
            if (strpos($lastRootItem->order_display, '.') === false) {
                $lastNumber = (int) $lastRootItem->order_display;
            } else {
                $parts = explode('.', $lastRootItem->order_display);
                $lastNumber = (int) $parts[0];
            }

            return str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        }

        // Child level
        $parent = MenuMaster::findOrFail($parentId);
        $lastChild = MenuMaster::where('parent_id', $parentId)
            ->orderBy('order_display', 'desc')
            ->first();

        $parentOrder = $this->normalizeOrderDisplay($parent->order_display);

        if (! $lastChild) {
            return $parentOrder.'.001';
        }

        // Extract the last segment and increment
        $childOrder = $this->normalizeOrderDisplay($lastChild->order_display);
        $parts = explode('.', $childOrder);
        $lastSegment = (int) end($parts);
        $parts[count($parts) - 1] = str_pad($lastSegment + 1, 3, '0', STR_PAD_LEFT);

        return implode('.', $parts);
    }

    /**
     * Normalize order display to zero-padded format
     */
    private function normalizeOrderDisplay(string $orderDisplay): string
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

    /**
     * Convert zero-padded order to human readable
     */
    private function convertToHumanReadable(string $orderDisplay): string
    {
        if (empty($orderDisplay) || $orderDisplay === '0.00') {
            return '1';
        }

        $parts = explode('.', $orderDisplay);
        $humanParts = array_map(fn ($part) => (int) $part, $parts);

        return implode('.', $humanParts);
    }

    /**
     * Update menu item
     */
    public function updateMenuItem(MenuMaster $menuMaster, array $data): MenuMaster
    {
        $oldParentId = $menuMaster->parent_id;
        $newParentId = $data['parent_id'] ?? null;

        // If parent is changing, handle the move
        if ($oldParentId != $newParentId) {
            $this->moveItem($menuMaster, $newParentId);
        }

        // Update other fields
        $menuMaster->update([
            'menu_icon' => $data['menu_icon'] ?? $menuMaster->menu_icon,
            'menu_title' => $data['menu_title'] ?? $menuMaster->menu_title,
            'menu_route' => $data['menu_route'] ?? $menuMaster->menu_route,
            'is_main_menu' => $data['is_main_menu'] ?? ($newParentId === null),
            'module_name' => $data['module_name'] ?? $menuMaster->module_name,
            'if_can' => $data['if_can'] ?? $menuMaster->if_can,
        ]);

        return $menuMaster->fresh();
    }

    /**
     * Move item to new position
     */
    public function moveItem(MenuMaster $item, ?int $newParentId, ?int $position = null): void
    {
        // Prevent moving item to itself or its descendants
        if ($newParentId && $this->isDescendant($item->id, $newParentId)) {
            throw new Exception('Cannot move menu item to its own descendant');
        }

        // If moving to different parent
        if ($item->parent_id !== $newParentId) {
            $this->moveToNewParent($item, $newParentId, $position);
        } else {
            // Reorder within same parent
            $this->reorderWithinParent($item, $position);
        }
    }

    /**
     * Check if a menu is descendant of another
     */
    private function isDescendant(int $ancestorId, int $descendantId): bool
    {
        $descendant = MenuMaster::find($descendantId);

        while ($descendant && $descendant->parent_id) {
            if ($descendant->parent_id == $ancestorId) {
                return true;
            }
            $descendant = $descendant->parent;
        }

        return false;
    }

    /**
     * Move item to new parent
     */
    private function moveToNewParent(MenuMaster $item, ?int $newParentId, ?int $position): void
    {
        $siblings = MenuMaster::where('parent_id', $newParentId)
            ->where('id', '!=', $item->id)
            ->orderBy('order_display')
            ->get();

        // Generate new order display
        $newOrderDisplay = $this->generateOrderDisplayAtPosition($newParentId, $position, $siblings);

        // Update the item and all its descendants
        $this->updateItemAndDescendants($item, $newOrderDisplay, $newParentId);
    }

    /**
     * Generate order display at specific position
     */
    private function generateOrderDisplayAtPosition(?int $parentId, ?int $position, Collection $siblings): string
    {
        $baseOrder = '';
        if ($parentId) {
            $parent = MenuMaster::find($parentId);
            $baseOrder = $this->normalizeOrderDisplay($parent->order_display).'.';
        }

        if ($position === null || $position >= $siblings->count()) {
            // Add at end
            if ($siblings->count() > 0) {
                $lastSibling = $siblings->last();
                $lastOrder = $this->normalizeOrderDisplay($lastSibling->order_display);
                $parts = explode('.', $lastOrder);
                $lastNumber = (int) end($parts);
            } else {
                $lastNumber = 0;
            }

            return $baseOrder.str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        }

        if ($position === 0) {
            // Add at beginning
            return $baseOrder.'001';
        }

        // Insert at specific position - need to reorder siblings
        $this->reorderSiblingsFromPosition($siblings, $position, $baseOrder);

        return $baseOrder.str_pad($position + 1, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Update item and all its descendants with new order
     */
    private function updateItemAndDescendants(MenuMaster $item, string $newOrderDisplay, ?int $newParentId): void
    {
        $oldOrderDisplay = $this->normalizeOrderDisplay($item->order_display);
        $newOrderDisplay = $this->normalizeOrderDisplay($newOrderDisplay);

        // Update the item itself
        $item->update([
            'order_display' => $newOrderDisplay,
            'display_order' => $this->convertToHumanReadable($newOrderDisplay),
            'parent_id' => $newParentId,
            'is_main_menu' => $newParentId === null,
        ]);

        // Update descendants
        $descendants = MenuMaster::where('order_display', 'LIKE', $oldOrderDisplay.'.%')
            ->orWhere('order_display', 'LIKE', $oldOrderDisplay.'%')
            ->where('id', '!=', $item->id)
            ->get();

        foreach ($descendants as $descendant) {
            $descendantOldOrder = $this->normalizeOrderDisplay($descendant->order_display);
            $newDescendantOrder = str_replace($oldOrderDisplay, $newOrderDisplay, $descendantOldOrder);

            $descendant->update([
                'order_display' => $newDescendantOrder,
                'display_order' => $this->convertToHumanReadable($newDescendantOrder),
            ]);
        }
    }

    /**
     * Reorder siblings from a specific position
     */
    private function reorderSiblingsFromPosition(Collection $siblings, int $fromPosition, string $baseOrder): void
    {
        $siblings->slice($fromPosition)->each(function ($sibling, $index) use ($baseOrder, $fromPosition) {
            $newPosition = $fromPosition + $index + 2; // +2 because we're inserting at $fromPosition + 1
            $newOrderDisplay = $baseOrder.str_pad($newPosition, 3, '0', STR_PAD_LEFT);

            $sibling->update([
                'order_display' => $newOrderDisplay,
                'display_order' => $this->convertToHumanReadable($newOrderDisplay),
            ]);
        });
    }

    /**
     * Reorder within same parent
     */
    private function reorderWithinParent(MenuMaster $item, ?int $position): void
    {
        if ($position === null) {
            return;
        }

        $siblings = MenuMaster::where('parent_id', $item->parent_id)
            ->where('id', '!=', $item->id)
            ->orderBy('order_display')
            ->get();

        $baseOrder = '';
        if ($item->parent_id) {
            $parent = MenuMaster::find($item->parent_id);
            $baseOrder = $this->normalizeOrderDisplay($parent->order_display).'.';
        }

        // Reorder all siblings including the moved item
        $allItems = $siblings->toArray();
        array_splice($allItems, $position, 0, [$item->toArray()]);

        foreach ($allItems as $index => $itemData) {
            $menuItem = MenuMaster::find($itemData['id']);
            $newOrderDisplay = $baseOrder.str_pad($index + 1, 3, '0', STR_PAD_LEFT);

            $menuItem->update([
                'order_display' => $newOrderDisplay,
                'display_order' => $this->convertToHumanReadable($newOrderDisplay),
            ]);
        }
    }

    /**
     * Get hierarchical menu tree
     */
    public function getMenuTree(?int $parentId = null, ?string $moduleName = null): Collection
    {
        $query = MenuMaster::with('children')
            ->where('parent_id', $parentId)
            ->ordered();

        if ($moduleName) {
            $query->byModule($moduleName);
        }

        return $query->get();
    }

    /**
     * Get flattened menu list with indentation
     */
    public function getFlattenedMenu(?string $moduleName = null): Collection
    {
        $query = MenuMaster::ordered();

        if ($moduleName) {
            $query->byModule($moduleName);
        }

        return $query->get()->map(function ($item) {
            $level = $item->getLevel();
            $indent = str_repeat('  ', $level - 1);
            $item->indented_title = $indent.$item->getHumanReadableOrder().' '.$item->menu_title;

            return $item;
        });
    }

    /**
     * Delete menu item and reorder siblings
     */
    public function deleteMenuItem(MenuMaster $item): void
    {
        $parentId = $item->parent_id;

        // Delete the item (cascade will handle descendants if foreign key is set)
        $item->delete();

        // Reorder remaining siblings
        $this->reorderSiblings($parentId);
    }

    /**
     * Reorder siblings after deletion
     */
    private function reorderSiblings(?int $parentId): void
    {
        $siblings = MenuMaster::where('parent_id', $parentId)
            ->orderBy('order_display')
            ->get();

        $baseOrder = '';
        if ($parentId) {
            $parent = MenuMaster::find($parentId);
            $baseOrder = $this->normalizeOrderDisplay($parent->order_display).'.';
        }

        $siblings->each(function ($sibling, $index) use ($baseOrder) {
            $newOrderDisplay = $baseOrder.str_pad($index + 1, 3, '0', STR_PAD_LEFT);

            if ($this->normalizeOrderDisplay($sibling->order_display) !== $newOrderDisplay) {
                $this->updateItemAndDescendants($sibling, $newOrderDisplay, $sibling->parent_id);
            }
        });
    }

    /**
     * Get navigation menu for specific module with permissions
     */
    public function getNavigationMenuForUser(?string $moduleName = null, $user = null): Collection
    {
        $user = $user ?: Auth::user();

        $menus = MenuMaster::with(['children' => function ($query) {
            $query->ordered();
        }])
            ->mainMenu()
            ->ordered();

        if ($moduleName) {
            $menus->byModule($moduleName);
        }

        $menus = $menus->get();

        // Filter by permissions
        return $menus->filter(function ($menu) use ($user) {
            return $menu->canAccess($user);
        })->map(function ($menu) use ($user) {
            // Filter children by permissions too
            $menu->setRelation('children', $menu->children->filter(function ($child) use ($user) {
                return $child->canAccess($user);
            }));

            return $menu;
        });
    }

    /**
     * Normalize all existing menu orders to zero-padded format
     */
    public function normalizeAllMenuOrders(): void
    {
        $allMenus = MenuMaster::orderBy('id')->get();

        foreach ($allMenus as $menu) {
            $normalizedOrder = $this->normalizeOrderDisplay($menu->order_display);

            if ($normalizedOrder !== $menu->order_display) {
                $menu->update([
                    'order_display' => $normalizedOrder,
                    'display_order' => $this->convertToHumanReadable($normalizedOrder),
                ]);
            }
        }
    }

    /**
     * Rebuild entire menu hierarchy (useful for fixing corrupted data)
     */
    public function rebuildMenuHierarchy(): void
    {
        // Get all root menus
        $rootMenus = MenuMaster::mainMenu()->orderBy('id')->get();

        foreach ($rootMenus as $index => $rootMenu) {
            $newOrder = str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $rootMenu->update([
                'order_display' => $newOrder,
                'display_order' => $this->convertToHumanReadable($newOrder),
            ]);

            $this->rebuildChildrenHierarchy($rootMenu, $newOrder);
        }
    }

    /**
     * Recursively rebuild children hierarchy
     */
    private function rebuildChildrenHierarchy(MenuMaster $parent, string $parentOrder): void
    {
        $children = MenuMaster::where('parent_id', $parent->id)->orderBy('id')->get();

        foreach ($children as $index => $child) {
            $newOrder = $parentOrder.'.'.str_pad($index + 1, 3, '0', STR_PAD_LEFT);
            $child->update([
                'order_display' => $newOrder,
                'display_order' => $this->convertToHumanReadable($newOrder),
            ]);

            $this->rebuildChildrenHierarchy($child, $newOrder);
        }
    }
}
