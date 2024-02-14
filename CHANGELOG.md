# Yii RBAC PHP File Storage Change Log

## 2.0.0 under development

- Chg #63: Raise PHP version to 8.0 (@arogachev)
- Enh #63: Improve performance (@arogachev)
- Enh #70: Implement `getByNames()` and `getAccessTree()` methods in `ItemsStorage` (@arogachev)
- Enh #70: Implement `filterUserItemNames()` method in `AssignmentsStorage` (@arogachev)
- Chg #70: Rename `$name` argument to `$names` and allow array type for it in `getAllChildren()`, `getAllChildRoles()`,
  `getAllChildPermissions()` methods in `ItemsStorage` (@arogachev)
- Enh #76: Use simple storages for items and assignments from the base `rbac` package (@arogachev)
- Chg #76: Raise PHP version to 8.1 (@arogachev)
- Enh #77: Use snake case for item attribute names (ease migration from Yii 2) (@arogachev)
- Enh #51: Save `Item::$createdAt` and `Item::$updatedAt` (@arogachev)
- Enh #50: Save `Assignment::$createdAt` (@arogachev)
- Enh #52: Handle concurrency when working with storages (@arogachev)
- Enh #87: Move handling same names during renaming item in `AssignmentsStorage` to base package (@arogachev)
- Chg #90: Adjust naming for storages' file name (@arogachev)
- Chg #: Rename `getAccessTree()` to `getHierarchy()` in `ItemsStorageInterface` implementations (@arogachev)

## 1.0.0 April 08, 2022

- Initial release.
