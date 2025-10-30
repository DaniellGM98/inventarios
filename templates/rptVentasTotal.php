<?php
require_once('../public/core/tcpdf/config/lang/spa.php');
require_once('../public/core/tcpdf/tcpdf.php');
ini_set('error_reporting', 0);

class PDF extends TCPDF {
    private $titulo = "";
    private $inicio = "";
    private $fin = "";

    public function setTitulo($tittle, $inicio, $fin) {
        $this->titulo = $tittle;
        $this->inicio = $inicio;
        $this->fin = $fin;
    }

    function Header() {
        $arrMes = array('', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
        $arrFe = explode('-', $this->inicio);
        $subtitulo = "Del " . $arrFe[2] . " de " . $arrMes[(int)$arrFe[1]];
        if ($this->inicio != $this->fin) {
            $arrFe = explode('-', $this->fin);
            $subtitulo .= " al " . $arrFe[2] . " de " . $arrMes[(int)$arrFe[1]] . " del " . $arrFe[0];
        } else {
            $subtitulo .= " del " . $arrFe[0];
        }

        $this->Image('../public/assets/images/logo.png', 15, 5, 55, 40);
        $this->Ln(25);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
        $this->Cell(65, 10, '', 0, 0, 'C');
        $this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'C');
        $this->Cell(65, 10, '', 0, 0, 'C');
        $this->Cell(0, 10, $subtitulo, 0, 1, 'C');
        $this->Ln(5);
        $this->setX(15);
        $this->Cell(188, 0, '', 'T', 0, 'L');
        $this->Ln(1);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->setX(15);
		$this->Cell(1, 5, 'FECHA', 0, 0, 'L');
		$this->Cell(120, 5, 'CANTIDAD', 0, 0, 'C');
		$this->Cell(65, 5, 'IMPORTE', 0, 1, 'R');
        $this->setX(15);
        $this->Cell(188, 0, '', 'T', 0, 'L');
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

$miReporte = new PDF('P', 'mm', 'letter');
$miReporte->setTitulo($vista, $inicio, $fin);
$miReporte->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$miReporte->SetDisplayMode('real');
$miReporte->SetAuthor('DDS.media');
$miReporte->SetCreator('www.ddsmedia.net');
$miReporte->SetTitle($vista);
$miReporte->SetSubject($vista);
$miReporte->SetFillColor(240, 240, 240);
$miReporte->AddPage();
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->SetXY(15, 60);
$miReporte->SetMargins(15, 60);

$prodsTotales = 0;
$importeTotal = 0;
$currentDate = $inicio;

setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'Spanish_Spain', 'es'); // Localización en español

while ($currentDate <= $fin) {
    $productosDia = 0;
    $importeDia = 0;

    foreach ($registros as $prod) {
        $fechaProd = date('Y-m-d', strtotime($prod->fecha));
        if ($fechaProd === $currentDate) {
            $productosDia += $prod->num_prod;
            $importeDia += $prod->total;
        }
    }

    if ($productosDia > 0) {
        $arrFe = explode('-', $currentDate);
        $arrMes = array('', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');

        $miReporte->SetFont('helvetica', '', 9); // Fuente compatible
        $texto = $arrFe[2] . " de " . $arrMes[(int)$arrFe[1]] . " del " . $arrFe[0];
        $miReporte->Cell(50, 5, $texto, 0, 0, 'L');
        $miReporte->Cell(50, 5, "$productosDia productos", 0, 0, 'L');
        $miReporte->Cell(88, 5, "$ " . number_format($importeDia, 2), 0, 1, 'R');

        $prodsTotales += $productosDia;
        $importeTotal += $importeDia;
    }

    $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
}


// Imprimir el total general
$miReporte->Ln(5);
$miReporte->Cell(188, 0, '', 'T', 1, 'L');
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(50, 5, 'TOTAL', 0, 0, 'L');
$miReporte->Cell(50, 5, "$prodsTotales productos", 0, 0, 'L');
$miReporte->Cell(88, 5, "$ " . number_format($importeTotal, 2), 0, 1, 'R');

$miReporte->Output();
exit();
