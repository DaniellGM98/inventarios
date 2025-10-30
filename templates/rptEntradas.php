<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";
    private $cveprov = "";
    private $finicio = "";
    private $ffin = "";
    private $ttotal = 0;

    public function setTitulo($tittle, $cveprov, $finicio, $ffin){
        $this->titulo=$tittle;
        $this->cveprov=$cveprov;
        $this->finicio=$finicio;
        $this->ffin=$ffin;
    }

	function Header(){
		$this->Image('../public/assets/images/logo.png', 15, 5, 55, 40);

        if(utf8_decode($this->finicio) == utf8_decode($this->ffin)){
            $subtitulo = 'Del día '.utf8_decode($this->finicio);
        }else{
            $subtitulo = 'Del día '.utf8_decode($this->finicio).' al día '.utf8_decode($this->ffin);
        }
    
        $subCve= '';
        if(utf8_decode($this->cveprov) != 'Todas'){
            $subCve = 'Tipo '.$this->cveprov;
        }

		$this->Ln(10);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
		$this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'C');
		$this->Cell(0, 10, $subtitulo, 0, 1, 'C');
        $this->Cell(0, 10, $subCve, 0, 1, 'C');
		$this->Ln(10);
        $this->SetX(20);
        $this->Cell(250, 0, '', 'T', 0, 'L');
		$this->Ln(1);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->SetX(20);
		$this->Cell(28, 5, 'FECHA', 0, 0, 'L');
        $this->Cell(70, 5, 'DONADOR', 0, 0, 'L');
        $this->Cell(75, 5, 'PRODUCTO', 0, 0, 'L');
        $this->Cell(22, 5, 'VALOR', 0, 0, 'C');
        $this->Cell(25, 5, 'KILOGRAMOS', 0, 0, 'C');
        $this->Cell(30, 5, 'NOTA ENTRADA', 0, 1, 'C');
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
$miReporte->setTitulo($vista, $cveprov, $finicio, $ffin);
$miReporte->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
$miReporte->SetAuthor('DDS.media'); 
$miReporte->SetCreator('www.fpdf.org'); 
$miReporte->SetTitle($vista); 
$miReporte->SetFillColor(240, 240, 240); 
$miReporte->AddPage();
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->SetXY(20,60);
$miReporte->SetMargins(20, 60); 
$contador = 2;

foreach($registros as $prod){
    $relleno = $contador % 2;
		$miReporte->Cell(28, 5, $prod->fechaf, 0, 0, 'C', $relleno);
		$miReporte->Cell(70, 5, $prod->proveedor, 0, 0, 'L', $relleno);
		$miReporte->Cell(75, 5, $prod->descripcion, 0, 0, 'L', $relleno);
		$miReporte->Cell(22, 5, $prod->valor, 0, 0, 'C', $relleno);
		$miReporte->Cell(25, 5, number_format($prod->peso,3), 0, 0, 'C', $relleno);
        $this->ttotal = $this->ttotal + $prod->peso;
		$miReporte->Cell(30, 5, $prod->nota_entrada, 0, 1, 'C', $relleno);
		$contador = $contador + 1;
}

$miReporte->Cell(250, 0, '', 'T', 1, 'L');
$miReporte->Ln(5);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 12);
$miReporte->Cell(200, 5, 'TOTAL: ', 0, 0, 'R');
$miReporte->Cell(60, 5, number_format($this->ttotal,3), 0, 0, 'L');
$miReporte->Output();
exit();
?>