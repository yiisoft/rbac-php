# Yii RBAC PHP File Storage Change Log

## 2.1.0 May 14, 2026

- Chg #111, #119: Change PHP constraint in `composer.json` to `8.1 - 8.5` (@vjik)
- Enh #110: Bump `yiisoft/rbac` version to `^2.1` (@vjik)
- Enh #115: Apply code style fixes (@vjik)
- Enh #115: Explicitly import functions and constants in "use" section (@vjik)

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
