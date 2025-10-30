<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";
    private $inicio = "";
    private $fin = "";
    private $total = 0;
    private $total2 = 0;

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
        $this->Cell(0, 0, '', 'T', 0, 'L');
        $this->Ln(1);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->SetX(10);
        $this->Cell(15, 5, 'FOLIO', 0, 0, 'L');
        $this->Cell(30, 5, 'FECHA', 0, 0, 'L');
        $this->Cell(40, 5, 'COMUNIDAD', 0, 0, 'L');
        $this->Cell(50, 5, 'PROVEEDOR', 0, 0, 'L');
        $this->Cell(60, 5, 'PRODUCTO', 0, 0, 'L');
        $this->Cell(20, 5, 'CANT', 0, 0, 'C');
        $this->Cell(22, 5, 'PESO', 0, 0, 'C');
        $this->Cell(22, 5, 'IMPORTE', 0, 1, 'C');
        $this->SetX(10);
        $this->Cell(0, 0, '', 'T', 0, 'C');
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
    $miReporte->SetXY(15,60);
    $miReporte->SetMargins(15, 60); 
    $contador = 2;

    $comu = '';
    $totalc = 0;
    $totalcp = 0;

    foreach($registros as $prod){
        $total += $prod->peso;
        $total2 += $prod->importe;
        if($comu != $prod->cliente && $contador > 2){
            $relleno = $contador % 2;
            $miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
            $miReporte->SetX(10);
            $miReporte->Cell(215, 5, ' ', 0, 0, 'R', $relleno);
            $miReporte->Cell(22, 5, number_format($totalcp,3), 0, 0, 'C', $relleno);
            $miReporte->Cell(22, 5, number_format($totalc,2), 0, 1, 'R', $relleno);
            $contador = $contador + 1;
    
            $miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
            $comu = $prod->cliente;
            $totalcp = 0;
            $totalc = 0;
        }
        $relleno = $contador % 2;
        $miReporte->SetX(10);
        $miReporte->Cell(15, 5, $prod->folio, 0, 0, 'L', $relleno);
        $miReporte->Cell(30, 5, substr($prod->fechaf,0,16), 0, 0, 'L', $relleno);
        $miReporte->Cell(40, 5, $prod->cliente, 0, 0, 'L', $relleno);
        $miReporte->Cell(50, 5, $prod->proveedor, 0, 0, 'L', $relleno);
        $miReporte->Cell(65, 5, $prod->descripcion, 0, 0, 'L', $relleno);
        $miReporte->Cell(15, 5, $prod->cantidad, 0, 0, 'C', $relleno);
        $miReporte->Cell(22, 5, number_format($prod->peso,3), 0, 0, 'C', $relleno);
        $miReporte->Cell(22, 5, number_format($prod->importe,2), 0, 1, 'R', $relleno);
        $contador = $contador + 1;

        $totalcp += $prod->peso;
        $totalc += $prod->importe;
    }

    $relleno = $contador % 2;
    $miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
    $miReporte->Cell(210, 5, ' ', 0, 0, 'R', $relleno);
    $miReporte->Cell(22, 5, number_format($totalcp,3), 0, 0, 'C', $relleno);
    $miReporte->Cell(22, 5, number_format($totalc,2), 0, 1, 'R', $relleno);
    $miReporte->SetX(10);
    $miReporte->Cell(260, 0, '', 'T', 0, 'C');
    $miReporte->Ln(5);
    $miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
    $miReporte->Cell(200, 5, 'TOTALES: ', 0, 0, 'R');
    $miReporte->Cell(27, 5, number_format($total,3), 0, 0, 'R');
    $miReporte->Cell(28, 5, number_format($total2,3), 0, 0, 'R');
    $miReporte->Output();
exit();
?>