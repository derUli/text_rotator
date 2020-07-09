<?php

abstract class TextRotatorBaseTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $migrator = new DBMigrator(
            "text_rotator",
            ModuleHelper::buildRessourcePath("text_rotator", "migrations/up")
        );
        $migrator->migrate();
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
