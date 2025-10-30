<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";
    private $inicio = "";
    private $fin = "";
    private $total = 0;

    public function setTitulo($tittle, $inicio, $fin){
        $this->titulo=$tittle;
        $this->inicio=$inicio;
        $this->fin=$fin;
    }

	function Header(){

        if(utf8_decode($this->inicio) == utf8_decode($this->fin)){
            $subtitulo = 'Del día '.utf8_decode($this->inicio);
        }else{
            $subtitulo = 'Del día '.utf8_decode($this->inicio).' al día '.utf8_decode($this->fin);
        }

        $this->Image('../public/assets/images/logo.png', 15, 5, 55, 40);
		$this->Ln(20);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
		$this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'C');
		$this->Cell(0, 10, $subtitulo, 0, 1, 'C');
		$this->Ln(10);
        $this->SetX(10);
        $this->Cell(255, 0, '', 'T', 0, 'L');
        $this->Ln(1);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->SetX(10);
        $this->Cell(40, 5, 'FECHA', 0, 0, 'C');
        $this->Cell(45, 5, 'COMUNIDAD', 0, 0, 'L');
        $this->Cell(55, 5, 'PROVEEDOR', 0, 0, 'L');
        $this->Cell(55, 5, 'PRODUCTO', 0, 0, 'L');
        $this->Cell(30, 5, 'CANTIDAD', 0, 0, 'C');
        $this->Cell(30, 5, 'KILOGRAMOS', 0, 1, 'C');
        $this->SetX(10);
        $this->Cell(255, 0, '', 'T', 0, 'C');
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

    $miReporte = new PDF('L', 'mm', 'letter');
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
    $miReporte->SetXY(10,60);
    $miReporte->SetMargins(15, 60); 
    $contador = 2;

    foreach($registros as $prod){
        $total += $prod->peso;
        $relleno = $contador % 2;
        $miReporte->SetX(10);
        $miReporte->Cell(40, 5, $prod->fechaf, 0, 0, 'C', $relleno);
        $miReporte->Cell(30, 5, $prod->cliente, 0, 0, 'L', $relleno);
        $miReporte->Cell(65, 5, $prod->proveedor, 0, 0, 'L', $relleno);
        $miReporte->Cell(60, 5, $prod->descripcion, 0, 0, 'L', $relleno);
        $miReporte->Cell(30, 5, $prod->cantidad, 0, 0, 'C', $relleno);
        $miReporte->Cell(30, 5, number_format($prod->peso,3), 0, 1, 'C', $relleno);
        $contador = $contador + 1;
        }

        $relleno = $contador % 2;
        $miReporte->Cell(0, 0, '', 0, 1, 'L');
        $miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $miReporte->Cell(215, 5, 'TOTALES: ', 0, 0, 'R');
        $miReporte->Cell(27, 5, number_format($total,3), 0, 0, 'R');
        $miReporte->Output();
exit();
?>