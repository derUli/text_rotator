<?php

abstract class TextRotatorBaseTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        Vars::set("script_queue", []);

        $migrator = new DBMigrator(
            "text_rotator",
            ModuleHelper::buildRessourcePath("text_rotator", "migrations/up")
        );
        $migrator->migrate();

        include_once getLanguageFilePath("en");
        Translation::loadAllModuleLanguageFiles("en");
    }

    protected function tearDown(): void
    {
        $migrator = new DBMigrator(
            "text_rotator",
            ModuleHelper::buildRessourcePath("text_rotator", "migrations/down")
        );
        $migrator->rollback();
    }
}
