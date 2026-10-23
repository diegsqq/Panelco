<?php

namespace Tests\Unit;

use App\Services\StructuredTableExtractor;
use PHPUnit\Framework\TestCase;

class StructuredTableExtractorTest extends TestCase
{
    private const RATIOS_TEXT = 'Table Financial Ratios Overview Category Ratio Formula Result Interpretation '
        . 'Liquidity Current Ratio Current Assets ÷ Current Liabilities 7.68 KARRT has ₱ 7.68 in current assets for every ₱ 1.00 of current liabilities, indicating very strong liquidity. '
        . 'Liquidity Quick Ratio (Cash + Receivables) ÷ Current Liabilities 7.30 Immediate obligations can be fully covered using highly liquid assets. This reflects excellent short-term stability. '
        . 'Leverage Debt Ratio Total Liabilities ÷ Total Assets 0.19 Only 19% of total assets are financed by debt, indicating very low financial risk. '
        . 'Leverage Debt-to-Equity Ratio Total Liabilities ÷ Equity 0.24 For every ₱ 1.00 of equity, only ₱ 0.24 is financed by debt. This shows conservative leverage. '
        . 'Profitability Return on Assets (ROA) Net Income ÷ Total Assets 13.35% Assets are efficiently utilized, generating profit per peso invested in assets. '
        . 'Investment Internal Rate of Return Discount Rate where NPV = 0 33.42 % The IRR significantly exceeds the required rate of return of 12%, confirming financial feasibility. '
        . 'Market Payback Period Initial Investment ÷ Annual Net Cash Inflow 4 years The investment will be recovered within four years, indicating moderate risk.';

    private StructuredTableExtractor $extractor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extractor = new StructuredTableExtractor();
    }

    public function test_extracts_financial_ratios_table(): void
    {
        $tables = $this->extractor->extract(self::RATIOS_TEXT);

        $this->assertCount(1, $tables);
        $table = $tables[0];

        $this->assertSame('Financial Ratios Overview', $table['caption']);
        $this->assertSame(
            ['Category', 'Ratio', 'Formula', 'Result', 'Interpretation'],
            $table['headers']
        );
        $this->assertCount(7, $table['rows']);
    }

    public function test_splits_ratio_and_formula_cells(): void
    {
        $rows = $this->extractor->extract(self::RATIOS_TEXT)[0]['rows'];

        $this->assertSame(
            ['Liquidity', 'Current Ratio', 'Current Assets ÷ Current Liabilities', '7.68'],
            array_slice($rows[0], 0, 4)
        );
        // "+"/"÷" inside a parenthesized formula must stay in the formula cell.
        $this->assertSame(
            ['Liquidity', 'Quick Ratio', '(Cash + Receivables) ÷ Current Liabilities', '7.30'],
            array_slice($rows[1], 0, 4)
        );
        // Hyphenated ratio names must not be mistaken for subtraction.
        $this->assertSame(
            ['Leverage', 'Debt-to-Equity Ratio', 'Total Liabilities ÷ Equity', '0.24'],
            array_slice($rows[3], 0, 4)
        );
    }

    public function test_handles_multi_sentence_interpretations(): void
    {
        $rows = $this->extractor->extract(self::RATIOS_TEXT)[0]['rows'];

        // Row 1's interpretation has two sentences; the row must not break
        // between them.
        $this->assertSame(
            'Immediate obligations can be fully covered using highly liquid assets. This reflects excellent short-term stability.',
            $rows[1][4]
        );
        $this->assertSame('Leverage', $rows[2][0]);
    }

    public function test_quoted_numbers_inside_interpretations_are_not_results(): void
    {
        $rows = $this->extractor->extract(self::RATIOS_TEXT)[0]['rows'];

        // "₱ 0.24" inside row 3's interpretation must not shift later rows.
        $this->assertSame('0.24', $rows[3][3]);
        $this->assertSame('13.35%', $rows[4][3]);
        // "12%" quoted mid-interpretation likewise.
        $this->assertSame('33.42%', $rows[5][3]);
    }

    public function test_where_formulas_and_unit_results(): void
    {
        $rows = $this->extractor->extract(self::RATIOS_TEXT)[0]['rows'];

        $this->assertSame(
            ['Investment', 'Internal Rate of Return', 'Discount Rate where NPV = 0', '33.42%'],
            array_slice($rows[5], 0, 4)
        );
        $this->assertSame('4 years', $rows[6][3]);
    }

    public function test_prose_without_tables_returns_empty(): void
    {
        $this->assertSame([], $this->extractor->extract(
            'The board met on 12 June 2025. Attendance was 5 members. '
            . 'Quorum was established at 5. The minutes were approved.'
        ));
        $this->assertSame([], $this->extractor->extract(null));
        $this->assertSame([], $this->extractor->extract(''));
    }

    public function test_spreadsheet_cell_dumps_are_not_tables(): void
    {
        // Pipe/newline separated cells: extraction artifact, not a table row.
        $this->assertSame([], $this->extractor->extract(
            "ZARATE, JEDDI 02-10-1006-2869 | MANINGDING, ROGELIO S. | 5512 02-10-1013-0249 | PARAYNO, JIMMY D. | 519. "
            . "ESPASOL, JOSE V. 02-10-1090-0285 | BERNANDINO, EDGAR B. | 15453 02-10-1090-0380 | RAYRAY, ALVIN BUENO. "
            . "MANNIX CARANCHO COMMERCIAL COMPLEX | 17 02-10-1099-0669 | PAN 02-10-1099-0800 | SANTOS, JUAN | 8891."
        ));
    }
}
