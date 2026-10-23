<?php

namespace Tests\Unit;

use App\Services\SummaryVerifier;
use PHPUnit\Framework\TestCase;

class SummaryVerifierTest extends TestCase
{
    private function verifier(): SummaryVerifier
    {
        return new SummaryVerifier();
    }

    public function test_all_figures_present_marks_verified(): void
    {
        $summary = 'KARRT shows a current ratio of 7.68, ROE of 83.05%, '
            . 'IRR of 33.42% against a 12% hurdle, and ₱3,000,000 payback in 4 years.';
        $text = 'Financial Ratios: Current Ratio 7.68 very strong. ROE 83.05%. '
            . 'IRR 33.42% exceeds 12%. The ₱ 3,000,000 investment recovered in 4 years.';

        $check = $this->verifier()->verify($summary, $text);

        $this->assertTrue($check['verified']);
        $this->assertSame([], $check['unsupported']);
        $this->assertContains('7.68', $check['checked']);
    }

    public function test_invented_figure_reported_as_unsupported(): void
    {
        $summary = 'Liquidity is strong with a current ratio of 9.99 and ROE of 83.05%.';
        $text = 'Current Ratio 7.68. ROE 83.05%.';

        $check = $this->verifier()->verify($summary, $text);

        $this->assertFalse($check['verified']);
        $this->assertSame(['9.99'], $check['unsupported']);
    }

    public function test_percent_and_currency_spacing_normalized(): void
    {
        $summary = 'Net margin 5.98% on ₱3,000,000 capital.';
        $text = 'Net Profit Margin 5.98 % of sales. Capital ₱ 3,000,000 total.';

        $this->assertTrue($this->verifier()->verify($summary, $text)['verified']);
    }

    public function test_partial_numbers_do_not_match(): void
    {
        // "19" must not match inside "0.19"; "0.19" must not match inside "10.19".
        $this->assertFalse($this->verifier()->verify('Debt ratio 19%.', 'Debt Ratio 0.19')['verified']);
        $this->assertFalse($this->verifier()->verify('Ratio is 0.19 now.', 'Ratio 10.19 recorded')['verified']);
    }

    public function test_trailing_sentence_period_does_not_break_match(): void
    {
        $summary = 'IRR of 33.42%.';
        $text = 'IRR of 33.42%. Next line.';

        $this->assertTrue($this->verifier()->verify($summary, $text)['verified']);
    }

    public function test_summary_without_numbers_is_unverified_not_error(): void
    {
        $check = $this->verifier()->verify('The document discusses financial assumptions.', 'Any text');

        $this->assertFalse($check['verified']);
        $this->assertSame([], $check['unsupported']);
        $this->assertSame([], $check['checked']);
    }

    public function test_spreadsheet_numbers_without_separators_match(): void
    {
        $summary = 'Total capital requirement of PHP 3,000,000 and notes payable of 800,000.';
        $text = 'TOTAL CAPITAL REQUIREMENT 3000000. Notes payable 800000 at 12%.';

        $this->assertTrue($this->verifier()->verify($summary, $text)['verified']);
    }

    public function test_word_magnitude_matches_full_digit_form(): void
    {
        $summary = 'The ₱3 million investment and NPV of 1.3 million recover quickly.';
        $text = 'Initial investment ₱3,000,000. NPV is 1300000 at 12% discount.';

        $this->assertTrue($this->verifier()->verify($summary, $text)['verified']);
    }

    public function test_trailing_commas_never_enter_claims(): void
    {
        $summary = 'Week 4, 2026, recorded 30 hours.';
        $text = 'Week 4 for SY 2026-2027. Total 30 hours rendered.';

        $check = $this->verifier()->verify($summary, $text);

        $this->assertTrue($check['verified'], 'Unsupported: ' . implode(', ', $check['unsupported']));
        foreach ($check['checked'] as $claim) {
            $this->assertStringEndsNotWith(',', $claim);
        }
    }

    public function test_doc44_summary_passes_against_doc44_text(): void
    {
        $summary = 'KARRT exhibits very strong liquidity with a current ratio of 7.68 and a quick ratio of 7.30, indicating ample short-term working capital. Its leverage is low, reflected by a debt ratio of 0.19, equity ratio of 0.80, and debt-to-equity of 0.24, showing limited reliance on borrowed funds. Profitability metrics are robust, including a net profit margin of 5.98%, ROA of 13.35%, ROE of 83.05% and ROI of 18.79%, while the internal rate of return of 33.42% far exceeds the 12% required rate. Operational efficiency is highlighted by an asset turnover of 2.23, a market share of 5.58%, and a four-year payback period for the ₱3,000,000 investment.';
        $text = 'Table Financial Ratios Overview Category Ratio Formula Result Interpretation '
            . 'Liquidity Current Ratio Current Assets ÷ Current Liabilities 7.68 KARRT has ₱ 7.68 in current assets. '
            . 'Liquidity Quick Ratio (Cash + Receivables) ÷ Current Liabilities 7.30. '
            . 'Leverage Debt Ratio Total Liabilities ÷ Total Assets 0.19. '
            . 'Leverage Equity Ratio Equity ÷ Total Assets 0.80. '
            . 'Leverage Debt-to-Equity Ratio Total Liabilities ÷ Equity 0.24. '
            . 'Profitability Net Profit Margin Net Income ÷ Sales 5.98 %. '
            . 'Profitability Return on Assets (ROA) Net Income ÷ Total Assets 13.35%. '
            . 'Profitability Return on Equity (ROE) Net Income ÷ Equity 83.05%. '
            . 'Profitability Return on Investment (ROI) Net Income ÷ Total Investment 18.79%. '
            . 'Investment Internal Rate of Return Discount Rate where NPV = 0 33.42 % exceeds the required rate of return of 12%. '
            . 'Efficiency Asset Turnover Sales ÷ Total Assets 2.23. '
            . 'Market Market Share Revenue ÷ Industry Revenue 5.58%. '
            . 'Market Payback Period Initial Investment ÷ Annual Net Cash Inflow 4 years. '
            . 'The ₱ 3,000,000 investment will be recovered within four years.';

        $check = $this->verifier()->verify($summary, $text);

        $this->assertSame([], $check['unsupported'], 'Unsupported: ' . implode(', ', $check['unsupported']));
        $this->assertTrue($check['verified']);
    }
}
