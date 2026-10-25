<?php
/**
 * i4ware PDF Engine
 *
 * Self-contained, lightweight vector PDF 1.4 generator in pure PHP.
 * Supports:
 * - A4 Page geometry (210 x 297 mm)
 * - Standard core Type 1 fonts (Helvetica, Helvetica-Bold, Helvetica-Oblique)
 * - Full WinAnsiEncoding mapping for Finnish characters (ä, ö, å, Ä, Ö, Å, €, –, etc.)
 * - Colors (fill, stroke, text), borders, line widths, rectangles, lines
 * - Exact text alignment (Left, Center, Right) and multi-line wrapping (MultiCell)
 * - Direct binary string output or secure file write
 */

if (!defined('ABSPATH')) {
    exit;
}

class I4ware_PDF_Engine
{
    protected $page = 0;
    protected $n = 2; // object counter
    protected $offsets = [];
    protected $buffer = '';
    protected $pages = [];
    protected $w = 210.0; // A4 mm
    protected $h = 297.0;
    protected $k = 72.0 / 25.4; // scale factor mm to pt (2.83464567)
    protected $wPt;
    protected $hPt;
    protected $lMargin = 15.0;
    protected $rMargin = 15.0;
    protected $tMargin = 15.0;
    protected $bMargin = 15.0;
    protected $x;
    protected $y;
    protected $lasth = 0;
    protected $fontSizePt = 10.0;
    protected $fontSize = 3.527; // in mm
    protected $fontFamily = 'Helvetica';
    protected $fontStyle = '';
    protected $drawColor = '0 G';
    protected $fillColor = '0 g';
    protected $textColor = '0 g';
    protected $lineWidth = 0.567; // 0.2 mm
    protected $autoPageBreak = true;
    protected $bMarginLimit = 18.0;

    public function __construct()
    {
        $this->wPt = $this->w * $this->k;
        $this->hPt = $this->h * $this->k;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
    }

    public function AddPage()
    {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
    }

    public function SetMargins($left, $top, $right = null)
    {
        $this->lMargin = (float) $left;
        $this->tMargin = (float) $top;
        $this->rMargin = ($right === null) ? (float) $left : (float) $right;
    }

    public function SetFont($family, $style = '', $size = 0)
    {
        $family = strtolower($family);
        if ($family === 'arial' || $family === 'helvetica' || $family === 'sans-serif') {
            $this->fontFamily = 'Helvetica';
        } elseif ($family === 'times' || $family === 'serif') {
            $this->fontFamily = 'Times';
        } else {
            $this->fontFamily = 'Helvetica';
        }

        $style = strtoupper($style);
        $this->fontStyle = '';
        if (strpos($style, 'B') !== false) $this->fontStyle .= 'B';
        if (strpos($style, 'I') !== false) $this->fontStyle .= 'I';

        if ($size > 0) {
            $this->fontSizePt = (float) $size;
            $this->fontSize = (float) $size / $this->k;
        }
    }

    public function SetFontSize($size)
    {
        $this->fontSizePt = (float) $size;
        $this->fontSize = (float) $size / $this->k;
    }

    public function SetDrawColor($r, $g = null, $b = null)
    {
        if ($g === null) {
            $this->drawColor = sprintf('%.3F G', $r / 255.0);
        } else {
            $this->drawColor = sprintf('%.3F %.3F %.3F RG', $r / 255.0, $g / 255.0, $b / 255.0);
        }
        $this->_out($this->drawColor);
    }

    public function SetFillColor($r, $g = null, $b = null)
    {
        if ($g === null) {
            $this->fillColor = sprintf('%.3F g', $r / 255.0);
        } else {
            $this->fillColor = sprintf('%.3F %.3F %.3F rg', $r / 255.0, $g / 255.0, $b / 255.0);
        }
        $this->_out($this->fillColor);
    }

    public function SetTextColor($r, $g = null, $b = null)
    {
        if ($g === null) {
            $this->textColor = sprintf('%.3F g', $r / 255.0);
        } else {
            $this->textColor = sprintf('%.3F %.3F %.3F rg', $r / 255.0, $g / 255.0, $b / 255.0);
        }
    }

