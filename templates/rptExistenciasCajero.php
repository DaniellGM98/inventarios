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

        $arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
        $subtitulo = "Al ".date('d')." de ".$arrMes[intval(date('m'))]." de ".date('Y')." ".date('H:i:s');

        $this->Image('../public/assets/images/logo.png', 15, 5, 55, 40);
		$this->Ln(20);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
        $this->Cell(50, 0, '', 0, 0, 'L');
		$this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'C');
        $this->Cell(50, 0, '', 0, 0, 'L');
		$this->Cell(0, 10, $subtitulo, 0, 1, 'C');
		$this->Ln(10);
        $this->setX(15);
        $this->Cell(185, 0, '', 'T', 0, 'L');
        $this->Ln(1);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
        $this->setX(15);
        $this->Cell(86, 5, 'PRODUCTO', 0, 0, 'L');
        $this->Cell(50, 5, 'EXISTENCIAS TOTALES', 0, 0, 'C');
        $this->Cell(50, 5, 'EXISTENCIAS CAJA', 0, 1, 'C');
        $this->setX(15);
        $this->Cell(185, 0, '', 'T', 0, 'L');
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

    $miReporte = new PDF('P', 'mm', 'letter');
    $miReporte->setTitulo($vista);
    $miReporte->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
    $miReporte->SetDisplayMode('real');
    $miReporte->SetAuthor('DDS.media'); 
    $miReporte->SetCreator('www.ddsmedia.net'); 
    $miReporte->SetTitle($vista); 
    $miReporte->SetSubject("Existencias [tiendita]");
    $miReporte->SetFillColor(240, 240, 240); 
    $miReporte->AddPage();
    $miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
    $miReporte->SetXY(15,60);
    $miReporte->SetMargins(15, 60); 
    $contador = 1;

    //echo json_encode($registros); exit;

    foreach($registros as $prod){
        $relleno = $contador % 2;
        $miReporte->Cell(86, 5, $prod['descripcion'], 0, 0, 'L', $relleno);
        $miReporte->Cell(50, 5, $prod['stock_tiendita'], 0, 0, 'C', $relleno);
        $miReporte->Cell(50, 5, $prod['stock_cajero'], 0, 1, 'C', $relleno);
	    $contador++;
    }

    $miReporte->setX(15);
    $miReporte->Cell(185, 0, '', 'T', 1, 'L');
    $miReporte->Output();
    exit();
?>