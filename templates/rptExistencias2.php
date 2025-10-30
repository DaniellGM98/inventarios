<?php
    require_once('../public/core/tcpdf/config/lang/spa.php');
	require_once('../public/core/tcpdf/tcpdf.php');
    ini_set('error_reporting',0);

Class PDF extends TCPDF{

    private $titulo = "";

    public function setTitulo($tittle, $sub){
        $this->titulo=$tittle;        
		$this->subtitulo=$sub;        
    }

	function Header(){
		
		$this->Image('../public/assets/images/logo.png', 15, 5, 55, 40);

		$arrMes = array('','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre');
		$arrFe = explode('-', utf8_decode($this->subtitulo));
		$subtitulo = "Del ".$arrFe[2]." de ".$arrMes[(int)$arrFe[1]];
		$subtitulo .= " del ".$arrFe[0];

		$this->Ln(20);
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 13);
		$this->Cell(0, 10, utf8_decode($this->titulo), 0, 1, 'R');
		$this->Cell(0, 10, $subtitulo, 0, 1, 'R');
        $this->Cell(0, 10, '', 0, 1, 'R');
        $this->Cell(88, 0, '', 'B', 0, 'L');
		$this->Cell(10, 0, '', 0, 0, 'L');
		$this->Cell(88, 0, '', 'B', 1, 'L');
		$this->SetFont(PDF_FONT_NAME_MAIN, 'B', 10);
		$this->Cell(60, 5, 'DONADOR', 0, 0, 'C');
		$this->Cell(28, 5, 'EXISTENCIAS', 0, 0, 'L');
		$this->Cell(10, 5, '', 0, 0, 'L');
		$this->Cell(60, 5, 'DONADOR', 0, 0, 'C');
		$this->Cell(28, 5, 'EXISTENCIAS', 0, 1, 'C');
		$this->Cell(88, 0, '', 'T', 0, 'L');
		$this->Cell(10, 0, '', 0, 0, 'L');
		$this->Cell(88, 0, '', 'T', 1, 'L');
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
$miReporte->setTitulo($vista, $sub);
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

$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(98, 5, "AMBA ABARROTES", 0, 0, 'L', 0);
$miReporte->Cell(88, 5, 'COMPRAS', 0, 1, 'L', 0);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(60, 5, 'A WALMART ABV', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '72,136.379', 0, 0, 'R', 1);

$miReporte->Cell(10, 5, '', 0, 0, 'L');
$miReporte->Cell(60, 5, 'COMERCIALIZADORA MADE', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '241,929.440', 0, 1, 'R', 1);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(60, 5, 'GRIFFITH ABV', 0, 0, 'L', 0);
$miReporte->Cell(28, 5, '188.440', 0, 0, 'R', 0);

$miReporte->Cell(10, 5, '', 0, 0, 'L');
$miReporte->Cell(60, 5, 'SAHUAYO ABV', 0, 0, 'L', 0);
$miReporte->Cell(28, 5, '6,573.180', 0, 1, 'R', 0);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(60, 5, 'JUMEX CADI JUGO', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '50.000', 0, 0, 'R', 1);

$miReporte->Cell(10, 5, '', 0, 0, 'L');
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(60, 5, '', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '248,502.620', 0, 1, 'R', 1);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(60, 5, 'KELLOGGS AMBA ABARROTE', 0, 0, 'L', 0);
$miReporte->Cell(28, 5, '15,048.600', 0, 0, 'R', 0);

$miReporte->Cell(10, 5, '', 0, 0, 'L', 0);
$miReporte->Cell(88, 5, '', 0, 1, 'L', 0);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(60, 5, 'MONDELEZ ABV', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '6,549.336', 0, 0, 'R', 1);

$miReporte->Cell(10, 5, '', 0, 0, 'L', 0);
$miReporte->Cell(60, 5, 'CORDOBA BANCO', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '4,931.000', 0, 1, 'R', 1);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(60, 5, 'NESTLE AMBA ABARROTES', 0, 0, 'L', 0);
$miReporte->Cell(28, 5, '2,818.200', 0, 0, 'R', 0);

$miReporte->Cell(10, 5, '', 0, 0, 'L');
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(60, 5, '', 0, 0, 'L', 0);
$miReporte->Cell(28, 5, '4,931.000', 0, 1, 'R', 0);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(60, 5, 'PEPSICO GEEP BAMX BAMX', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '1,020.600', 0, 0, 'R', 1);

$miReporte->Cell(10, 5, '', 0, 0, 'L');
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(88, 5, 'LOCALES ABARROTES', 0, 1, 'L', 1);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(60, 5, 'PURATOS DONDADO ABV', 0, 0, 'L', 0);
$miReporte->Cell(28, 5, '761.000', 0, 0, 'R', 0);

$miReporte->Cell(10, 5, '', 0, 0, 'L', 0);
$miReporte->Cell(60, 5, 'FARMACIAS GUADALAJARA', 0, 0, 'L', 0);
$miReporte->Cell(28, 5, '11,498.000', 0, 1, 'R', 0);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(60, 5, 'PURATOS HARINA', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '850.000', 0, 0, 'R', 1);

$miReporte->Cell(10, 5, '', 0, 0, 'L', 0);
$miReporte->Cell(60, 5, 'PUBLICO ABV', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '10.000', 0, 1, 'R', 1);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(60, 5, 'SUPER FOOD BAMX', 0, 0, 'L', 0);
$miReporte->Cell(28, 5, '1,660.305', 0, 0, 'R', 0);

$miReporte->Cell(10, 5, '', 0, 0, 'L', 0);
$miReporte->Cell(60, 5, 'SC MERCANTIL', 0, 0, 'L', 0);
$miReporte->Cell(28, 5, '232.000', 0, 1, 'R', 0);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(60, 5, 'UNILEVER BAMX BAMX', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '745.000', 0, 0, 'R', 1);

$miReporte->Cell(10, 5, '', 0, 0, 'L');
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(60, 5, '', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '11,740.000', 0, 1, 'R', 1);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(60, 5, '', 0, 0, 'L', 0);
$miReporte->Cell(28, 5, '101,827.86', 0, 0, 'R', 0);

$miReporte->Cell(10, 5, '', 0, 0, 'L');
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(88, 5, 'LOCALES PERECEDEROS', 0, 1, 'L', 0);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->Cell(88, 5, '', 0, 0, 'L', 0);

$miReporte->SetFont(PDF_FONT_NAME_MAIN, '', 8);
$miReporte->Cell(10, 5, '', 0, 0, 'L', 0);
$miReporte->Cell(60, 5, 'PUBLICO FYV', 0, 0, 'L', 1);
$miReporte->Cell(28, 5, '48,574.218', 0, 1, 'R', 1);

//
$miReporte->Ln(1);
$miReporte->SetX(10);
$miReporte->Cell(88, 5, '', 0, 0, 'L', 0);

$miReporte->Cell(10, 5, '', 0, 0, 'L');
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 9);
$miReporte->Cell(60, 5, '', 0, 0, 'L', 0);
$miReporte->Cell(28, 5, '48,574.218', 0, 1, 'R', 0);

$miReporte->Ln(20);
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 12);
$miReporte->Cell(88, 5, 'TOTAL EXISTENCIAS: ', 0, 0, 'R');
$miReporte->Cell(10, 5, '', 0, 0, 'C');
$miReporte->SetFont(PDF_FONT_NAME_MAIN, 'B', 12);
$miReporte->Cell(88, 5, "415,575.698", 0, 1, 'L');
$miReporte->Output();
exit();
?>