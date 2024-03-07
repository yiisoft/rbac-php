# Yii RBAC PHP File Storage Change Log

## 2.0.1 under development

- no changes in this release.

## 2.0.0 March 07, 2024

- Enh #90, 91: Use 1 file path argument for storages (@arogachev)
- Chg #63, #76: Raise PHP version to 8.1 (@arogachev)
- Enh #50: Save `Assignment::$createdAt` (@arogachev)
- Enh #51: Save `Item::$createdAt` and `Item::$updatedAt` (@arogachev)
- Enh #52: Handle concurrency when working with storages (@arogachev)
- Enh #63: Improve performance (@arogachev)
- Enh #70, #94: Sync with base package (implement interface methods) (@arogachev)
- Enh #76: Use simple storages for items and assignments from the base `rbac` package (@arogachev)
- Enh #77: Use snake case for item attribute names (ease migration from Yii 2) (@arogachev)
- Enh #87: Move handling same names during renaming item in `AssignmentsStorage` to base package (@arogachev)

## 1.0.0 April 08, 2022

- Initial release.
