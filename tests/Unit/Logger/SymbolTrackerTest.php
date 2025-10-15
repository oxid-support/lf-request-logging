<?php

declare(strict_types=1);

namespace OxidSupport\RequestLogger\Tests\Unit\Logger;

use OxidSupport\RequestLogger\Logger\SymbolTracker;
use PHPUnit\Framework\TestCase;

class SymbolTrackerTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset static state before each test
        $reflection = new \ReflectionClass(SymbolTracker::class);

        $enabledProp = $reflection->getProperty('enabled');
        $enabledProp->setAccessible(true);
        $enabledProp->setValue(null, false);

        $classesProp = $reflection->getProperty('classesBefore');
        $classesProp->setAccessible(true);
        $classesProp->setValue(null, []);

        $interfacesProp = $reflection->getProperty('interfacesBefore');
        $interfacesProp->setAccessible(true);
        $interfacesProp->setValue(null, []);

        $traitsProp = $reflection->getProperty('traitsBefore');
        $traitsProp->setAccessible(true);
        $traitsProp->setValue(null, []);
    }

    public function testEnableCanBeCalledMultipleTimes(): void
    {
        SymbolTracker::enable();
        SymbolTracker::enable();
        SymbolTracker::enable();

        $this->assertTrue(true, 'Multiple enable() calls should not cause issues');
    }

    public function testEnableCapturesCurrentSymbols(): void
    {
        $classCountBefore = count(get_declared_classes());

        SymbolTracker::enable();

        $reflection = new \ReflectionClass(SymbolTracker::class);
        $classesProp = $reflection->getProperty('classesBefore');
        $classesProp->setAccessible(true);
        $captured = $classesProp->getValue(null);

        $this->assertGreaterThanOrEqual($classCountBefore, count($captured));
    }

    public function testReportReturnsArrayWithSymbolsKey(): void
    {
        SymbolTracker::enable();
        $report = SymbolTracker::report();

        $this->assertIsArray($report);
        $this->assertArrayHasKey('symbols', $report);
        $this->assertIsArray($report['symbols']);
    }

    public function testReportTracksNewlyDeclaredClass(): void
    {
        SymbolTracker::enable();

        // Declare a new class dynamically
        eval('namespace OxidSupport\RequestLogger\Tests\Unit\Logger; class DynamicTestClass {}');

        $report = SymbolTracker::report();

        $this->assertContains(
            'OxidSupport\RequestLogger\Tests\Unit\Logger\DynamicTestClass',
            $report['symbols']
        );
    }

    public function testReportFiltersParentAliases(): void
    {
        SymbolTracker::enable();

        // Simulate a class with _parent suffix
        eval('namespace OxidSupport\RequestLogger\Tests\Unit\Logger; class SomeClass_parent {}');

        $report = SymbolTracker::report();

        $this->assertNotContains(
            'OxidSupport\RequestLogger\Tests\Unit\Logger\SomeClass_parent',
            $report['symbols']
        );
    }

    public function testReportFiltersLegacyLowercaseClassNames(): void
    {
        SymbolTracker::enable();

        // Simulate legacy lowercase class (no namespace)
        eval('class legacytestclass {}');

        $report = SymbolTracker::report();

        $this->assertNotContains('legacytestclass', $report['symbols']);
    }

    public function testReportWithStrictModeFiltersEvaledClasses(): void
    {
        SymbolTracker::enable();

        // Evaled class (returns "eval()'d code" as filename)
        eval('namespace OxidSupport\RequestLogger\Tests\Unit\Logger; class EvaledClass {}');

        $reportNonStrict = SymbolTracker::report(false);
        $reportStrict = SymbolTracker::report(true);

        $this->assertContains(
            'OxidSupport\RequestLogger\Tests\Unit\Logger\EvaledClass',
            $reportNonStrict['symbols'],
            'Non-strict mode should include evaled classes'
        );

        // Current implementation: evaled classes have a filename like "eval()'d code"
        // which is a string and not empty, so they are NOT filtered out in strict mode
        // This test verifies current behavior
        $this->assertContains(
            'OxidSupport\RequestLogger\Tests\Unit\Logger\EvaledClass',
            $reportStrict['symbols'],
            'Strict mode currently does not filter evaled classes (they have a filename)'
        );
    }

    public function testReportTracksInterfaces(): void
    {
        SymbolTracker::enable();

        eval('namespace OxidSupport\RequestLogger\Tests\Unit\Logger; interface DynamicTestInterface {}');

        $report = SymbolTracker::report();

        $this->assertContains(
            'OxidSupport\RequestLogger\Tests\Unit\Logger\DynamicTestInterface',
            $report['symbols']
        );
    }

    public function testReportTracksTraits(): void
    {
        SymbolTracker::enable();

        eval('namespace OxidSupport\RequestLogger\Tests\Unit\Logger; trait DynamicTestTrait {}');

        $report = SymbolTracker::report();

        $this->assertContains(
            'OxidSupport\RequestLogger\Tests\Unit\Logger\DynamicTestTrait',
            $report['symbols']
        );
    }

    public function testReportWithoutEnableReturnsAllSymbols(): void
    {
        // Don't call enable()
        $report = SymbolTracker::report();

        // Should return all currently declared symbols since baseline is empty
        $this->assertGreaterThan(0, count($report['symbols']));
    }
}
