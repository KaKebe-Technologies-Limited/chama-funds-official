<?php
// ============================================================
// ChamaFunds – includes/simple_pdf.php
// A minimal, dependency-free PDF writer. No composer package for
// PDF generation is available in this environment (no internet
// access to fetch one), so this hand-writes a valid PDF byte
// stream directly — text, filled rectangles, lines/dashes and
// triangles, using the built-in Helvetica/Helvetica-Bold fonts
// (no font embedding needed, so no external font files either).
// Coordinates are given from the TOP-LEFT of the page (y grows
// downward), which is converted internally to PDF's bottom-left
// origin convention.
// ============================================================

class SimplePdf {
    private float $width;
    private float $height;
    private string $content = '';

    // Rough average glyph-width factors (fraction of font size) for
    // Helvetica regular/bold — not real AFM metrics, just enough to
    // approximate text width for centering/right-aligning.
    private const AVG_WIDTH_REGULAR = 0.52;
    private const AVG_WIDTH_BOLD    = 0.56;

    public function __construct(float $width = 595, float $height = 340) {
        $this->width  = $width;
        $this->height = $height;
    }

    public function width(): float { return $this->width; }
    public function height(): float { return $this->height; }

    // The base-14 fonts (Helvetica) render using WinAnsiEncoding, which
    // is ~identical to Windows-1252 — not UTF-8. Writing raw UTF-8 bytes
    // into a PDF text string makes multi-byte characters (em dashes,
    // ellipses, curly quotes, accented letters) show up as mojibake
    // (e.g. "â€"" instead of "—"), so transcode first.
    private function toPdfEncoding(string $text): string {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) return $converted;
        }
        if (function_exists('mb_convert_encoding')) {
            $converted = @mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
            if ($converted !== false) return $converted;
        }
        return $text;
    }

    private function esc(string $text): string {
        $text = $this->toPdfEncoding($text);
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    public function estimateTextWidth(string $text, float $size, bool $bold = false): float {
        $factor = $bold ? self::AVG_WIDTH_BOLD : self::AVG_WIDTH_REGULAR;
        // Count post-transcode bytes (1 per rendered glyph), not raw UTF-8
        // bytes, or multi-byte characters (em dashes, etc.) would inflate
        // the estimate and throw off centering/wrapping.
        return strlen($this->toPdfEncoding($text)) * $size * $factor;
    }

    public function setFillColor(int $r, int $g, int $b): void {
        $this->content .= sprintf("%.3F %.3F %.3F rg\n", $r / 255, $g / 255, $b / 255);
    }

    public function setStrokeColor(int $r, int $g, int $b): void {
        $this->content .= sprintf("%.3F %.3F %.3F RG\n", $r / 255, $g / 255, $b / 255);
    }

    public function rect(float $x, float $y, float $w, float $h, string $mode = 'f'): void {
        $pdfY = $this->height - $y - $h;
        $op = $mode === 's' ? 'S' : ($mode === 'sf' ? 'B' : 'f');
        $this->content .= sprintf("%.2F %.2F %.2F %.2F re %s\n", $x, $pdfY, $w, $h, $op);
    }

    public function triangle(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void {
        $py1 = $this->height - $y1;
        $py2 = $this->height - $y2;
        $py3 = $this->height - $y3;
        $this->content .= sprintf("%.2F %.2F m %.2F %.2F l %.2F %.2F l h f\n", $x1, $py1, $x2, $py2, $x3, $py3);
    }

    public function line(float $x1, float $y1, float $x2, float $y2, float $lineWidth = 1): void {
        $py1 = $this->height - $y1;
        $py2 = $this->height - $y2;
        $this->content .= sprintf("%.2F w\n%.2F %.2F m %.2F %.2F l S\n", $lineWidth, $x1, $py1, $x2, $py2);
    }

    public function dottedLine(float $x1, float $y, float $x2, float $lineWidth = 0.7): void {
        $this->content .= "[1 2] 0 d\n";
        $this->line($x1, $y, $x2, $y, $lineWidth);
        $this->content .= "[] 0 d\n";
    }

    public function text(float $x, float $y, string $text, float $size = 10, bool $bold = false): void {
        $font = $bold ? 'F2' : 'F1';
        $pdfY = $this->height - $y;
        $this->content .= sprintf("BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n", $font, $size, $x, $pdfY, $this->esc($text));
    }

    public function textCentered(float $centerX, float $y, string $text, float $size = 10, bool $bold = false): void {
        $w = $this->estimateTextWidth($text, $size, $bold);
        $this->text($centerX - $w / 2, $y, $text, $size, $bold);
    }

    public function textRight(float $rightX, float $y, string $text, float $size = 10, bool $bold = false): void {
        $w = $this->estimateTextWidth($text, $size, $bold);
        $this->text($rightX - $w, $y, $text, $size, $bold);
    }

    // Renders and returns the raw PDF bytes. Pass $path to also save to disk.
    public function output(?string $path = null): string {
        $objects = [];
        $objects[1] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[2] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->width} {$this->height}] "
                    . "/Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> /Contents 4 0 R >>";
        $streamLen  = strlen($this->content);
        $objects[4] = "<< /Length {$streamLen} >>\nstream\n{$this->content}endstream";
        $objects[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[6] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

        $pdf     = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $num => $body) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$body\nendobj\n";
        }
        $xrefOffset = strlen($pdf);
        $count      = count($objects) + 1;
        $pdf .= "xref\n0 $count\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size $count /Root 1 0 R >>\nstartxref\n$xrefOffset\n%%EOF";

        if ($path !== null) {
            file_put_contents($path, $pdf);
        }
        return $pdf;
    }
}
