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

        $arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
        $arrFe = explode('-', $this->inicio);
        $subtitulo = "Del ".$arrFe[2]." de ".$arrMes[(int)$arrFe[1]];
        if($this->inicio != $this->fin){
            $arrFe = explode('-', $this->fin);
            $subtitulo .= " al ".$arrFe[2]." de ".$arrMes[(int)$arrFe[1]]." del ".$arrFe[0];
        }else{
            $subtitulo .= " del ".$arrFe[0];
        }

        $this->Image('../public/assets/images/logo.png', 15, 5, 55, 40);
		$this->Ln(20);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
		$this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'C');
		$this->Cell(0, 10, $subtitulo, 0, 1, 'C');
		$this->Ln(10);
        $this->setX(15);
        $this->Cell(250, 0, '', 'T', 0, 'L');
        $this->Ln(1);
        $this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
		$this->Cell(40, 5, 'FECHA', 0, 0, 'C');
		$this->Cell(40.5, 5, 'USUARIO', 0, 0, 'C');
		$this->Cell(46.5, 5, 'ORIGEN', 0, 0, 'C');
		$this->Cell(46.5, 5, 'DESTINO', 0, 0, 'C');
		$this->Cell(60, 5, 'PRODUCTO', 0, 0, 'L');
		$this->Cell(20, 5, 'CANTIDAD', 0, 1, 'R');
        $this->setX(15);
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
    $contador = 1;
    
    foreach($registros as $prod){
        $relleno = $contador % 2;
        $miReporte->Cell(30, 5, $prod->fecha, 0, 0, 'L', $relleno);
        $miReporte->Cell(46.5, 5, $prod->usuario, 0, 0, 'C', $relleno);
        $miReporte->Cell(46.5, 5, $prod->origen, 0, 0, 'C', $relleno);
        $miReporte->Cell(46.5, 5, $prod->destino, 0, 0, 'C', $relleno);
        $miReporte->Cell(60, 5, $prod->producto, 0, 0, 'L', $relleno);
        $miReporte->Cell(20, 5, $prod->cantidad, 0, 0, 'R', $relleno);
        $contador++;
        $miReporte->SetY($miReporte->GetY()+5);
    }

    $miReporte->setX(15);
    $miReporte->Cell(250, 0, '', 'T', 1, 'L');
    $miReporte->Output();
exit();
?>