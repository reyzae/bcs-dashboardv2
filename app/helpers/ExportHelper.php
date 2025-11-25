<?php
/**
 * Export Helper - Excel & PDF Export Functionality
 * Bytebalok Dashboard
 */

class ExportHelper {
    
    /**
     * Get export directory path
     * 
     * @return string Export directory path
     */
    private static function getExportDir() {
        // Try multiple possible paths
        $paths = [
            __DIR__ . '/../../storage/exports/',  // From app/helpers
            __DIR__ . '/../../../storage/exports/', // Alternative
            dirname(__DIR__, 3) . '/storage/exports/', // From root
            dirname(__DIR__, 2) . '/storage/exports/'  // From app
        ];
        
        foreach ($paths as $path) {
            $realPath = realpath(dirname($path));
            if ($realPath !== false && is_dir($realPath)) {
                return $realPath . '/exports/';
            }
        }
        
        // Default: create in app/helpers relative path
        $defaultPath = __DIR__ . '/../../storage/exports/';
        $dir = dirname($defaultPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $defaultPath;
    }
    
    /**
     * Ensure export directory exists
     * 
     * @return string Export directory path
     */
    private static function ensureExportDir() {
        $dir = self::getExportDir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }
    
    /**
     * Export data to Excel format
     * 
     * @param array $data Data to export (2D array)
     * @param string $filename Output filename
     * @param array $headers Column headers
     * @param string $sheetName Sheet name (default: "Sheet1")
     * @return string File path
     */
    public static function exportToExcel($data, $filename, $headers = [], $sheetName = 'Report') {
        if (PHP_VERSION_ID < 80200) {
            $csvFilename = preg_replace('/\.(xlsx?|xls)$/i', '.csv', $filename);
            if ($csvFilename === $filename) {
                $csvFilename = pathinfo($filename, PATHINFO_FILENAME) . '.csv';
            }
            error_log('PhpSpreadsheet requires PHP >= 8.2; falling back to CSV');
            return self::exportToCSV($data, $csvFilename, $headers);
        }
        // Try to load autoload.php first
        $autoloadPaths = [
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../../../vendor/autoload.php',
            dirname(__DIR__, 3) . '/vendor/autoload.php'
        ];
        
        $autoloadLoaded = false;
        foreach ($autoloadPaths as $autoloadPath) {
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
                $autoloadLoaded = true;
                break;
            }
        }
        
        // Check if PhpSpreadsheet is available
        if (!$autoloadLoaded || !class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            // Fallback to CSV if PhpSpreadsheet not available
            // Change filename extension to .csv
            $csvFilename = preg_replace('/\.(xlsx?|xls)$/i', '.csv', $filename);
            if ($csvFilename === $filename) {
                $csvFilename = pathinfo($filename, PATHINFO_FILENAME) . '.csv';
            }
            error_log("PhpSpreadsheet not available, falling back to CSV: " . $csvFilename);
            return self::exportToCSV($data, $csvFilename, $headers);
        }
        
        try {
            $baseName = pathinfo($filename, PATHINFO_FILENAME);
            $filename = $baseName . '.xlsx';
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($sheetName);
            
            // Set headers with styling
            if (!empty($headers)) {
                $col = 'A';
                foreach ($headers as $header) {
                    $sheet->setCellValue($col . '1', $header);
                    
                    // Header styling
                    $sheet->getStyle($col . '1')->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                            'size' => 12
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '4472C4']
                        ],
                        'alignment' => [
                            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                        ]
                    ]);
                    
