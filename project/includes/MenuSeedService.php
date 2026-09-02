<?php

class MenuSeedService
{
    public static function getTreeData()
    {
        return require __DIR__ . '/data/menu_seed_tree.php';
    }

    public static function countNodes(array $nodes)
    {
        $count = 0;
        foreach ($nodes as $node) {
            $count++;
            if (!empty($node['children'])) {
                $count += self::countNodes($node['children']);
            }
        }
        return $count;
    }

    public static function resetProjectMenus($projectId)
    {
        $db = Database::getConnection();
        $db->prepare('DELETE FROM menus WHERE project_id = ?')->execute(array((int) $projectId));
    }

    public static function seedProject($projectId)
    {
        self::resetProjectMenus($projectId);
        self::insertNodes((int) $projectId, self::getTreeData(), null);
        MenuService::rebuildCodes($projectId);
        StoryboardFileService::syncStubsForProject($projectId);
        ProjectService::updateProgress($projectId);
        return self::countNodes(self::getTreeData());
    }

    private static function insertNodes($projectId, array $nodes, $parentId)
    {
        $order = 0;
        foreach ($nodes as $node) {
            $id = MenuService::create($projectId, array(
                'title' => $node['title'],
                'parent_id' => $parentId,
                'sort_order' => $order++,
            ));
            if (!empty($node['children'])) {
                self::insertNodes($projectId, $node['children'], $id);
            }
        }
    }
}
