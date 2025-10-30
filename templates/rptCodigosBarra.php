<?php
require_once('../public/core/tcpdf/config/lang/spa.php');
require_once('../public/core/tcpdf/tcpdf.php');
ini_set('error_reporting', 0);

// Función para limpiar y convertir a codificación compatible
function limpiar_utf8($texto) {
    if (!mb_check_encoding($texto, 'UTF-8')) {
        $texto = mb_convert_encoding($texto, 'UTF-8', 'auto');
    }
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT//IGNORE', $texto);
}

class PDF extends TCPDF {

    private $titulo = "";

    public function setTitulo($titulo) {
        $this->titulo = $titulo;
    }

    function Header() {
        $this->Image('../public/assets/images/logo.png', 15, 5, 55, 35);
        $this->Ln(20);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
        $this->Cell(0, 10, $this->titulo, 0, 1, 'C');
        $this->Ln(10);
    }

    function Footer() {
        $paginas = "Página " . $this->PageNo() . ' de ' . $this->getAliasNbPages();
        $this->SetY(-15);
        $this->SetTextColor(77, 73, 72);
        $this->Ln(4);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 7);
        $this->Cell(0, 3, $paginas, 0, 0, 'C');
    }
}

// Variables entrantes
$registros = $registros ?? [];
$numero = isset($numero) ? intval($numero) : 1;
$vista = $vista ?? "Código de Barras";
$vista = limpiar_utf8($vista); // Sanear título

// Parámetros para layout
$marginLeft = 15;
$marginTop = 50;
$marginBottom = 260;

$columnWidth = 55;
$barcodeHeight = 18;
$spaceX = 10;
$spaceY = 35;
$maxColumns = 3;

// Ancho del código de barras para centrar dentro de la columna
$barcodeWidth = 40;
// Alto para la celda de la descripción
$descripcionHeight = 6;

// Altura total del bloque (cuadro + contenido)
$blockHeight = $descripcionHeight + $barcodeHeight + 4;

$miReporte = new PDF('P', 'mm', 'letter');
$miReporte->setTitulo($vista);
$miReporte->SetAutoPageBreak(FALSE);
$miReporte->SetDisplayMode('real');
$miReporte->SetAuthor('DDS.media');
$miReporte->SetCreator('www.ddsmedia.net');
$miReporte->SetTitle($vista);
$miReporte->SetSubject($vista);
$miReporte->SetFillColor(240, 240, 240);
$miReporte->AddPage();
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->SetMargins($marginLeft, $marginTop);

$currentX = $marginLeft;
$currentY = $marginTop;
$columnCount = 0;

foreach ($registros as $registro) {
    $codigo = $registro['codigo_barras'];
    $descripcion = limpiar_utf8($registro['descripcion']); // Sanear descripción

    for ($i = 0; $i < $numero; $i++) {
        if ($currentY + $blockHeight > $marginBottom) {
            $miReporte->AddPage();
            $currentX = $marginLeft;
            $currentY = $marginTop;
            $columnCount = 0;
        }

        // Dibuja el rectángulo (cuadro) que encierra la descripción y el código
        $miReporte->Rect($currentX, $currentY, $columnWidth, $blockHeight);

        // Imprimir descripción arriba, centrada en la columna
        $miReporte->SetXY($currentX, $currentY);
        $miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 9);
        $miReporte->Cell($columnWidth, $descripcionHeight, $descripcion, 0, 0, 'C');

        // Calcular posición X para centrar código de barras en la columna
        $barcodeX = $currentX + ($columnWidth - $barcodeWidth) / 2;
        // Imprimir código de barras debajo de la descripción
        $miReporte->SetXY($barcodeX, $currentY + $descripcionHeight + 2); // +2 para un pequeño espacio

        $miReporte->write1DBarcode($codigo, 'C128', '', '', $barcodeWidth, $barcodeHeight, 0.3, [
            'position' => 'S',
            'align' => 'C',
            'stretch' => false,
            'fitwidth' => true,
            'border' => false,
            'hpadding' => 'auto',
            'vpadding' => 'auto',
            'fgcolor' => [0, 0, 0],
            'bgcolor' => false,
            'text' => true,
            'font' => 'helvetica',
            'fontsize' => 6,
            'stretchtext' => 4
        ], 'N');

        $columnCount++;

        if ($columnCount >= $maxColumns) {
            $columnCount = 0;
            $currentX = $marginLeft;
            $currentY += $spaceY;
        } else {
            $currentX += $columnWidth + $spaceX;
        }
    }
}

$miReporte->Output();
exit();
?>
