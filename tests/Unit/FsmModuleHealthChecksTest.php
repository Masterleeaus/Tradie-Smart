<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FsmModuleHealthChecksTest extends TestCase
{
    private string $repoRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repoRoot = dirname(__DIR__, 2);
    }

    public function test_fsm_vehicle_health_checks_are_not_placeholder(): void
    {
        $content = file_get_contents($this->repoRoot . '/Modules/FSMVehicle/health/checks.php');

        $this->assertStringNotContainsString('return [];', $content);
        $this->assertStringContainsString("'id'       => 'fsmvehicle:module_json'", $content);
        $this->assertStringContainsString("'id'       => 'fsmvehicle:service_provider'", $content);
        $this->assertStringContainsString("'id'       => 'fsmvehicle:migrations'", $content);
    }

    public function test_fsm_workflow_health_checks_include_core_structure_guards(): void
    {
        $content = file_get_contents($this->repoRoot . '/Modules/FSMWorkflow/health/checks.php');

        $this->assertStringContainsString("'id'       => 'fsmworkflow:module_json'", $content);
        $this->assertStringContainsString("'id'       => 'fsmworkflow:service_provider'", $content);
        $this->assertStringContainsString("'id'       => 'fsmworkflow:routes_web'", $content);
    }

    public function test_fsm_activity_health_checks_are_defined(): void
    {
        $content = file_get_contents($this->repoRoot . '/Modules/FSMActivity/health/checks.php');

        $this->assertStringContainsString("'id'       => 'fsmactivity:module_json'", $content);
        $this->assertStringContainsString("'id'       => 'fsmactivity:service_provider'", $content);
        $this->assertStringContainsString("'id'       => 'fsmactivity:fsm_core_dep'", $content);
    }

    public function test_fsm_equipment_health_checks_are_defined(): void
    {
        $content = file_get_contents($this->repoRoot . '/Modules/FSMEquipment/health/checks.php');

        $this->assertStringContainsString("'id'       => 'fsmequipment:module_json'", $content);
        $this->assertStringContainsString("'id'       => 'fsmequipment:migrations'", $content);
        $this->assertStringContainsString("'id'       => 'fsmequipment:fsm_core_dep'", $content);
    }

    public function test_fsm_recurring_health_checks_are_defined(): void
    {
        $content = file_get_contents($this->repoRoot . '/Modules/FSMRecurring/health/checks.php');

        $this->assertStringContainsString("'id'       => 'fsmrecurring:module_json'", $content);
        $this->assertStringContainsString("'id'       => 'fsmrecurring:service_provider'", $content);
        $this->assertStringContainsString("'id'       => 'fsmrecurring:fsm_core_dep'", $content);
    }

    public function test_fsm_skill_health_checks_are_defined(): void
    {
        $content = file_get_contents($this->repoRoot . '/Modules/FSMSkill/health/checks.php');

        $this->assertStringContainsString("'id'       => 'fsmskill:module_json'", $content);
        $this->assertStringContainsString("'id'       => 'fsmskill:migrations'", $content);
        $this->assertStringContainsString("'id'       => 'fsmskill:fsm_core_dep'", $content);
    }

    public function test_fsm_stock_health_checks_are_defined(): void
    {
        $content = file_get_contents($this->repoRoot . '/Modules/FSMStock/health/checks.php');

        $this->assertStringContainsString("'id'       => 'fsmstock:module_json'", $content);
        $this->assertStringContainsString("'id'       => 'fsmstock:migrations'", $content);
        $this->assertStringContainsString("'id'       => 'fsmstock:fsm_core_dep'", $content);
    }

    public function test_fsm_timesheet_health_checks_are_defined(): void
    {
        $content = file_get_contents($this->repoRoot . '/Modules/FSMTimesheet/health/checks.php');

        $this->assertStringContainsString("'id'       => 'fsmtimesheet:module_json'", $content);
        $this->assertStringContainsString("'id'       => 'fsmtimesheet:service_provider'", $content);
        $this->assertStringContainsString("'id'       => 'fsmtimesheet:fsm_core_dep'", $content);
    }

}