                    $col++;
                }
            }
            
            $row = 2;
            $colCount = count($headers);
            $sums = array_fill(0, $colCount, null);

            $lowerHeaders = array_map(function($h){ return strtolower(trim($h)); }, $headers);
            $isTextCol = array_map(function($h){
                return (strpos($h, 'number') !== false) || (strpos($h, 'code') !== false) || (strpos($h, 'sku') !== false) || (strpos($h, 'barcode') !== false) || (strpos($h, 'phone') !== false);
            }, $lowerHeaders);
            $isNumericCol = array_map(function($h){
                return (strpos($h, 'amount') !== false) || (strpos($h, 'subtotal') !== false) || (strpos($h, 'discount') !== false) || (strpos($h, 'tax') !== false) || (strpos($h, 'shipping') !== false) || (strpos($h, 'revenue') !== false) || (strpos($h, 'profit') !== false) || (strpos($h, 'price') !== false) || (strpos($h, 'cost') !== false) || (strpos($h, 'total spent') !== false) || (strpos($h, 'stock quantity') !== false) || (strpos($h, 'items count') !== false);
            }, $lowerHeaders);
            $isCurrencyCol = array_map(function($h){
                return (strpos($h, 'amount') !== false) || (strpos($h, 'subtotal') !== false) || (strpos($h, 'discount') !== false) || (strpos($h, 'tax') !== false) || (strpos($h, 'shipping') !== false) || (strpos($h, 'revenue') !== false) || (strpos($h, 'profit') !== false) || (strpos($h, 'price') !== false) || (strpos($h, 'cost') !== false) || (strpos($h, 'total spent') !== false);
            }, $lowerHeaders);
            $isDateCol = array_map(function($h){
                return (strpos($h, 'date') !== false) || (strpos($h, 'paid at') !== false) || (strpos($h, 'created at') !== false) || (strpos($h, 'order date') !== false) || (strpos($h, 'last purchase') !== false);
            }, $lowerHeaders);

            foreach ($data as $item) {
                $col = 'A';
                for ($i = 0; $i < $colCount; $i++) {
                    $value = $item[$i] ?? '';
                    if (!empty($isTextCol[$i]) && $isTextCol[$i]) {
                        $sheet->setCellValueExplicit($col . $row, (string)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    } elseif (!empty($isDateCol[$i]) && $isDateCol[$i]) {
                        $ts = is_string($value) ? strtotime($value) : null;
                        if ($ts) {
                            $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ts);
                            $sheet->setCellValue($col . $row, $excelDate);
                            $sheet->getStyle($col . $row)->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
                        } else {
                            $sheet->setCellValue($col . $row, $value);
                        }
                    } elseif ((!empty($isCurrencyCol[$i]) && $isCurrencyCol[$i]) || (!empty($isNumericCol[$i]) && $isNumericCol[$i])) {
                        $numVal = self::toNumber($value);
                        if ($numVal !== null) {
                            $sheet->setCellValue($col . $row, $numVal);
                        } else {
                            $sheet->setCellValue($col . $row, $value);
                        }
                    } else {
                        $sheet->setCellValue($col . $row, $value);
                    }
                    $num = self::toNumber($value);
                    if ($num !== null && !empty($isNumericCol[$i]) && $isNumericCol[$i]) {
                        $sums[$i] = ($sums[$i] ?? 0) + $num;
                    }
                    $col++;
                }
                $row++;
            }

            if ($row > 2 && $colCount > 0) {
                $sheet->setCellValue('A' . $row, 'TOTAL');
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $col = 'B';
                for ($i = 1; $i < $colCount; $i++) {
                    $sum = $sums[$i];
                    if ($sum !== null && !empty($isNumericCol[$i]) && $isNumericCol[$i]) {
                        $sheet->setCellValue($col . $row, $sum);
                    } else {
                        $sheet->setCellValue($col . $row, '');
                    }
                    $col++;
                }
                $lastColTotal = chr(65 + $colCount - 1) . $row;
                $sheet->getStyle('A' . $row . ':' . $lastColTotal)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EDEDED']
                    ]
                ]);
            }
            
            // Auto-size columns & apply number formats
            $lastCol = chr(65 + count($headers) - 1);
            foreach (range('A', $lastCol) as $columnID) {
                $sheet->getColumnDimension($columnID)->setAutoSize(true);
            }

            $colIdx = 0;
            foreach (range('A', $lastCol) as $columnID) {
                if (!empty($isTextCol[$colIdx]) && $isTextCol[$colIdx]) {
                    $sheet->getStyle($columnID . '2:' . $columnID . ($row))->getNumberFormat()->setFormatCode('@');
                } elseif (!empty($isCurrencyCol[$colIdx]) && $isCurrencyCol[$colIdx]) {
                    $sheet->getStyle($columnID . '2:' . $columnID . ($row))->getNumberFormat()->setFormatCode('"Rp" #,##0');
                } elseif (!empty($isNumericCol[$colIdx]) && $isNumericCol[$colIdx]) {
                    $sheet->getStyle($columnID . '2:' . $columnID . ($row))->getNumberFormat()->setFormatCode('#,##0');
                }
                $colIdx++;
            }
            
            // Add borders to data
            if ($row > 2) {
                $sheet->getStyle('A1:' . $lastCol . ($row - 1))->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC']
                        ]
                    ]
                ]);
            }

            // Freeze header row and apply autofilter
            $sheet->freezePane('A2');
            $sheet->setAutoFilter('A1:' . $lastCol . ($row - 1));
            
            // Generate file
            $exportDir = self::ensureExportDir();
            $filepath = $exportDir . $filename;
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($filepath);
            
            return $filepath;
            
        } catch (Exception $e) {
            error_log("Excel Export Error: " . $e->getMessage());
            // Fallback to CSV - change filename extension
            $csvFilename = preg_replace('/\.(xlsx?|xls)$/i', '.csv', $filename);
            if ($csvFilename === $filename) {
                $csvFilename = pathinfo($filename, PATHINFO_FILENAME) . '.csv';
            }
            return self::exportToCSV($data, $csvFilename, $headers);
        }
    }

    private static function toNumber($value) {
        if (is_int($value) || is_float($value)) { return (float)$value; }
        if (!is_string($value)) { return null; }
        $t = trim($value);
        if ($t === '') { return null; }
        if (preg_match('/^Rp\s*/i', $t)) {
            $t = preg_replace('/[^0-9,\.]/', '', $t);
            $t = str_replace('.', '', $t);
            $t = str_replace(',', '.', $t);
            return is_numeric($t) ? (float)$t : null;
        }
        $n = preg_replace('/[^0-9,\.\-]/', '', $t);
        $n = str_replace(',', '.', $n);
        return is_numeric($n) ? (float)$n : null;
    }
    
    /**
     * Export data to PDF format
     * 
     * @param string $html HTML content to convert
     * @param string $filename Output filename
     * @param string $orientation Page orientation (P=Portrait, L=Landscape)
     * @param array $options Additional options
     * @return string File path
     */
    public static function exportToPDF($html, $filename, $orientation = 'P', $options = []) {
        // Try to load autoload.php first
        $autoloadPaths = [
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../../../vendor/autoload.php',
            dirname(__DIR__, 3) . '/vendor/autoload.php'
        ];
        
        $autoloadLoaded = false;
        foreach ($autoloadPaths as $autoloadPath) {
            if (file_exists($autoloadPath)) {
                require_once $autoloadPath;
                $autoloadLoaded = true;
                break;
            }
        }
        
        // Check if TCPDF is available
        if (!$autoloadLoaded || !class_exists('TCPDF')) {
            // Fallback to HTML file
            // Change filename extension to .html
            $htmlFilename = preg_replace('/\.pdf$/i', '.html', $filename);
            if ($htmlFilename === $filename) {
                $htmlFilename = pathinfo($filename, PATHINFO_FILENAME) . '.html';
            }
            error_log("TCPDF not available, falling back to HTML: " . $htmlFilename);
            return self::exportToHTML($html, $htmlFilename);
        }
        
        try {
            $pdf = new TCPDF($orientation, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8');
            
            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor($options['author'] ?? 'Bytebalok Dashboard');
            $pdf->SetTitle($options['title'] ?? 'Report');
            $pdf->SetSubject($options['subject'] ?? 'Report Export');
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            // Set font
            $pdf->SetFont('helvetica', '', 10);
            
            // Add page
            $pdf->AddPage();
            
            // Write HTML content
            $pdf->writeHTML($html, true, false, true, false, '');
            
            // Generate file
            $exportDir = self::ensureExportDir();
            $filepath = $exportDir . $filename;
            $pdf->Output($filepath, 'F');
            
            return $filepath;
            
        } catch (Exception $e) {
            error_log("PDF Export Error: " . $e->getMessage());
            // Fallback to HTML - change filename extension
            $htmlFilename = preg_replace('/\.pdf$/i', '.html', $filename);
            if ($htmlFilename === $filename) {
                $htmlFilename = pathinfo($filename, PATHINFO_FILENAME) . '.html';
            }
            return self::exportToHTML($html, $htmlFilename);
        }
    }
    
    /**
     * Export to CSV format (Excel-compatible)
     * 
     * @param array $data Data to export
     * @param string $filename Output filename
     * @param array $headers Column headers
     * @return string File path
     */
    public static function exportToCSV($data, $filename, $headers = []) {
        $exportDir = self::ensureExportDir();
        $filepath = $exportDir . $filename;
        
        // Open file with UTF-8 encoding
        $fp = fopen($filepath, 'w');
        
        // Add UTF-8 BOM for Excel compatibility (Windows Excel requires this)
        fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write headers
        if (!empty($headers)) {
            fputcsv($fp, $headers, ',', '"');
        }
        
        // Write data
        foreach ($data as $row) {
            // Ensure all values are properly formatted
            $formattedRow = array_map(function($cell) {
                // Convert to string and handle special characters
                return $cell !== null && $cell !== '' ? (string)$cell : '';
            }, $row);
            fputcsv($fp, $formattedRow, ',', '"');
        }
        
        fclose($fp);
        return $filepath;
    }
    
    /**
     * Fallback: Export to HTML format
     * 
     * @param string $html HTML content
     * @param string $filename Output filename
     * @return string File path
     */
    public static function exportToHTML($html, $filename) {
        $exportDir = self::ensureExportDir();
        $filepath = $exportDir . $filename;
        
        $fullHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Report Export</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #4472C4; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        h1 { color: #333; }
    </style>
</head>
<body>
' . $html . '
</body>
</html>';
        
        file_put_contents($filepath, $fullHtml);
        return $filepath;
    }
    
    /**
     * Generate HTML table from data array
     * 
     * @param array $data Data array
     * @param array $headers Table headers
     * @param string $title Table title
     * @return string HTML content
     */
    public static function generateHTMLTable($data, $headers = [], $title = 'Report') {
        $html = '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<p>Generated: ' . date('d F Y H:i:s') . '</p>';
        $html .= '<table>';
        
        // Headers
        if (!empty($headers)) {
            $html .= '<tr>';
            foreach ($headers as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr>';
        }
        
        // Data
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
    }
    
    /**
     * Clean old export files (older than specified hours)
     * 
     * @param int $hours Age in hours (default: 24)
     * @return int Number of files deleted
     */
    public static function cleanOldExports($hours = 24) {
        $exportDir = self::getExportDir();
        $deleted = 0;
        
        if (!is_dir($exportDir)) {
            return 0;
        }
        
        $files = glob($exportDir . '*');
        $now = time();
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $fileAge = $now - filemtime($file);
                if ($fileAge > ($hours * 3600)) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
    
    /**
     * Format currency for export
     * 
     * @param float $amount Amount
     * @param string $currency Currency code
     * @return string Formatted currency
     */
    public static function formatCurrency($amount, $currency = 'IDR') {
        if ($currency === 'IDR') {
            return 'Rp ' . number_format($amount, 0, ',', '.');
        }
        return $currency . ' ' . number_format($amount, 2, '.', ',');
    }
    
    /**
     * Format date for export
     * 
     * @param string $date Date string
     * @param string $format Date format
     * @return string Formatted date
     */
    public static function formatDate($date, $format = 'd/m/Y H:i') {
        if (empty($date)) return '-';
        return date($format, strtotime($date));
    }
}

