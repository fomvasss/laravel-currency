<?php

namespace Fomvasss\Currency\Tests;

use Illuminate\Support\Facades\Blade;

class BladeDirectivesTest extends TestCase
{
    public function test_currency_symbol_directive_compiles_to_helper_call()
    {
        $compiled = Blade::compileString("@currencySymbol('USD')");

        $this->assertStringContainsString("currency_symbol('USD')", $compiled);
    }

    public function test_currency_symbol_directive_renders_symbol()
    {
        $compiled = Blade::compileString("@currencySymbol('USD')");

        ob_start();
        eval('?>' . $compiled);
        $output = ob_get_clean();

        $this->assertEquals('$', $output);
    }
}