    public function SetLineWidth($width)
    {
        $this->lineWidth = $width * $this->k;
        $this->_out(sprintf('%.3F w', $this->lineWidth));
    }

    public function GetX() { return $this->x; }
    public function GetY() { return $this->y; }
    public function SetX($x) { $this->x = ($x >= 0) ? $x : $this->w + $x; }
    public function SetY($y) { $this->y = ($y >= 0) ? $y : $this->h + $y; }
    public function SetXY($x, $y) { $this->SetX($x); $this->SetY($y); }

    public function Line($x1, $y1, $x2, $y2)
    {
        $this->_out(sprintf('%.2F %.2F m %.2F %.2F l S',
            $x1 * $this->k, ($this->h - $y1) * $this->k,
            $x2 * $this->k, ($this->h - $y2) * $this->k
        ));
    }

    public function Rect($x, $y, $w, $h, $style = '')
    {
        $op = 'S';
        if ($style === 'F') $op = 'f';
        elseif ($style === 'FD' || $style === 'DF' || $style === 'B') $op = 'B';
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s',
            $x * $this->k, ($this->h - $y - $h) * $this->k,
            $w * $this->k, $h * $this->k, $op
        ));
    }

    public function RoundedRect($x, $y, $w, $h, $r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        $op = 'S';
        if ($style === 'F') $op = 'f';
        elseif ($style === 'FD' || $style === 'DF' || $style === 'B') $op = 'B';

        $myArc = 4.0 / 3.0 * (sqrt(2) - 1.0);
        $this->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
        $xc = $x + $w - $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $xc * $k, ($hp - $y) * $k));
        $this->_arc($xc + $r * $myArc, $yc - $r, $xc + $r, $yc - $r * $myArc, $xc + $r, $yc);
        $xc = $x + $w - $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - $yc) * $k));
        $this->_arc($xc + $r, $yc + $r * $myArc, $xc + $r * $myArc, $yc + $r, $xc, $yc + $r);
        $xc = $x + $r;
        $yc = $y + $h - $r;
        $this->_out(sprintf('%.2F %.2F l', ($x + $r) * $k, ($hp - ($y + $h)) * $k));
        $this->_arc($xc - $r * $myArc, $yc + $r, $xc - $r, $yc + $r * $myArc, $xc - $r, $yc);
        $xc = $x + $r;
        $yc = $y + $r;
        $this->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $yc) * $k));
        $this->_arc($xc - $r, $yc - $r * $myArc, $xc - $r * $myArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    protected function _arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $k = $this->k;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1 * $k, ($h - $y1) * $k, $x2 * $k, ($h - $y2) * $k, $x3 * $k, ($h - $y3) * $k));
    }

    public function GetStringWidth($s)
    {
        $len = strlen($s);
        $w = 0;
        $isBold = (strpos($this->fontStyle, 'B') !== false);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($s[$i]);
            if ($c === 32) {
                $w += 278;
            } elseif ($c >= 48 && $c <= 57) {
                $w += 556;
            } elseif ($c >= 65 && $c <= 90) {
                $w += $isBold ? 722 : 667;
            } elseif ($c >= 97 && $c <= 122) {
                $w += $isBold ? 556 : 500;
            } elseif ($c === 128) { // €
                $w += 556;
            } elseif ($c === 228 || $c === 246 || $c === 229) { // ä, ö, å
                $w += $isBold ? 580 : 530;
            } elseif ($c === 196 || $c === 214 || $c === 197) { // Ä, Ö, Å
                $w += $isBold ? 740 : 690;
            } elseif ($c === 150) { // – (en-dash)
                $w += 556;
            } else {
                $w += 500;
            }
        }
        return ($w * $this->fontSizePt / 1000.0) / $this->k;
    }

    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false)
    {
        $k = $this->k;
        if ($this->y + $h > $this->h - $this->bMarginLimit && $this->autoPageBreak) {
            $x = $this->x;
            $this->AddPage();
            $this->x = $x;
        }
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }

        $s = '';
        if ($fill || $border == 1) {
            $op = '';
            if ($fill) $op = ($border == 1) ? 'B' : 'f';
            else $op = 'S';
            $s .= sprintf('%.2F %.2F %.2F %.2F re %s ',
                $this->x * $k, ($this->h - $this->y - $h) * $k,
                $w * $k, $h * $k, $op);
        }

        if (is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if (strpos($border, 'L') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, $x * $k, ($this->h - ($y + $h)) * $k);
            }
            if (strpos($border, 'T') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - $y) * $k);
            }
            if (strpos($border, 'R') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x + $w) * $k, ($this->h - $y) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
            }
            if (strpos($border, 'B') !== false) {
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x * $k, ($this->h - ($y + $h)) * $k, ($x + $w) * $k, ($this->h - ($y + $h)) * $k);
            }
        }

        if ($txt !== '') {
            $width = $this->GetStringWidth($txt);
            if ($align === 'R') $dx = $w - $width - 1.5;
            elseif ($align === 'C') $dx = ($w - $width) / 2.0;
            else $dx = 1.5;

            $fontName = $this->fontFamily;
            if ($this->fontStyle === 'B') $fontName .= '-Bold';
            elseif ($this->fontStyle === 'I') $fontName .= '-Oblique';
            elseif ($this->fontStyle === 'BI') $fontName .= '-BoldOblique';

            $txtEsc = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $txt);
            $s .= sprintf('q %s BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET Q ',
                $this->textColor,
                $fontName,
                $this->fontSizePt,
                ($this->x + $dx) * $k,
                ($this->h - ($this->y + 0.5 * $h + 0.3 * $this->fontSize)) * $k,
                $txtEsc
            );
        }

        if ($s) {
            $this->_out($s);
        }

        $this->lasth = $h;
        if ($ln > 0) {
            $this->y += $h;
            if ($ln == 1) $this->x = $this->lMargin;
        } else {
            $this->x += $w;
        }
    }

    public function MultiCell($w, $h, $txt, $border = 0, $align = 'L', $fill = false)
    {
        if ($w == 0) $w = $this->w - $this->rMargin - $this->x;
        $wmax = $w - 3.0; // padding
        $lines = explode("\n", str_replace("\r", "", $txt));
        foreach ($lines as $line) {
            $words = explode(" ", $line);
            $currentLine = '';
            foreach ($words as $word) {
                $test = ($currentLine === '') ? $word : $currentLine . ' ' . $word;
                if ($this->GetStringWidth($test) > $wmax && $currentLine !== '') {
                    $this->Cell($w, $h, $currentLine, $border, 1, $align, $fill);
                    $currentLine = $word;
                } else {
                    $currentLine = $test;
                }
            }
            if ($currentLine !== '') {
                $this->Cell($w, $h, $currentLine, $border, 1, $align, $fill);
            }
        }
    }

    public function Ln($h = null)
    {
        $this->x = $this->lMargin;
        $this->y += ($h === null) ? $this->lasth : $h;
    }

    protected function _out($s)
    {
        if ($this->page > 0) {
            $this->pages[$this->page] .= $s . "\n";
        } else {
            $this->buffer .= $s . "\n";
        }
    }

    public function Output($dest = 'S', $name = '')
    {
        $this->_enddoc();
        if ($dest === 'S') {
            return $this->buffer;
        } elseif ($dest === 'F') {
            $dir = dirname($name);
            if (!is_dir($dir)) {
                wp_mkdir_p($dir);
            }
            $f = fopen($name, 'wb');
            if (!$f) return false;
            fwrite($f, $this->buffer);
            fclose($f);
            return true;
        } elseif ($dest === 'D') {
            // Stream download
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($name) . '"');
            header('Content-Length: ' . strlen($this->buffer));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $this->buffer;
            exit;
        }
        return $this->buffer;
    }

    protected function _newobj()
    {
        $this->n++;
        $this->offsets[$this->n] = strlen($this->buffer);
        $this->buffer .= $this->n . " 0 obj\n";
    }

    protected function _enddoc()
    {
        $this->buffer = "%PDF-1.4\n";

        // Pages objects
        $pageObjIds = [];
        for ($n = 1; $n <= $this->page; $n++) {
            // Content stream
            $this->_newobj();
            $stream = $this->pages[$n];
            $contentId = $this->n;
            $this->buffer .= "<< /Length " . strlen($stream) . " >>\n";
            $this->buffer .= "stream\n" . $stream . "endstream\nendobj\n";

            // Page object
            $this->_newobj();
            $pageObjIds[$n] = $this->n;
            $this->buffer .= "<< /Type /Page\n";
            $this->buffer .= "/Parent 1 0 R\n";
            $this->buffer .= "/Resources << /Font <<\n";
            $this->buffer .= "  /Helvetica 4 0 R\n";
            $this->buffer .= "  /Helvetica-Bold 5 0 R\n";
            $this->buffer .= "  /Helvetica-Oblique 6 0 R\n";
            $this->buffer .= "  /Helvetica-BoldOblique 7 0 R\n";
            $this->buffer .= ">> >>\n";
            $this->buffer .= "/MediaBox [0 0 " . sprintf('%.2F %.2F', $this->wPt, $this->hPt) . "]\n";
            $this->buffer .= "/Contents " . $contentId . " 0 R\n";
            $this->buffer .= ">>\nendobj\n";
        }

        // Root Pages (obj 1)
        $this->offsets[1] = strlen($this->buffer);
        $kids = '';
        foreach ($pageObjIds as $id) $kids .= $id . ' 0 R ';
        $this->buffer .= "1 0 obj\n<< /Type /Pages /Kids [" . trim($kids) . "] /Count " . $this->page . " >>\nendobj\n";

        // Catalog (obj 2)
        $this->offsets[2] = strlen($this->buffer);
        $this->buffer .= "2 0 obj\n<< /Type /Catalog /Pages 1 0 R >>\nendobj\n";

        // Fonts (obj 4, 5, 6, 7)
        $fonts = [
            4 => 'Helvetica',
            5 => 'Helvetica-Bold',
            6 => 'Helvetica-Oblique',
            7 => 'Helvetica-BoldOblique'
        ];
        foreach ($fonts as $fId => $fName) {
            $this->offsets[$fId] = strlen($this->buffer);
            $this->buffer .= $fId . " 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /" . $fName . " /Encoding /WinAnsiEncoding >>\nendobj\n";
        }

        // XRef
        $startxref = strlen($this->buffer);
        $totalObjs = $this->n + 1;
        $this->buffer .= "xref\n0 " . $totalObjs . "\n";
        $this->buffer .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $this->n; $i++) {
            $offset = isset($this->offsets[$i]) ? $this->offsets[$i] : 0;
            $this->buffer .= sprintf("%010d 00000 n \n", $offset);
        }
        $this->buffer .= "trailer\n<< /Size " . $totalObjs . " /Root 2 0 R >>\n";
        $this->buffer .= "startxref\n" . $startxref . "\n%%EOF\n";
    }

    /**
     * Convert UTF-8 string to WinAnsiEncoding (Windows-1252) for standard PDF Type 1 fonts.
     * Safely handles Finnish umlauts (ä, ö, å, Ä, Ö, Å) and special characters (€, –, etc.).
     */
    public static function conv($str)
    {
        if ($str === null || $str === '') {
            return '';
        }

        $str = (string) $str;

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'windows-1252//TRANSLIT', $str);
            if ($converted !== false) {
                return $converted;
            }
        }
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($str, 'Windows-1252', 'UTF-8');
            if ($converted !== false && $converted !== '') {
                return $converted;
            }
        }

        // Custom pre-replacements for common typographical glyphs before utf8_decode fallback
        $replacements = [
            '€' => chr(128),
            '–' => chr(150), // en-dash
            '—' => chr(151), // em-dash
            '‘' => chr(145),
            '’' => chr(146),
            '“' => chr(147),
            '”' => chr(148),
            '„' => chr(132),
            '•' => chr(149),
            '…' => chr(133),
            '™' => chr(153)
        ];
        $str = str_replace(array_keys($replacements), array_values($replacements), $str);

        return utf8_decode($str);
    }
}
