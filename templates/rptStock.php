<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";
    public function setTitulo($tittle){
        $this->titulo=$tittle;
    }

	function Header(){
		$this->Image('../public/assets/images/logo.png', 15, 5, 55, 40);

        $mesStr = array('',' de Enero de ',' de Febrero de ',' de Marzo de ',' de Abril de ', 
				' de Mayo de ',' de Junio de ',' de Julio de ',' de Agosto de ', 
				' de Septiembre de ',' de Octubre de ',' de Noviembre de ',' de Diciembre de ');

        $subtitulo = "Al ".date('d').$mesStr[date('n')].date('Y H:i:s');

		$this->Ln(10);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
		$this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'C');
		$this->Cell(0, 10, $subtitulo, 0, 1, 'C');
		$this->Ln(20);
        $this->SetX(20);
        $this->Cell(250, 0, '', 'T', 0, 'L');
        $this->Ln(1);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
		$this->SetX(20);
		$this->Cell(85, 5, 'PRODUCTO', 0, 0, 'L');
		$this->Cell(75, 5, 'DONADOR', 0, 0, 'L');
		$this->Cell(18, 5, 'PRESENT', 0, 0, 'C');
		$this->Cell(20, 5, 'TIPO', 0, 0, 'C');
		$this->Cell(15, 5, 'PESO', 0, 0, 'R');
		$this->Cell(15, 5, 'STOCK', 0, 0, 'C');
		$this->Cell(22, 5, 'EXIST (Kg)', 0, 1, 'R');
        $this->SetX(20);
        $this->Cell(250, 0, '', 'T', 0, 'L');
	}

	function Footer(){
		$paginas = "Página " . $this->PageNo() . ' de ' . $this->getAliasNbPages();
		$this->SetY(-15);
		$this->SetTextColor(77, 73, 72);
	    $this->Ln(4);
	    $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 7);
	    $this->Cell(0, 3, $paginas, 0, 0, 'C');
	}
}

$miReporte = new PDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false); 
$miReporte->setTitulo($vista);
$miReporte->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$miReporte->SetAuthor('DDS.media'); 
$miReporte->SetCreator('www.fpdf.org'); 
$miReporte->SetTitle($vista); 

$miReporte->SetFillColor(240, 240, 240); 

$miReporte->AddPage();
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
//$miReporte->SetY(70);
$miReporte->SetXY(20,60);
$miReporte->SetMargins(20, 60); 
$contador = 2;

foreach($registros as $prod){
    $relleno = $contador % 2;
		$exist = $prod->stock * $prod->peso;
		$miReporte->Cell(85, 5, $prod->descripcion, 0, 0, 'L', $relleno);
		$miReporte->Cell(75, 5, $prod->proveedor, 0, 0, 'L', $relleno);
		$miReporte->Cell(18, 5, $prod->presenta, 0, 0, 'C', $relleno);
		$miReporte->Cell(20, 5, $prod->tipos, 0, 0, 'C', $relleno);
		$miReporte->Cell(15, 5, number_format($prod->peso,3), 0, 0, 'R', $relleno);
		$miReporte->Cell(15, 5, $prod->stock, 0, 0, 'C', $relleno);
		$miReporte->Cell(22, 5, number_format($exist,3), 0, 1, 'R', $relleno);

		$contador = $contador + 1;
}

$miReporte->Output();
exit();
?>