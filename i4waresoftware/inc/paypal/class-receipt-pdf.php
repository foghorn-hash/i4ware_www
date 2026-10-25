<?php
/**
 * i4ware Receipt PDF Document Builder
 *
 * Renders executive-grade official PDF receipts and credit notes for i4ware Software.
 * Complies fully with:
 * - Finnish VAT Act (Arvonlisäverolaki 209 e § tositemerkinnät)
 * - Required seller & buyer details
 * - Sequential invoice numbering
 * - Net, VAT %, VAT amount, Gross total
 * - Visibility period & ad specification
 * - PayPal transaction ID
 * - Clear "Maksettu – ei maksettavaa" or "Hyvitystosite" status marking
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/class-pdf-engine.php';

class I4ware_Receipt_PDF
{
    /**
     * Generate PDF receipt binary string.
     *
     * @param array $data Complete receipt data
     * @return string Binary PDF content
     */
    public static function build(array $data)
    {
        $pdf = new I4ware_PDF_Engine();
        $pdf->SetMargins(15, 15, 15);
        $pdf->AddPage();

        $is_credit_note = (!empty($data['receipt_type']) && $data['receipt_type'] === 'credit_note');

        // 1. Seller Header & Logo text
        $pdf->SetXY(15, 15);
        $pdf->SetTextColor(15, 23, 42); // slate-900
        $pdf->SetFont('Helvetica', 'B', 20);
        $pdf->Cell(100, 8, I4ware_PDF_Engine::conv('i4ware Software'), 0, 1, 'L');

        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(71, 85, 105); // slate-600
        $pdf->Cell(100, 4.5, I4ware_PDF_Engine::conv('Toiminimi | Yksityinen elinkeinonharjoittaja'), 0, 1, 'L');
        $pdf->Cell(100, 4.5, I4ware_PDF_Engine::conv('Y-tunnus: 2739594-6 | ALV-tunniste: FI27395946'), 0, 1, 'L');
        $pdf->Cell(100, 4.5, I4ware_PDF_Engine::conv('Kasarmikatu 15, 13100 Hämeenlinna, Finland'), 0, 1, 'L');
        $pdf->Cell(100, 4.5, I4ware_PDF_Engine::conv('Sähköposti: info@i4ware.fi | https://i4ware.fi'), 0, 1, 'L');

        // 2. Document Title & Badge (Right aligned)
        $pdf->SetXY(115, 15);
        if ($is_credit_note) {
            $pdf->SetTextColor(190, 18, 60); // rose-700
            $pdf->SetFont('Helvetica', 'B', 18);
            $pdf->Cell(80, 8, I4ware_PDF_Engine::conv('HYVITYSTOSITE'), 0, 1, 'R');

            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(180, 5, I4ware_PDF_Engine::conv('CREDIT NOTE'), 0, 1, 'R');

            // Credit Note Status Badge
            $pdf->SetXY(135, 30);
            $pdf->SetFillColor(255, 241, 242); // rose-50
            $pdf->SetDrawColor(244, 63, 94);  // rose-500
            $pdf->SetLineWidth(0.3);
            $pdf->RoundedRect(135, 30, 60, 8, 2, 'FD');
            $pdf->SetTextColor(159, 18, 57);
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->Cell(60, 8, I4ware_PDF_Engine::conv('HYVITYS MAKSETTU'), 0, 1, 'C');
        } else {
            $pdf->SetTextColor(15, 23, 42);
            $pdf->SetFont('Helvetica', 'B', 18);
            $pdf->Cell(80, 8, I4ware_PDF_Engine::conv('MAATIKUUTTI / TOSITE'), 0, 1, 'R');

            $pdf->SetFont('Helvetica', '', 9);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(180, 5, I4ware_PDF_Engine::conv('PAYMENT RECEIPT'), 0, 1, 'R');

            // Paid Status Badge: "Maksettu – ei maksettavaa"
            $pdf->SetXY(130, 30);
            $pdf->SetFillColor(236, 253, 245); // emerald-50
            $pdf->SetDrawColor(16, 185, 129);  // emerald-500
            $pdf->SetLineWidth(0.3);
            $pdf->RoundedRect(130, 30, 65, 8, 2, 'FD');
            $pdf->SetTextColor(6, 95, 70); // emerald-800
            $pdf->SetFont('Helvetica', 'B', 8.5);
            $pdf->Cell(65, 8, I4ware_PDF_Engine::conv('MAKSETTU – EI MAKSETTAVAA'), 0, 1, 'C');
        }

        // Horizontal divider
        $pdf->SetDrawColor(226, 232, 240); // slate-200
        $pdf->SetLineWidth(0.4);
        $pdf->Line(15, 43, 195, 43);

        // 3. Customer Box & Invoice Details (Two-column layout)
        $startY = 48;

        // LEFT COLUMN: Customer Information
        $pdf->SetXY(15, $startY);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(88, 5, I4ware_PDF_Engine::conv('ASIAKKAAN TIEDOT / BILLED TO:'), 0, 1, 'L');

        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetTextColor(15, 23, 42);
        $customer_display_name = !empty($data['company_name']) ? $data['company_name'] : $data['customer_name'];
        $pdf->Cell(88, 5.5, I4ware_PDF_Engine::conv($customer_display_name), 0, 1, 'L');

        if (!empty($data['company_name']) && !empty($data['customer_name']) && $data['company_name'] !== $data['customer_name']) {
            $pdf->SetFont('Helvetica', '', 8.5);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell(88, 4.5, I4ware_PDF_Engine::conv('Yhteyshenkilö: ' . $data['customer_name']), 0, 1, 'L');
        }

        if (!empty($data['vat_number'])) {
            $pdf->SetFont('Helvetica', '', 8.5);
            $pdf->SetTextColor(71, 85, 105);
            $pdf->Cell(88, 4.5, I4ware_PDF_Engine::conv('ALV-tunniste: ' . $data['vat_number']), 0, 1, 'L');
        }

        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(71, 85, 105);
        $address = !empty($data['billing_address']) ? $data['billing_address'] : '';
        if (!empty($data['billing_country'])) {
            $address .= ', ' . $data['billing_country'];
        }
        $pdf->MultiCell(88, 4.5, I4ware_PDF_Engine::conv($address), 0, 'L');
        $pdf->Cell(88, 4.5, I4ware_PDF_Engine::conv('Sähköposti: ' . $data['customer_email']), 0, 1, 'L');

        // RIGHT COLUMN: Invoice Details Box
        $pdf->SetXY(112, $startY);
        $pdf->SetFillColor(248, 250, 252); // slate-50
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->RoundedRect(112, $startY, 83, 38, 2, 'FD');

        $pdf->SetXY(115, $startY + 2.5);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(38, 4.8, I4ware_PDF_Engine::conv('Tositenumero:'), 0, 0, 'L');
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell(42, 4.8, I4ware_PDF_Engine::conv($data['receipt_number']), 0, 1, 'R');

        if ($is_credit_note && !empty($data['original_receipt_number'])) {
            $pdf->SetX(115);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->SetTextColor(190, 18, 60);
            $pdf->Cell(38, 4.5, I4ware_PDF_Engine::conv('Alkuperäinen tosite:'), 0, 0, 'L');
            $pdf->Cell(42, 4.5, I4ware_PDF_Engine::conv($data['original_receipt_number']), 0, 1, 'R');
        }

        $pdf->SetX(115);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(38, 4.5, I4ware_PDF_Engine::conv('Kuitin päiväys:'), 0, 0, 'L');
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(42, 4.5, I4ware_PDF_Engine::conv(!empty($data['receipt_date']) ? $data['receipt_date'] : gmdate('d.m.Y')), 0, 1, 'R');

        $pdf->SetX(115);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(38, 4.5, I4ware_PDF_Engine::conv('Maksupäivä:'), 0, 0, 'L');
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(42, 4.5, I4ware_PDF_Engine::conv(!empty($data['payment_date']) ? $data['payment_date'] : gmdate('d.m.Y H:i') . ' UTC'), 0, 1, 'R');

        $pdf->SetX(115);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(38, 4.5, I4ware_PDF_Engine::conv('Maksutapa:'), 0, 0, 'L');
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(42, 4.5, I4ware_PDF_Engine::conv('PayPal'), 0, 1, 'R');

        $pdf->SetX(115);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(38, 4.5, I4ware_PDF_Engine::conv('PayPal-tapahtuma:'), 0, 0, 'L');
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell(42, 4.5, I4ware_PDF_Engine::conv($data['paypal_transaction_id']), 0, 1, 'R');

        // 4. Advertising Service Scope & Visibility Period Box
        $scopeY = 92;
        $pdf->SetXY(15, $scopeY);
        $pdf->SetFillColor(241, 245, 249); // slate-100
        $pdf->SetDrawColor(203, 213, 225); // slate-300
        $pdf->RoundedRect(15, $scopeY, 180, 17, 2, 'FD');

        $pdf->SetXY(18, $scopeY + 2);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->SetTextColor(30, 41, 59);
        $pdf->Cell(174, 4.5, I4ware_PDF_Engine::conv('MAINOSPALVELUN NÄKYVYYS JA KOHDENNUS:'), 0, 1, 'L');

        $pdf->SetX(18);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(71, 85, 105);
        $visibility_str = 'Näkyvyyden kesto: ' . (!empty($data['visibility_period']) ? $data['visibility_period'] : '30 päivää määräaikainen');
        if (!empty($data['ad_format']) || !empty($data['ad_placement'])) {
            $visibility_str .= ' | Muoto: ' . $data['ad_format'] . ' | Sijoittelu: ' . $data['ad_placement'];
        }
        $pdf->Cell(174, 4.5, I4ware_PDF_Engine::conv($visibility_str), 0, 1, 'L');

        $pdf->SetX(18);
        $pdf->SetFont('Helvetica', 'I', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(174, 4, I4ware_PDF_Engine::conv('Aineiston toimitus: info@i4ware.fi. Mainos julkaistaan sovitussa muodossa toimitusehtojen mukaisesti.'), 0, 1, 'L');

        // 5. Line Items Table
        $tableY = 115;
        $pdf->SetXY(15, $tableY);

        // Table Header
        $pdf->SetFillColor(15, 23, 42); // slate-900
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(90, 7, I4ware_PDF_Engine::conv(' Tuote / Palvelukuvaus'), 0, 0, 'L', true);
        $pdf->Cell(15, 7, I4ware_PDF_Engine::conv('Määrä'), 0, 0, 'C', true);
        $pdf->Cell(25, 7, I4ware_PDF_Engine::conv('Veroton'), 0, 0, 'R', true);
        $pdf->Cell(20, 7, I4ware_PDF_Engine::conv('ALV %'), 0, 0, 'R', true);
        $pdf->Cell(30, 7, I4ware_PDF_Engine::conv('Yhteensä '), 0, 1, 'R', true);

        // Table Row
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Helvetica', '', 8.5);

        $rowHeight = 12;
        $pdf->SetX(15);
        $pdf->Rect(15, $tableY + 7, 180, $rowHeight, 'D');

        // Item title
        $pdf->SetXY(17, $tableY + 8.5);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $item_title = !empty($data['item_name']) ? $data['item_name'] : 'Mainospalvelu verkkosivustolla';
        if ($is_credit_note) {
            $item_title = 'HYVITYS: ' . $item_title;
        }
        $pdf->Cell(88, 4.5, I4ware_PDF_Engine::conv($item_title), 0, 1, 'L');

        $pdf->SetX(17);
        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $desc = !empty($data['ad_description']) ? $data['ad_description'] : 'Mainostila sovittuna ajanjaksona';
        $pdf->Cell(88, 4, I4ware_PDF_Engine::conv($desc), 0, 0, 'L');

        // Qty
        $pdf->SetXY(105, $tableY + 10);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(15, 5, I4ware_PDF_Engine::conv('1 kpl'), 0, 0, 'C');

        // Net amount
        $sign = $is_credit_note ? -1 : 1;
        $net_amount = (float) $data['net_amount'] * $sign;
        $pdf->SetXY(120, $tableY + 10);
        $pdf->Cell(25, 5, I4ware_PDF_Engine::conv(number_format($net_amount, 2, ',', ' ') . ' €'), 0, 0, 'R');

        // VAT %
        $vat_rate = ($data['vat_rate'] !== null) ? (float) $data['vat_rate'] : 0.0;
        $pdf->SetXY(145, $tableY + 10);
        $pdf->Cell(20, 5, I4ware_PDF_Engine::conv(number_format($vat_rate, 1, ',', ' ') . ' %'), 0, 0, 'R');

        // Gross total
        $gross_amount = (float) $data['gross_amount'] * $sign;
        $pdf->SetXY(165, $tableY + 10);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell(28, 5, I4ware_PDF_Engine::conv(number_format($gross_amount, 2, ',', ' ') . ' €'), 0, 1, 'R');

        // 6. Tax Breakdown & Totals Box (Right aligned)
        $summaryY = $tableY + 7 + $rowHeight + 6;
        $pdf->SetXY(110, $summaryY);
        $pdf->SetFillColor(248, 250, 252);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->RoundedRect(110, $summaryY, 85, 32, 2, 'FD');

        $pdf->SetXY(114, $summaryY + 2.5);
        $pdf->SetFont('Helvetica', '', 8.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(45, 5, I4ware_PDF_Engine::conv('Veroton summa (Net):'), 0, 0, 'L');
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(32, 5, I4ware_PDF_Engine::conv(number_format($net_amount, 2, ',', ' ') . ' €'), 0, 1, 'R');

        $vat_amount = (float) $data['vat_amount'] * $sign;
        $pdf->SetX(114);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(45, 5, I4ware_PDF_Engine::conv('Arvonlisävero (' . number_format($vat_rate, 1, ',', ' ') . ' %):'), 0, 0, 'L');
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(32, 5, I4ware_PDF_Engine::conv(number_format($vat_amount, 2, ',', ' ') . ' €'), 0, 1, 'R');

        $pdf->SetDrawColor(203, 213, 225);
        $pdf->Line(114, $summaryY + 14, 191, $summaryY + 14);

        $pdf->SetXY(114, $summaryY + 16);
        $pdf->SetFont('Helvetica', 'B', 10);
        $total_label = $is_credit_note ? 'Hyvitetty summa:' : 'Maksettu loppusumma:';
        $pdf->SetTextColor($is_credit_note ? 190 : 15, $is_credit_note ? 18 : 23, $is_credit_note ? 60 : 42);
        $pdf->Cell(45, 6, I4ware_PDF_Engine::conv($total_label), 0, 0, 'L');
        $pdf->Cell(32, 6, I4ware_PDF_Engine::conv(number_format($gross_amount, 2, ',', ' ') . ' ' . (!empty($data['currency']) ? $data['currency'] : 'EUR')), 0, 1, 'R');

        $pdf->SetX(114);
        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(77, 4, I4ware_PDF_Engine::conv('Asiakkaan maksama täysi bruttosumma'), 0, 1, 'R');

        // 7. Statutory Tax Statement (Lakisääteinen veromerkintä)
        $taxNoteY = $summaryY + 36;
        $pdf->SetXY(15, $taxNoteY);
        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->RoundedRect(15, $taxNoteY, 180, 18, 2, 'FD');

        $pdf->SetXY(18, $taxNoteY + 2.5);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(174, 4, I4ware_PDF_Engine::conv('LAKISÄÄTEINEN VEROMERKINTÄ JA MAKSUTIEDOT:'), 0, 1, 'L');

        $pdf->SetX(18);
        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetTextColor(51, 65, 85);
        $tax_note = !empty($data['tax_note']) ? $data['tax_note'] : 'Suomen arvonlisävero 25,5 % (AVL 1 §)';
        $pdf->Cell(174, 4, I4ware_PDF_Engine::conv('• ' . $tax_note), 0, 1, 'L');

        $pdf->SetX(18);
        $pdf->SetFont('Helvetica', 'I', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(174, 4, I4ware_PDF_Engine::conv('• Tämä tosite on toimitettu PayPalin maksuvahvistuksen lisäksi i4ware Softwaren palvelimelta. Maksua ei tarvitse suorittaa uudelleen.'), 0, 1, 'L');

        // 8. Footer (Bottom of A4 page)
        $pdf->SetXY(15, 272);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line(15, 272, 195, 272);

        $pdf->SetXY(15, 274);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(100, 116, 139);
        $footer_text = 'i4ware Software | Y-tunnus: 2739594-6 | ALV-tunniste: FI27395946 | Kasarmikatu 15, 13100 Hämeenlinna, Finland' . "\n" .
                       'Asiakaspalvelu ja mainosaineistot: info@i4ware.fi | Verkkosivusto: https://i4ware.fi | Sivu 1 / 1';
        $pdf->MultiCell(180, 3.5, I4ware_PDF_Engine::conv($footer_text), 0, 'C');

        return $pdf->Output('S');
    }
}
